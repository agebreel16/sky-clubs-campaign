<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessDataImport;
use App\Models\AppSetting;
use App\Models\DataImport;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class NumbersApiSettings extends Page
{
    protected string $view = 'filament.pages.numbers-api-settings';

    public static function getNavigationLabel(): string  { return 'الأرقام اليومية'; }
    public static function getNavigationGroup(): ?string { return 'إعدادات API'; }
    public static function getNavigationIcon(): string   { return 'heroicon-o-chart-bar'; }
    public static function getNavigationSort(): ?int     { return 93; }
    public function getTitle(): string                   { return 'إعدادات API الأرقام اليومية'; }

    public ?string $api_url        = null;
    public ?string $api_token      = null;
    public ?string $daily_sync_time = '02:00';

    public function mount(): void
    {
        $this->api_url         = AppSetting::get('numbers_api_url');
        $this->api_token       = AppSetting::get('numbers_api_token');
        $this->daily_sync_time = AppSetting::get('daily_sync_time', '02:00');
    }

    public function save(): void
    {
        AppSetting::set('numbers_api_url',   $this->api_url);
        AppSetting::set('numbers_api_token', $this->api_token);
        AppSetting::set('daily_sync_time',   $this->daily_sync_time);

        Notification::make()->title('تم حفظ إعدادات API الأرقام اليومية')->success()->send();
    }

    public function syncNow(): void
    {
        $url   = $this->api_url   ?: AppSetting::get('numbers_api_url');
        $token = $this->api_token ?: AppSetting::get('numbers_api_token');

        if (! $url || ! $token) {
            Notification::make()->title('يرجى إدخال رابط API والتوكن أولاً')->danger()->send();
            return;
        }

        $import = DataImport::create([
            'data_date'   => today(),
            'source_type' => 'api',
            'api_url'     => $url,
            'api_token'   => $token,
            'status'      => 'pending',
            'uploaded_by' => Auth::id(),
        ]);

        ProcessDataImport::dispatch($import);

        Notification::make()
            ->title('بدأت مزامنة الأرقام اليومية')
            ->body('يمكن متابعة التقدم من صفحة استيراد البيانات.')
            ->success()
            ->send();
    }

    public function isConfigured(): bool
    {
        return ! empty($this->api_url) && ! empty($this->api_token);
    }

    public function getLastSync(): ?array
    {
        $import = DataImport::where('source_type', 'api')
            ->latest()
            ->first(['status', 'processed', 'promotions_count', 'demotions_count', 'created_at']);

        if (! $import) return null;

        return [
            'time'       => $import->created_at->format('d/m/Y H:i'),
            'status'     => $import->status,
            'processed'  => $import->processed ?? 0,
            'promotions' => $import->promotions_count ?? 0,
            'demotions'  => $import->demotions_count ?? 0,
        ];
    }
}
