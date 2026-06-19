<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\Distributor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessDistributorSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function handle(): void
    {
        $url   = AppSetting::get('distributors_api_url');
        $token = AppSetting::get('distributors_api_token');

        if (! $url || ! $token) {
            Log::warning('ProcessDistributorSync: API URL or token not configured.');
            return;
        }

        $response = Http::withToken($token)->timeout(60)->get($url);

        if (! $response->successful()) {
            Log::error('ProcessDistributorSync: API request failed with status ' . $response->status());
            throw new \RuntimeException('فشل طلب API الموزعين: HTTP ' . $response->status());
        }

        $body = $response->json();

        $rows = $body['distributors'] ?? $body['data'] ?? (is_array($body) ? $body : null);

        if (! is_array($rows)) {
            throw new \RuntimeException('صيغة رد API الموزعين غير متوقعة');
        }

        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $distributorId = trim((string) ($row['distributor_id'] ?? ''));

            if (empty($distributorId)) {
                $skipped++;
                continue;
            }

            $exists = Distributor::withTrashed()->where('id', $distributorId)->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $phone = trim((string) ($row['phone'] ?? ''));

            Distributor::forceCreate([
                'id'        => $distributorId,
                'name'      => trim($row['name'] ?? $phone ?: $distributorId),
                'phone'     => $phone ?: null,
                'email'     => $row['email']  ?? null,
                'region'    => $row['region'] ?? null,
                'password'  => Str::random(16),
                'is_active' => true,
            ]);

            $created++;
        }

        AppSetting::set('last_distributor_sync', now()->toDateTimeString());
        AppSetting::set('last_distributor_sync_result', "تم إضافة {$created} موزع جديد، تجاوز {$skipped}.");

        Log::info("ProcessDistributorSync: created={$created}, skipped={$skipped}");
    }
}
