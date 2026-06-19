<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessDistributorSync;
use App\Models\AppSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DistributorsApiSettings extends Page
{
    protected string $view = 'filament.pages.distributors-api-settings';

    public static function getNavigationLabel(): string  { return 'الموزعون'; }
    public static function getNavigationGroup(): ?string { return 'إعدادات API'; }
    public static function getNavigationIcon(): string   { return 'heroicon-o-building-storefront'; }
    public static function getNavigationSort(): ?int     { return 92; }
    public function getTitle(): string                   { return 'إعدادات API الموزعين'; }

    public ?string $api_url   = null;
    public ?string $api_token = null;

    public function mount(): void
    {
        $this->api_url   = AppSetting::get('distributors_api_url');
        $this->api_token = AppSetting::get('distributors_api_token');
    }

    public function save(): void
    {
        AppSetting::set('distributors_api_url',   $this->api_url);
        AppSetting::set('distributors_api_token', $this->api_token);

        Notification::make()->title('تم حفظ إعدادات API الموزعين')->success()->send();
    }

    public function syncNow(): void
    {
        $url   = $this->api_url   ?: AppSetting::get('distributors_api_url');
        $token = $this->api_token ?: AppSetting::get('distributors_api_token');

        if (! $url || ! $token) {
            Notification::make()->title('يرجى إدخال رابط API والتوكن أولاً')->danger()->send();
            return;
        }

        AppSetting::set('distributors_api_url',   $url);
        AppSetting::set('distributors_api_token', $token);

        ProcessDistributorSync::dispatch();

        Notification::make()
            ->title('بدأت مزامنة الموزعين')
            ->body('سيتم إضافة الموزعين الجدد تلقائياً.')
            ->success()
            ->send();
    }

    public function isConfigured(): bool
    {
        return ! empty($this->api_url) && ! empty($this->api_token);
    }

    public function getLastSync(): ?array
    {
        $time   = AppSetting::get('last_distributor_sync');
        $result = AppSetting::get('last_distributor_sync_result');

        if (! $time) return null;

        return [
            'time'   => \Carbon\Carbon::parse($time)->format('d/m/Y H:i'),
            'result' => $result,
        ];
    }
}
