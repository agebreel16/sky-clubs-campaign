<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessDataImport;
use App\Models\AppSetting;
use App\Models\DataImport;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DealsApiSettings extends Page
{
    protected string $view = 'filament.pages.deals-api-settings';

    public static function getNavigationLabel(): string  { return 'مزامنة أرقام الوكلاء'; }
    public static function getNavigationGroup(): ?string { return 'إعدادات API'; }
    public static function getNavigationIcon(): string   { return 'heroicon-o-phone'; }
    public static function getNavigationSort(): ?int     { return 95; }
    public function getTitle(): string                   { return 'إعدادات API خطوط الوكلاء'; }

    public ?string $deals_api_url             = 'https://sales.sky5g.net:8888/ipa/apis/json/internal/generic/v2';
    public ?string $deals_api_username        = 'deal_check';
    public ?string $deals_api_password        = 'Q4LhgrMYu!qEYaD6qPGc0';
    public ?string $deals_campaign_start_date = '2026-05-17';

    public bool $deals_sync_enabled          = false;
    public int  $deals_sync_interval_minutes = 120;

    public ?string $connectionStatus = null;
    public string  $connectionError  = '';

    public function mount(): void
    {
        $this->deals_api_url             = AppSetting::get('deals_api_url',             $this->deals_api_url);
        $this->deals_api_username        = AppSetting::get('deals_api_username',        $this->deals_api_username);
        $this->deals_api_password        = AppSetting::get('deals_api_password',        $this->deals_api_password);
        $this->deals_campaign_start_date = AppSetting::get('deals_campaign_start_date', $this->deals_campaign_start_date);

        $this->deals_sync_enabled          = AppSetting::get('deals_sync_enabled', '0') === '1';
        $this->deals_sync_interval_minutes = (int) AppSetting::get('deals_sync_interval_minutes', 120);
    }

    public function save(): void
    {
        AppSetting::set('deals_api_url',             $this->deals_api_url);
        AppSetting::set('deals_api_username',        $this->deals_api_username);
        AppSetting::set('deals_api_password',        $this->deals_api_password);
        AppSetting::set('deals_campaign_start_date', $this->deals_campaign_start_date);
        AppSetting::set('deals_sync_enabled',        $this->deals_sync_enabled ? '1' : '0');
        AppSetting::set('deals_sync_interval_minutes', (string) max(5, $this->deals_sync_interval_minutes));

        Notification::make()->title('تم حفظ إعدادات API خطوط الوكلاء')->success()->send();
    }

    public function syncNow(): void
    {
        $url      = $this->deals_api_url      ?: AppSetting::get('deals_api_url');
        $username = $this->deals_api_username ?: AppSetting::get('deals_api_username');

        if (! $url || ! $username) {
            Notification::make()->title('يرجى إدخال رابط API واسم المستخدم أولاً')->danger()->send();
            return;
        }

        $import = DataImport::where('data_date', today())
            ->where('source_type', 'deals_api')
            ->latest()
            ->first();

        if ($import && $import->status === 'processing') {
            Notification::make()->title('المزامنة قيد التشغيل بالفعل')->warning()->send();
            return;
        }

        if ($import) {
            $import->update(['status' => 'pending', 'api_url' => $url, 'uploaded_by' => Auth::id()]);
        } else {
            $import = DataImport::create([
                'data_date'   => today(),
                'source_type' => 'deals_api',
                'api_url'     => $url,
                'status'      => 'pending',
                'uploaded_by' => Auth::id(),
            ]);
        }

        ProcessDataImport::dispatch($import);

        Notification::make()
            ->title('بدأت مزامنة خطوط الوكلاء')
            ->body('يمكن متابعة التقدم من صفحة استيراد البيانات.')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $url      = $this->deals_api_url             ?: AppSetting::get('deals_api_url');
        $username = $this->deals_api_username        ?: AppSetting::get('deals_api_username');
        $password = $this->deals_api_password        ?: AppSetting::get('deals_api_password');
        $from     = $this->deals_campaign_start_date ?: AppSetting::get('deals_campaign_start_date', '2026-05-17');

        if (! $url || ! $username) {
            $this->connectionStatus = 'failed';
            $this->connectionError  = 'يرجى إدخال رابط API واسم المستخدم أولاً';
            return;
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->post($url, [
                    'username'  => $username,
                    'password'  => $password,
                    'apiName'   => 'GetSubCustomerDeals',
                    'wildcards' => ['TEST_CONNECTION', $from, now()->format('Y-m-d')],
                ]);

            if ($response->json() !== null) {
                $this->connectionStatus = 'success';
                $this->connectionError  = '';
            } else {
                $this->connectionStatus = 'failed';
                $this->connectionError  = 'السيرفر لم يرد بـ JSON — HTTP ' . $response->status();
            }
        } catch (\Exception $e) {
            $this->connectionStatus = 'failed';
            $this->connectionError  = $e->getMessage();
        }
    }

    public function isConfigured(): bool
    {
        return ! empty($this->deals_api_url) && ! empty($this->deals_api_username);
    }

    public function getLastSync(): ?array
    {
        $import = DataImport::where('source_type', 'deals_api')
            ->latest()
            ->first(['status', 'progress', 'processed', 'promotions_count', 'demotions_count', 'updated_at']);

        if (! $import) return null;

        return [
            'time'      => $import->updated_at->diffForHumans(),
            'is_recent' => $import->status === 'success' && $import->updated_at->diffInSeconds(now()) < 60,
            'status'    => $import->status,
            'progress'  => $import->progress ?? 0,
            'processed' => $import->processed ?? 0,
            'promotions'=> $import->promotions_count ?? 0,
            'demotions' => $import->demotions_count ?? 0,
        ];
    }

    public function isProcessing(): bool
    {
        return DataImport::where('source_type', 'deals_api')
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
    }
}
