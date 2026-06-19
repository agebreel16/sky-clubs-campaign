<?php

namespace App\Providers;

use App\Contracts\SmsDriver;
use App\Sms\NullSmsDriver;
use App\Sms\UnifonicSmsDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsDriver::class, fn() => match (config('services.sms.driver', 'null')) {
            'unifonic' => new UnifonicSmsDriver(),
            default    => new NullSmsDriver(),
        });
    }

    public function boot(): void
    {
        \App\Models\Agent::observe(\App\Observers\AgentObserver::class);
        \App\Models\Reward::observe(\App\Observers\RewardObserver::class);
    }
}
