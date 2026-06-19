<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessAgentImport;
use App\Models\AgentImportLog;
use App\Models\AppSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AgentsApiSettings extends Page
{
    protected string $view = 'filament.pages.agents-api-settings';

    public static function getNavigationLabel(): string  { return 'الوكلاء'; }
    public static function getNavigationGroup(): ?string { return 'إعدادات API'; }
    public static function getNavigationIcon(): string   { return 'heroicon-o-users'; }
    public static function getNavigationSort(): ?int     { return 91; }
    public function getTitle(): string                   { return 'إعدادات API الوكلاء'; }

    public ?string $api_url   = null;
    public ?string $api_token = null;

    public function mount(): void
    {
        $this->api_url   = AppSetting::get('agents_api_url');
        $this->api_token = AppSetting::get('agents_api_token');
    }

    public function save(): void
    {
        AppSetting::set('agents_api_url',   $this->api_url);
        AppSetting::set('agents_api_token', $this->api_token);

        Notification::make()->title('تم حفظ إعدادات API الوكلاء')->success()->send();
    }

    public function syncNow(): void
    {
        $url   = $this->api_url   ?: AppSetting::get('agents_api_url');
        $token = $this->api_token ?: AppSetting::get('agents_api_token');

        if (! $url || ! $token) {
            Notification::make()->title('يرجى إدخال رابط API والتوكن أولاً')->danger()->send();
            return;
        }

        $log = AgentImportLog::create([
            'source_type' => 'api',
            'api_url'     => $url,
            'api_token'   => $token,
            'status'      => 'pending',
            'imported_by' => Auth::id(),
        ]);

        ProcessAgentImport::dispatch($log);

        Notification::make()
            ->title('بدأت مزامنة الوكلاء')
            ->body('يمكن متابعة التقدم من صفحة استيراد الوكلاء.')
            ->success()
            ->send();
    }

    public function isConfigured(): bool
    {
        return ! empty($this->api_url) && ! empty($this->api_token);
    }

    public function getLastSync(): ?array
    {
        $log = AgentImportLog::where('source_type', 'api')
            ->latest()
            ->first(['status', 'created_count', 'skipped_count', 'created_at']);

        if (! $log) return null;

        return [
            'time'    => $log->created_at->format('d/m/Y H:i'),
            'status'  => $log->status,
            'created' => $log->created_count ?? 0,
            'skipped' => $log->skipped_count ?? 0,
        ];
    }
}
