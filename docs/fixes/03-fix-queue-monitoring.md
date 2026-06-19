# 🟡 إصلاح #3: مراقبة Queue Worker و Imports المعلقة

## المشكلة
- إذا توقف Queue Worker، تبقى الـ Imports في حالة `pending` بصمت بدون تنبيه.
- لا يوجد فحص دوري للـ imports المعلقة.
- لا dashboard يوضح حالة الـ Queue.

## الحل

### الطبقة 1: Failed Job Handler ذكي
عند فشل Job، تحديث الـ Import مباشرة + إرسال إشعار.

### الطبقة 2: Scheduled Command للفحص الدوري
كل 15 دقيقة، فحص imports المعلقة منذ أكثر من 30 دقيقة وتنبيه الـ Admin.

### الطبقة 3: Widget في Dashboard لحالة الـ Queue

---

## المهام المطلوبة من Claude Code

### المهمة 1: تحسين فشل ProcessDataImport

**الملف:** `app/Jobs/ProcessDataImport.php`

أضف الدوال التالية:

```php
/**
 * عدد المحاولات قبل اعتبار Job فاشلاً نهائياً.
 */
public int $tries = 3;

/**
 * مهلة كل محاولة (ثانية).
 */
public int $timeout = 300;

/**
 * تأخير بين المحاولات (ثانية).
 */
public int $backoff = 60;

/**
 * يُستدعى عند فشل Job نهائياً (بعد كل المحاولات).
 */
public function failed(\Throwable $exception): void
{
    $this->import->update([
        'status' => 'failed',
        'error_message' => $exception->getMessage() . "\n\nTrace:\n" . $exception->getTraceAsString(),
    ]);

    // إشعار جميع الـ Admins
    \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['super_admin', 'admin']);
        })
        ->each(function ($user) use ($exception) {
            \Filament\Notifications\Notification::make()
                ->title('فشل استيراد بيانات')
                ->body("فشل Import #{$this->import->import_id}: " . substr($exception->getMessage(), 0, 200))
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('عرض الاستيراد')
                        ->url(\App\Filament\Resources\DataImportResource::getUrl('view', ['record' => $this->import])),
                ])
                ->sendToDatabase($user);
        });
}
```

### المهمة 2: Command للفحص الدوري

**أنشئ Command جديد:**

```bash
php artisan make:command CheckStuckImports
```

**الملف:** `app/Console/Commands/CheckStuckImports.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\DataImport;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckStuckImports extends Command
{
    protected $signature = 'imports:check-stuck';
    protected $description = 'فحص الاستيرادات المعلقة وتنبيه الـ Admins';

    public function handle(): int
    {
        // imports في pending لأكثر من 15 دقيقة
        $stuckPending = DataImport::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(15))
            ->get();

        // imports في processing لأكثر من 30 دقيقة (الـ timeout هو 5 دقائق)
        $stuckProcessing = DataImport::where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->get();

        $stuck = $stuckPending->merge($stuckProcessing);

        if ($stuck->isEmpty()) {
            $this->info('لا توجد imports معلقة. ✅');
            return Command::SUCCESS;
        }

        $this->warn("وُجد {$stuck->count()} import معلق:");

        foreach ($stuck as $import) {
            $this->line("- #{$import->import_id} | status: {$import->status} | منذ: {$import->created_at->diffForHumans()}");
        }

        // تنبيه الـ Admins (مرة واحدة فقط في الساعة لتجنب spam)
        $cacheKey = 'stuck_imports_alert_sent';
        if (!cache()->has($cacheKey)) {
            User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['super_admin', 'admin']);
            })->each(function ($user) use ($stuck) {
                Notification::make()
                    ->title('⚠️ imports معلقة')
                    ->body("هناك {$stuck->count()} imports معلقة. تحقق من Queue Worker.")
                    ->warning()
                    ->persistent()
                    ->sendToDatabase($user);
            });

            cache()->put($cacheKey, true, now()->addHour());
            $this->info('تم إرسال تنبيهات للـ Admins.');
        }

        return Command::SUCCESS;
    }
}
```

### المهمة 3: جدولة الـ Command

**الملف:** `routes/console.php` (أو `app/Console/Kernel.php` حسب الإصدار)

في Laravel 11+:

```php
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('imports:check-stuck')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// أيضاً: تنظيف Failed Jobs أقدم من 7 أيام
Schedule::command('queue:prune-failed --hours=168')
    ->daily();

// نسخ احتياطي لـ failed_jobs قبل التنظيف
Schedule::command('queue:prune-batches --hours=168')
    ->daily();
```

في Laravel 10 وأقدم — `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('imports:check-stuck')
        ->everyFifteenMinutes()
        ->withoutOverlapping();

    $schedule->command('queue:prune-failed --hours=168')->daily();
}
```

### المهمة 4: Widget لحالة الـ Queue

**أنشئ Widget جديد:**

```bash
php artisan make:filament-widget QueueHealthWidget --type=stats-overview
```

**الملف:** `app/Filament/Widgets/QueueHealthWidget.php`

```php
<?php

namespace App\Filament\Widgets;

use App\Models\DataImport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class QueueHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $heading = 'حالة Queue';

    protected function getStats(): array
    {
        // jobs في الـ queue
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDay())
            ->count();

        // imports
        $stuckImports = DataImport::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(15))
            ->count();

        $todayImports = DataImport::whereDate('created_at', today())->count();
        $todayFailed = DataImport::whereDate('created_at', today())
            ->where('status', 'failed')
            ->count();

        // health status
        $isHealthy = $stuckImports === 0 && $failedJobs === 0;

        return [
            Stat::make('Jobs في الانتظار', $pendingJobs)
                ->description($isHealthy ? 'نظام يعمل' : '⚠️ تحقق من Worker')
                ->descriptionIcon($isHealthy ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($pendingJobs > 50 ? 'warning' : ($isHealthy ? 'success' : 'danger')),

            Stat::make('Imports معلقة', $stuckImports)
                ->description($stuckImports === 0 ? 'كل شيء تحت السيطرة' : 'يحتاج تدخل فوري')
                ->descriptionIcon($stuckImports === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($stuckImports === 0 ? 'success' : 'danger'),

            Stat::make("استيرادات اليوم", "{$todayImports} (فشل: {$todayFailed})")
                ->description($todayFailed === 0 ? 'لا أخطاء' : 'تحقق من الفشل')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color($todayFailed === 0 ? 'success' : 'warning'),

            Stat::make('Failed Jobs (24h)', $failedJobs)
                ->description($failedJobs === 0 ? 'لا فشل' : 'يحتاج مراجعة')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedJobs === 0 ? 'success' : 'danger'),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && method_exists($user, 'hasRole') 
            && $user->hasRole(['super_admin', 'admin']);
    }
}
```

### المهمة 5: تسجيل الـ Widget في Admin Panel

**الملف:** `app/Providers/Filament/AdminPanelProvider.php`

في دالة `panel()`:

```php
->widgets([
    \App\Filament\Widgets\CampaignStatsOverview::class,
    \App\Filament\Widgets\QueueHealthWidget::class,  // ✅ أضف هذا
    \App\Filament\Widgets\ClubStatusWidget::class,
    \App\Filament\Widgets\TodayActivityWidget::class,
    \App\Filament\Widgets\AtRiskAgentsWidget::class,
    \App\Filament\Widgets\ImportStatusWidget::class,
])
```

### المهمة 6: زر إعادة التشغيل اليدوي

**الملف:** `app/Filament/Resources/DataImportResource/Pages/ViewDataImport.php`

أضف Action في `getHeaderActions()`:

```php
protected function getHeaderActions(): array
{
    return [
        Actions\Action::make('reprocess')
            ->label('إعادة المعالجة')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn() => in_array($this->record->status, ['failed', 'pending']))
            ->requiresConfirmation()
            ->modalHeading('إعادة معالجة الاستيراد')
            ->modalDescription('هل أنت متأكد؟ سيتم إعادة تنفيذ كل المنطق على هذا الاستيراد.')
            ->action(function () {
                $this->record->update([
                    'status' => 'pending',
                    'error_message' => null,
                ]);

                \App\Jobs\ProcessDataImport::dispatch($this->record);

                \Filament\Notifications\Notification::make()
                    ->title('تم إعادة الإطلاق')
                    ->body('سيتم معالجة الاستيراد قريباً.')
                    ->success()
                    ->send();
            }),

        Actions\EditAction::make(),
    ];
}
```

---

## التشغيل في الإنتاج

### ضمان عمل Scheduler

في الـ crontab:

```bash
* * * * * cd /var/www/sky-clubs && php artisan schedule:run >> /dev/null 2>&1
```

### ضمان عمل Queue Worker

استخدم Supervisor (Linux):

**الملف:** `/etc/supervisor/conf.d/sky-clubs-queue.conf`

```ini
[program:sky-clubs-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sky-clubs/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/sky-clubs/queue.log
stopwaitsecs=3600
```

ثم:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sky-clubs-queue:*
```

---

## التحقق من النجاح

1. **اختبر يدوياً:**
   ```bash
   php artisan imports:check-stuck
   ```

2. **افتح Dashboard:** يجب أن ترى `QueueHealthWidget` يعمل.

3. **محاكاة فشل:** أوقف Queue Worker، ارفع Excel، انتظر 20 دقيقة، يجب أن يصلك إشعار.
