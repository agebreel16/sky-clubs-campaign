<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDataImport;
use App\Models\AppSetting;
use App\Models\DataImport;
use Illuminate\Console\Command;

class SyncAgentDeals extends Command
{
    protected $signature   = 'app:sync-agent-deals';
    protected $description = 'مزامنة خطوط الوكلاء من API GetSubCustomerDeals';

    public function handle(): int
    {
        // تحقق من التفعيل
        if (AppSetting::get('deals_sync_enabled', '0') !== '1') {
            $this->info('المزامنة التلقائية معطّلة.');
            return self::SUCCESS;
        }

        // تحقق من الفترة الزمنية
        $interval = max(5, (int) AppSetting::get('deals_sync_interval_minutes', 120));

        $lastRun = DataImport::where('source_type', 'deals_api')
            ->whereIn('status', ['success', 'failed'])
            ->latest('updated_at')
            ->value('updated_at');

        if ($lastRun && $lastRun->diffInMinutes(now()) < $interval) {
            $this->info("لم يحن موعد المزامنة — مضت {$lastRun->diffInMinutes(now())} دقيقة من أصل {$interval}.");
            return self::SUCCESS;
        }

        $url      = AppSetting::get('deals_api_url');
        $username = AppSetting::get('deals_api_username');

        if (! $url || ! $username) {
            $this->warn('لم يتم تهيئة إعدادات API خطوط الوكلاء.');
            return self::FAILURE;
        }

        $running = DataImport::where('source_type', 'deals_api')
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($running) {
            $this->warn('مزامنة أخرى قيد التشغيل — تم التخطي.');
            return self::SUCCESS;
        }

        $import = DataImport::create([
            'data_date'   => today(),
            'source_type' => 'deals_api',
            'api_url'     => $url,
            'status'      => 'pending',
            'uploaded_by' => null,
        ]);

        ProcessDataImport::dispatch($import);

        $this->info("تم إطلاق مزامنة خطوط الوكلاء — DataImport ID: {$import->import_id}");

        return self::SUCCESS;
    }
}
