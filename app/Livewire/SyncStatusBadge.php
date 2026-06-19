<?php

namespace App\Livewire;

use App\Models\DataImport;
use Livewire\Component;

class SyncStatusBadge extends Component
{
    public string $status        = 'idle';
    public int    $progress      = 0;
    public string $nextSyncAt    = '';
    public string $displayStatus = 'idle';
    public int    $lastProcessed  = 0;
    public int    $lastPromotions = 0;
    public int    $lastDemotions  = 0;
    public string $lastSyncTime   = '';

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $latest = DataImport::where('source_type', 'deals_api')
            ->latest()
            ->first(['status', 'progress', 'processed', 'promotions_count', 'demotions_count', 'updated_at']);

        $this->status   = $latest?->status ?? 'idle';
        $this->progress = $latest?->progress ?? 0;

        // حالة success تظل مرئية 60 ثانية ثم تعود idle
        if ($this->status === 'success' && $latest?->updated_at?->diffInSeconds(now()) < 60) {
            $this->displayStatus = 'success';
        } elseif (in_array($this->status, ['pending', 'processing'])) {
            $this->displayStatus = $this->status;
        } else {
            $this->displayStatus = 'idle';
        }

        // بيانات الـ tooltip من آخر مزامنة ناجحة
        $lastSuccess = DataImport::where('source_type', 'deals_api')
            ->where('status', 'success')
            ->latest('updated_at')
            ->first(['processed', 'promotions_count', 'demotions_count', 'updated_at']);

        if ($lastSuccess) {
            $this->lastProcessed  = $lastSuccess->processed        ?? 0;
            $this->lastPromotions = $lastSuccess->promotions_count ?? 0;
            $this->lastDemotions  = $lastSuccess->demotions_count  ?? 0;
            $this->lastSyncTime   = $lastSuccess->updated_at?->diffForHumans() ?? '';
        }

        // العداد التنازلي — مبني على آخر مزامنة + interval من الإعدادات
        $enabled  = \App\Models\AppSetting::get('deals_sync_enabled', '0') === '1';
        $interval = max(5, (int) \App\Models\AppSetting::get('deals_sync_interval_minutes', 120));

        if (! $enabled) {
            $this->nextSyncAt = '';
        } else {
            $lastRun = DataImport::where('source_type', 'deals_api')
                ->whereIn('status', ['success', 'failed'])
                ->latest('updated_at')
                ->value('updated_at');

            $nextSync = $lastRun
                ? $lastRun->copy()->addMinutes($interval)
                : now()->addMinutes($interval);

            $this->nextSyncAt = $nextSync->toISOString();
        }
    }

    public function autoSync(): void
    {
        $enabled = \App\Models\AppSetting::get('deals_sync_enabled', '0') === '1';
        if (! $enabled) {
            $this->refresh();
            return;
        }

        $interval = max(5, (int) \App\Models\AppSetting::get('deals_sync_interval_minutes', 120));

        $lastRun = DataImport::where('source_type', 'deals_api')
            ->whereIn('status', ['success', 'failed'])
            ->latest('updated_at')
            ->value('updated_at');

        if ($lastRun && $lastRun->diffInMinutes(now()) < $interval) {
            $this->refresh();
            return;
        }

        $running = DataImport::where('source_type', 'deals_api')
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($running) {
            $this->refresh();
            return;
        }

        $url      = \App\Models\AppSetting::get('deals_api_url');
        $username = \App\Models\AppSetting::get('deals_api_username');

        if (! $url || ! $username) {
            $this->refresh();
            return;
        }

        $import = DataImport::create([
            'data_date'   => today(),
            'source_type' => 'deals_api',
            'api_url'     => $url,
            'status'      => 'pending',
            'uploaded_by' => null,
        ]);

        \App\Jobs\ProcessDataImport::dispatch($import);

        $this->refresh();
    }

    public function render()
    {
        return view('livewire.sync-status-badge');
    }
}
