<?php

use App\Http\Controllers\AgentPortalController;
use App\Livewire\AgentPortal\AgentDashboard;
use App\Livewire\AgentPortal\AgentHistory;
use App\Livewire\AgentPortal\AgentNotifications;
use App\Livewire\AgentPortal\AgentOpportunities;
use App\Livewire\AgentPortal\AgentProgress;
use App\Livewire\AgentPortal\AgentRewards;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Agent Portal — entry (public, rate limited)
Route::get('/agent/{uuid}', [AgentPortalController::class, 'enter'])
    ->middleware('throttle:10,1')
    ->name('agent.portal.enter');

// Agent Portal — protected pages
Route::prefix('agent/{uuid}')
    ->middleware(['web', 'agent.portal.auth'])
    ->name('agent.portal.')
    ->group(function () {
        Route::get('/dashboard',     AgentDashboard::class)     ->name('dashboard');
        Route::get('/progress',      AgentProgress::class)      ->name('progress');
        Route::get('/notifications', AgentNotifications::class) ->name('notifications');
        Route::get('/rewards',       AgentRewards::class)       ->name('rewards');
        Route::get('/opportunities', AgentOpportunities::class) ->name('opportunities');
        Route::get('/history',       AgentHistory::class)       ->name('history');
        Route::post('/logout', [AgentPortalController::class, 'logout'])->name('logout');
    });

// WebPush subscription endpoint
Route::post('/agent/{uuid}/push/subscribe', [AgentPortalController::class, 'subscribePush'])
    ->middleware(['web', 'agent.portal.auth'])
    ->name('agent.portal.push.subscribe');
