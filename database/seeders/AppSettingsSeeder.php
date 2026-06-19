<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'deals_api_url'             => 'https://sales.sky5g.net:8888/ipa/apis/json/internal/generic/v2',
            'deals_api_username'        => 'deal_check',
            'deals_api_password'        => 'Q4LhgrMYu!qEYaD6qPGc0',
            'deals_campaign_start_date' => '2026-05-17',
            'deals_sync_enabled'        => '0',
            'deals_sync_interval_minutes' => '120',
        ];

        foreach ($defaults as $key => $value) {
            AppSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('  → App settings seeded.');
    }
}
