<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\Club;
use App\Models\ClubChangeRequest;
use App\Models\DataImport;
use App\Models\DailySnapshot;
use App\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\AppSetting;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessDataImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    protected array $apiErrors = [];

    protected int $totalAgentsQueried = 0;

    public function __construct(public DataImport $import)
    {
    }

    public function handle(): void
    {
        $this->import->update(['status' => 'processing', 'progress' => 0]);
        $startTime = microtime(true);

        $stats = [
            'total'              => 0,
            'processed'          => 0,
            'rejected'           => 0,
            'not_found'          => 0,
            'skipped_violators'  => 0,
            'pending_promotions' => 0,
            'pending_demotions'  => 0,
            'errors'             => 0,
        ];

        try {
            $clubs    = Club::where('is_active', true)->orderBy('club_order', 'asc')->get();
            $dataRows = $this->readData();
            $stats['total'] = $this->totalAgentsQueried > 0
                ? $this->totalAgentsQueried
                : count($dataRows);

            $errorDetails = [];

            DB::transaction(function () use ($dataRows, $clubs, &$stats, &$errorDetails) {
                Agent::withoutEvents(function () use ($dataRows, $clubs, &$stats, &$errorDetails) {
                    foreach ($dataRows as $rowIndex => $row) {
                        try {
                            $this->processAgentRow($row, $clubs, $stats);
                        } catch (\Exception $e) {
                            Log::error("Row {$rowIndex} error: " . $e->getMessage(), ['row' => $row]);
                            $errorDetails[] = [
                                'agent_id' => $row['agent_id'] ?? '؟',
                                'error'    => $e->getMessage(),
                            ];
                            $stats['errors']++;
                            $stats['rejected']++;
                        }
                    }
                });
            });

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $stats['rejected'] += $stats['not_found'] + count($this->apiErrors);

            $allErrors = array_merge($this->apiErrors, $errorDetails);

            $this->import->update([
                'status'                 => 'success',
                'progress'               => 100,
                'total_agents'           => $stats['total'],
                'processed'              => $stats['processed'],
                'rejected'               => $stats['rejected'],
                'promotions_count'       => $stats['pending_promotions'],
                'demotions_count'        => $stats['pending_demotions'],
                'warnings_count'         => $stats['skipped_violators'],
                'errors_count'           => $stats['errors'],
                'processing_duration_ms' => $duration,
                'error_details'          => $allErrors ?: null,
            ]);
        } catch (\Exception $e) {
            $this->import->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Import failed: ' . $e->getMessage());
        }
    }

    protected function readData(): array
    {
        return match($this->import->source_type) {
            'api'       => $this->readApi(),
            'deals_api' => $this->readDealsApi(),
            default     => $this->readExcelFile(),
        };
    }

    protected function readApi(): array
    {
        $response = Http::withToken($this->import->api_token)
            ->timeout(60)
            ->get($this->import->api_url);

        if (! $response->successful()) {
            throw new \RuntimeException('فشل طلب API: HTTP ' . $response->status());
        }

        $body = $response->json();

        if (isset($body['data']))   return $body['data'];
        if (isset($body['agents'])) return $body['agents'];
        if (is_array($body))        return $body;

        throw new \RuntimeException('صيغة رد API غير متوقعة');
    }

    protected function readDealsApi(): array
    {
        $url      = AppSetting::get('deals_api_url');
        $username = AppSetting::get('deals_api_username');
        $password = AppSetting::get('deals_api_password');
        $from     = AppSetting::get('deals_campaign_start_date', '2026-05-17');
        $to       = today()->format('Y-m-d');

        // استثناء المخالفين من استعلام الـ API
        $agents = Agent::whereNull('deleted_at')
            ->where('is_violator', false)
            ->get(['agent_id', 'pre_campaign_count']);

        $this->totalAgentsQueried = $agents->count();

        $chunks      = $agents->chunk(20);
        $totalChunks = $chunks->count();
        $chunkNum    = 0;
        $data        = [];

        foreach ($chunks as $batch) {
            $chunkNum++;

            $responses = Http::pool(function (Pool $pool) use ($batch, $url, $username, $password, $from, $to) {
                return $batch->map(fn ($agent) =>
                    $pool->as($agent->agent_id)
                         ->withoutVerifying()
                         ->timeout(30)
                         ->post($url, [
                             'username'  => $username,
                             'password'  => $password,
                             'apiName'   => 'GetSubCustomerDeals',
                             'wildcards' => [$agent->agent_id, $from, $to],
                         ])
                )->all();
            });

            foreach ($batch as $agent) {
                $response = $responses[$agent->agent_id] ?? null;

                if (! $response || $response instanceof \Exception) {
                    $reason = $response instanceof \Exception ? $response->getMessage() : 'لا استجابة';
                    Log::warning("GetSubCustomerDeals: فشل الطلب للوكيل {$agent->agent_id}: {$reason}");
                    $this->apiErrors[] = ['agent_id' => $agent->agent_id, 'error' => 'فشل طلب API: ' . $reason];
                    continue;
                }

                $body = $response->json();
                if (($body['result'] ?? '') !== 'SUCCESS') {
                    $reason = $body['message'] ?? $body['result'] ?? 'غير معروف';
                    Log::warning("GetSubCustomerDeals: نتيجة غير ناجحة للوكيل {$agent->agent_id}", ['body' => $body]);
                    $this->apiErrors[] = ['agent_id' => $agent->agent_id, 'error' => 'API أعاد: ' . $reason];
                    continue;
                }

                $rows      = collect($body['data'] ?? []);
                $newLines  = (int) $rows->where('task_name', 'new-order')->where('status', 'Activated')->sum(fn ($r) => (int) $r['count']);
                $transfers = (int) $rows->where('task_name', 'number-portability')->where('status', 'Activated')->sum(fn ($r) => (int) $r['count']);

                $data[] = [
                    'agent_id'       => $agent->agent_id,
                    'new_line_count' => $newLines,
                    'transfer_count' => $transfers,
                    'current_total'  => $agent->pre_campaign_count + $newLines + $transfers,
                ];
            }

            if ($totalChunks > 0) {
                $this->import->update(['progress' => (int)(($chunkNum / $totalChunks) * 90)]);
            }
        }

        return $data;
    }

    protected function readExcelFile(): array
    {
        $filePath = Storage::disk('local')->path($this->import->stored_filepath);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("ملف Excel غير موجود: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            throw new \RuntimeException('الملف لا يحتوي على بيانات (صف البيانات يبدأ من السطر 2)');
        }

        $headerRow = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $rows[1]);

        $colMap = [];
        foreach ($headerRow as $col => $name) {
            $colMap[$name] = $col;
        }

        $required = ['agent_id', 'current_total', 'transfer_count', 'new_line_count'];
        foreach ($required as $req) {
            if (!isset($colMap[$req])) {
                throw new \RuntimeException("عمود مفقود في ملف Excel: '{$req}'");
            }
        }

        $data = [];
        for ($i = 2; $i <= count($rows); $i++) {
            if (!isset($rows[$i])) {
                continue;
            }

            $row = $rows[$i];

            $agentId      = trim((string) ($row[$colMap['agent_id']]      ?? ''));
            $currentTotal = (int)         ($row[$colMap['current_total']]  ?? 0);
            $transferCnt  = (int)         ($row[$colMap['transfer_count']] ?? 0);
            $newLineCnt   = (int)         ($row[$colMap['new_line_count']] ?? 0);

            if (empty($agentId) && $currentTotal === 0) {
                continue;
            }

            $data[] = [
                'agent_id'       => $agentId,
                'current_total'  => $currentTotal,
                'transfer_count' => $transferCnt,
                'new_line_count' => $newLineCnt,
            ];
        }

        return $data;
    }

    protected function processAgentRow(array $row, $clubs, array &$stats): void
    {
        // [1] إيجاد الوكيل
        $agent = Agent::find($row['agent_id']);
        if (!$agent) {
            $agent = Agent::where('agent_name', $row['agent_id'])->first();
        }

        if (!$agent) {
            Log::warning("Agent not found: " . $row['agent_id']);
            $stats['not_found']++;
            return;
        }

        // [2] تجاوز الوكلاء المخالفين كلياً
        if ($agent->is_violator) {
            $stats['skipped_violators']++;
            return;
        }

        // [3] تحديث الأرقام
        $importedTotal    = (int) $row['current_total'];
        $importedTransfer = max(0, (int) $row['transfer_count']);
        $importedNewLines = max(0, (int) $row['new_line_count']);

        $floorValue = $agent->pre_campaign_count;
        $updateData = [
            'transfer_count' => $importedTransfer,
            'new_line_count' => $importedNewLines,
        ];

        if (isset($row['pre_campaign_count'])) {
            $sourcePre  = max(0, (int) $row['pre_campaign_count']);
            $newPre     = min($agent->pre_campaign_count, $sourcePre);
            $updateData['pre_campaign_count'] = $newPre;
            $floorValue = $newPre;
        }

        $updateData['current_total'] = max($floorValue, $importedTotal);
        $agent->update($updateData);

        // AuditLog يدوي (Observer متوقف داخل withoutEvents)
        $changes = $agent->getChanges();
        unset($changes['updated_at']);
        if (!empty($changes)) {
            \App\Models\AuditLog::create([
                'user_id'     => $this->import->uploaded_by,
                'action'      => 'update',
                'model_type'  => 'Agent',
                'model_id'    => $agent->agent_id,
                'old_values'  => array_intersect_key($agent->getOriginal(), $changes),
                'new_values'  => $changes,
                'ip_address'  => '0.0.0.0',
                'user_agent'  => 'import-job',
                'description' => 'تحديث بيانات الوكيل عبر Import: ' . $this->import->import_id,
                'status'      => 'success',
            ]);
        }

        DailySnapshot::updateOrCreate(
            [
                'data_date' => $this->import->data_date,
                'agent_id'  => $agent->agent_id,
            ],
            [
                'import_id'          => $this->import->import_id,
                'baseline_count'     => $agent->baseline_count,
                'pre_campaign_count' => $agent->pre_campaign_count,
                'current_total'      => $agent->current_total,
                'transfer_count'     => $agent->transfer_count,
                'new_line_count'     => $agent->new_line_count,
                'club_id_at_date'    => $agent->current_club_id,
            ]
        );

        $stats['processed']++;

        // [4] حساب التأهيل
        $agent->refresh();
        $campaignIncrease = $agent->transfer_count + $agent->new_line_count;

        $bestClub = null;
        foreach ($clubs as $club) {
            if ($campaignIncrease >= (int) $club->required_increase
                && $agent->transfer_count >= (int) $club->required_transfer_count) {
                $bestClub = $club;
            }
        }

        $currentClub  = $agent->club;
        $currentOrder = $currentClub ? (int) $currentClub->club_order : 0;
        $newOrder     = $bestClub    ? (int) $bestClub->club_order    : 0;

        // [5] الطلب المعلّق الموجود لهذا الوكيل
        $existingPending = ClubChangeRequest::where('agent_id', $agent->agent_id)
            ->where('status', 'pending')
            ->first();

        // [6] Snapshot للطلب
        $snapshot = [
            'campaign_increase' => $campaignIncrease,
            'transfer_count'    => $agent->transfer_count,
            'new_line_count'    => $agent->new_line_count,
            'current_total'     => $agent->current_total,
            'transfer_pct'      => ($currentClub && $currentClub->required_increase > 0)
                ? round($agent->transfer_count / $currentClub->required_increase * 100, 1)
                : 0,
        ];

        // ── PROMOTION مكتشفة ────────────────────────────────────────────────
        if ($newOrder > $currentOrder) {
            if ($existingPending) {
                if ($existingPending->change_type === 'promotion'
                    && $existingPending->to_club_id === $bestClub->club_id) {
                    // نفس الطلب — تحديث snapshot فقط
                    $existingPending->update(['agent_stats_snapshot' => $snapshot]);
                } else {
                    // طلب قديم مختلف — إلغاء وإنشاء جديد
                    $existingPending->update(['status' => 'auto_cancelled']);
                    ClubChangeRequest::create([
                        'agent_id'             => $agent->agent_id,
                        'import_id'            => $this->import->import_id,
                        'from_club_id'         => $agent->current_club_id,
                        'to_club_id'           => $bestClub->club_id,
                        'change_type'          => 'promotion',
                        'agent_stats_snapshot' => $snapshot,
                        'status'               => 'pending',
                    ]);
                    $stats['pending_promotions']++;
                }
            } else {
                ClubChangeRequest::create([
                    'agent_id'             => $agent->agent_id,
                    'import_id'            => $this->import->import_id,
                    'from_club_id'         => $agent->current_club_id,
                    'to_club_id'           => $bestClub->club_id,
                    'change_type'          => 'promotion',
                    'agent_stats_snapshot' => $snapshot,
                    'status'               => 'pending',
                ]);
                $stats['pending_promotions']++;
            }
        }

        // ── DEMOTION مكتشف ──────────────────────────────────────────────────
        elseif ($currentClub && $newOrder < $currentOrder) {
            if ($existingPending) {
                if ($existingPending->change_type === 'demotion') {
                    // نفس الطلب — تحديث snapshot فقط
                    $existingPending->update(['agent_stats_snapshot' => $snapshot]);
                } else {
                    // طلب قديم مختلف (ترقية لم تُراجَع) — إلغاء وإنشاء جديد
                    $existingPending->update(['status' => 'auto_cancelled']);
                    ClubChangeRequest::create([
                        'agent_id'             => $agent->agent_id,
                        'import_id'            => $this->import->import_id,
                        'from_club_id'         => $currentClub->club_id,
                        'to_club_id'           => $bestClub?->club_id,
                        'change_type'          => 'demotion',
                        'agent_stats_snapshot' => $snapshot,
                        'status'               => 'pending',
                    ]);
                    $stats['pending_demotions']++;
                }
            } else {
                ClubChangeRequest::create([
                    'agent_id'             => $agent->agent_id,
                    'import_id'            => $this->import->import_id,
                    'from_club_id'         => $currentClub->club_id,
                    'to_club_id'           => $bestClub?->club_id,
                    'change_type'          => 'demotion',
                    'agent_stats_snapshot' => $snapshot,
                    'status'               => 'pending',
                ]);
                $stats['pending_demotions']++;
            }
        }

        // ── لا تغيير — إلغاء أي طلب لم يعد ملائماً ────────────────────────
        else {
            if ($existingPending) {
                $existingPending->update(['status' => 'auto_cancelled']);
            }
        }

        // ── BONUS OPPORTUNITIES (للوكلاء في Peak Club حالياً) ───────────────
        if ($currentClub && $currentClub->has_bonus_opportunities && $currentClub->bonus_per_numbers > 0) {
            $bonusCount = (int) floor($agent->new_line_count / $currentClub->bonus_per_numbers);
            $existing   = $agent->opportunities()
                ->where('club_id', $currentClub->club_id)
                ->where('type', 'bonus')
                ->count();

            for ($i = $existing; $i < $bonusCount; $i++) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $currentClub->club_id,
                    'type'        => 'bonus',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }
        }
    }
}
