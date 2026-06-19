<?php

use App\Console\Commands\CreateMonthlyMaintenanceOpportunities;
use App\Console\Commands\SyncAgentDeals;
use App\Console\Commands\SyncDailyNumbers;
use App\Models\AppSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CreateMonthlyMaintenanceOpportunities::class)
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command(SyncDailyNumbers::class)
    ->dailyAt(rescue(fn () => AppSetting::get('daily_sync_time', '02:00'), '02:00', false))
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command(SyncAgentDeals::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

