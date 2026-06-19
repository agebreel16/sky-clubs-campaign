<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDataImport;
use App\Models\AppSetting;
use App\Models\DataImport;
use Illuminate\Console\Command;

class SyncDailyNumbers extends Command
{
    protected $signature   = 'app:sync-daily-numbers';
    protected $description = 'جلب الأرقام اليومية من API البرنامج الرئيسي وتشغيل حسابات الترقية/التهبيط';

    public function handle(): int
    {
        $url   = AppSetting::get('numbers_api_url');
        $token = AppSetting::get('numbers_api_token');

        if (! $url || ! $token) {
            $this->warn('لم يتم تهيئة إعدادات API الأرقام اليومية.');
            return self::FAILURE;
        }

        $import = DataImport::create([
            'data_date'   => today(),
            'source_type' => 'api',
            'api_url'     => $url,
            'api_token'   => $token,
            'status'      => 'pending',
            'uploaded_by' => null,
        ]);

        ProcessDataImport::dispatch($import);

        $this->info("تم إطلاق المزامنة — DataImport ID: {$import->import_id}");

        return self::SUCCESS;
    }
}

