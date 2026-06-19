<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\AgentImportLog;
use App\Models\AuditLog;
use App\Models\Distributor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessAgentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public AgentImportLog $importLog)
    {
    }

    public function failed(\Throwable $exception): void
    {
        $this->importLog->update([
            'status'        => 'failed',
            'error_message' => 'فشل Job بشكل غير متوقع: ' . $exception->getMessage(),
        ]);
    }

    public function handle(): void
    {
        $this->importLog->update(['status' => 'processing']);
        $startTime = microtime(true);

        $stats = [
            'total'    => 0,
            'created'  => 0,
            'skipped'  => 0,
            'rejected' => 0,
            'errors'   => 0,
        ];
        $errorDetails   = [];
        $successDetails = [];

        try {
            $rows = $this->importLog->source_type === 'excel'
                ? $this->readExcel()
                : $this->readApi();

            $stats['total'] = count($rows);

            Agent::withoutEvents(function () use ($rows, &$stats, &$errorDetails, &$successDetails) {
                foreach ($rows as $index => $row) {
                    try {
                        $this->processRow($row, $stats, $errorDetails, $successDetails, $index + 1);
                    } catch (\Exception $e) {
                        Log::error("AgentImport row {$index} error: " . $e->getMessage(), ['row' => $row]);
                        $stats['errors']++;
                        $stats['rejected']++;
                        $errorDetails[] = ['row' => $index + 1, 'error' => $e->getMessage()];
                    }
                }
            });

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $this->importLog->update([
                'status'                 => 'success',
                'total_rows'             => $stats['total'],
                'created_count'          => $stats['created'],
                'skipped_count'          => $stats['skipped'],
                'rejected_count'         => $stats['rejected'],
                'errors_count'           => $stats['errors'],
                'error_details'          => $errorDetails   ?: null,
                'success_details'        => $successDetails ?: null,
                'processing_duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            $this->importLog->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('ProcessAgentImport failed: ' . $e->getMessage());
        }
    }

    protected function readExcel(): array
    {
        $filePath = Storage::disk('local')->path($this->importLog->stored_filepath);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("ملف Excel غير موجود: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            throw new \RuntimeException('الملف لا يحتوي على بيانات (الصف الأول headers، البيانات من الصف الثاني)');
        }

        $headerRow = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[1]);
        $colMap    = array_flip($headerRow);

        $required = ['agent_id', 'agent_name', 'baseline_count', 'pre_campaign_count', 'current_total'];
        foreach ($required as $col) {
            if (!isset($colMap[$col])) {
                throw new \RuntimeException("عمود مفقود في ملف Excel: '{$col}'");
            }
        }

        $data = [];
        for ($i = 2; $i <= count($rows); $i++) {
            if (!isset($rows[$i])) {
                continue;
            }
            $row = $rows[$i];

            $agentId = trim((string) ($row[$colMap['agent_id']] ?? ''));
            if (empty($agentId)) {
                continue;
            }

            $phoneRaw = isset($colMap['phone']) ? trim((string) ($row[$colMap['phone']] ?? '')) : '';

            $data[] = [
                'agent_id'           => $agentId,
                'agent_name'         => trim((string) ($row[$colMap['agent_name']] ?? '')),
                'phone'              => $phoneRaw ?: null,
                'baseline_count'     => (int) ($row[$colMap['baseline_count']] ?? 0),
                'pre_campaign_count' => (int) ($row[$colMap['pre_campaign_count']] ?? 0),
                'current_total'      => (int) ($row[$colMap['current_total']] ?? 0),
                'transfer_count'     => (int) ($row[$colMap['transfer_count'] ?? ''] ?? 0),
                'new_line_count'     => (int) ($row[$colMap['new_line_count'] ?? ''] ?? 0),
                'distributor_id'     => trim((string) ($row[$colMap['distributor_id'] ?? ''] ?? '')) ?: null,
                'distributor_name'   => isset($colMap['distributor_name'])
                                        ? trim((string) ($row[$colMap['distributor_name']] ?? ''))
                                        : null,
            ];
        }

        return $data;
    }

    protected function readApi(): array
    {
        $response = Http::withToken($this->importLog->api_token)
            ->timeout(60)
            ->get($this->importLog->api_url);

        if (!$response->successful()) {
            throw new \RuntimeException("API أعاد خطأ HTTP {$response->status()}: " . $response->body());
        }

        $body = $response->json();

        // Support multiple response shapes
        if (isset($body['data']) && is_array($body['data'])) {
            return $body['data'];
        }
        if (isset($body['agents']) && is_array($body['agents'])) {
            return $body['agents'];
        }
        if (is_array($body) && array_is_list($body)) {
            return $body;
        }

        throw new \RuntimeException('تنسيق استجابة API غير مدعوم. يُتوقع: array أو {"data":[...]} أو {"agents":[...]}');
    }

    protected function processRow(array $row, array &$stats, array &$errorDetails, array &$successDetails, int $rowNum): void
    {
        $agentId = trim((string) ($row['agent_id'] ?? ''));

        if (empty($agentId)) {
            $stats['rejected']++;
            $errorDetails[] = ['row' => $rowNum, 'error' => 'agent_id فارغ'];
            return;
        }

        // Skip if already exists
        if (Agent::find($agentId)) {
            $stats['skipped']++;
            return;
        }

        $agentName         = trim((string) ($row['agent_name'] ?? ''));
        $baselineCount     = (int) ($row['baseline_count'] ?? 0);
        $preCampaignCount  = (int) ($row['pre_campaign_count'] ?? 0);
        $currentTotal      = (int) ($row['current_total'] ?? 0);
        $transferCount     = max(0, (int) ($row['transfer_count'] ?? 0));
        $newLineCount      = max(0, (int) ($row['new_line_count'] ?? 0));
        $distributorId     = $this->resolveDistributorId($row['distributor_id'] ?? null, $row['distributor_name'] ?? null);
        $phone             = $row['phone'] ?? null;

        // Validate DB CHECK constraints
        if (empty($agentName)) {
            $stats['rejected']++;
            $errorDetails[] = ['row' => $rowNum, 'agent_id' => $agentId, 'error' => 'agent_name فارغ'];
            return;
        }
        if ($baselineCount < 0) {
            $stats['rejected']++;
            $errorDetails[] = ['row' => $rowNum, 'agent_id' => $agentId, 'error' => 'baseline_count لا يمكن أن يكون سالباً'];
            return;
        }
        if ($preCampaignCount > $baselineCount) {
            $stats['rejected']++;
            $errorDetails[] = ['row' => $rowNum, 'agent_id' => $agentId, 'error' => 'pre_campaign_count يجب أن يكون ≤ baseline_count'];
            return;
        }
        if ($currentTotal < $preCampaignCount) {
            $stats['rejected']++;
            $errorDetails[] = ['row' => $rowNum, 'agent_id' => $agentId, 'error' => 'current_total يجب أن يكون ≥ pre_campaign_count'];
            return;
        }

        Agent::forceCreate([
            'agent_id'           => $agentId,
            'agent_name'         => $agentName,
            'phone'              => $phone,
            'baseline_count'     => $baselineCount,
            'pre_campaign_count' => $preCampaignCount,
            'current_total'      => $currentTotal,
            'transfer_count'     => $transferCount,
            'new_line_count'     => $newLineCount,
            'distributor_id'     => $distributorId,
            'current_club_id'    => null,
            'is_first_arrival'   => false,
            'entry_date'         => null,
        ]);

        AuditLog::create([
            'user_id'     => $this->importLog->imported_by,
            'action'      => 'create',
            'model_type'  => 'Agent',
            'model_id'    => $agentId,
            'old_values'  => [],
            'new_values'  => ['agent_id' => $agentId, 'agent_name' => $agentName],
            'ip_address'  => '0.0.0.0',
            'user_agent'  => 'agent-import-job',
            'description' => 'إنشاء وكيل عبر AgentImport: ' . $this->importLog->id,
            'status'      => 'success',
        ]);

        $stats['created']++;
        $successDetails[] = ['agent_id' => $agentId, 'agent_name' => $agentName];
    }

    protected function resolveDistributorId(?string $distributorId, ?string $distributorName): ?string
    {
        // الأولوية: distributor_id المباشر
        if (! empty($distributorId)) {
            return $distributorId;
        }

        if (empty($distributorName)) {
            return null;
        }

        // البحث بالاسم (مع تجاهل السوفت-ديليت)
        $distributor = Distributor::withTrashed()->where('name', $distributorName)->first();

        if ($distributor) {
            // إذا كان محذوفاً، أعده للحياة
            if ($distributor->trashed()) {
                $distributor->restore();
            }
            return $distributor->id;
        }

        // إنشاء موزع جديد بمعلومات افتراضية
        $uid = (string) Str::uuid();
        $distributor = Distributor::forceCreate([
            'id'        => $uid,
            'name'      => $distributorName,
            'phone'     => 'IMP-' . strtoupper(substr($uid, 0, 12)),
            'email'     => 'dist.' . substr($uid, 0, 8) . '@placeholder.local',
            'region'    => 'غير محدد',
            'password'  => Str::random(16),
            'is_active' => false,
        ]);

        Log::info("تم إنشاء موزع جديد: {$distributorName} ({$distributor->id}) — يتطلب تحديث البيانات من لوحة الإدارة.");

        return $distributor->id;
    }
}
