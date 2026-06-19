<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\AppSetting;
use App\Models\Club;
use App\Models\ClubChangeRequest;
use App\Models\DailySnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessAgentSelfSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 1;

    public function __construct(public Agent $agent) {}

    public function handle(): void
    {
        if ($this->agent->is_violator) {
            $this->updateSyncTime();
            return;
        }

        $url      = AppSetting::get('deals_api_url');
        $username = AppSetting::get('deals_api_username');
        $password = AppSetting::get('deals_api_password');
        $from     = AppSetting::get('deals_campaign_start_date', '2026-05-17');

        if (! $url || ! $username) {
            $this->updateSyncTime();
            return;
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->post($url, [
                    'username'  => $username,
                    'password'  => $password,
                    'apiName'   => 'GetSubCustomerDeals',
                    'wildcards' => [$this->agent->agent_id, $from, today()->format('Y-m-d')],
                ]);

            $body = $response->json();

            if (($body['result'] ?? '') !== 'SUCCESS') {
                Log::warning("AgentSelfSync: API لم يُرجع SUCCESS للوكيل {$this->agent->agent_id}");
                $this->updateSyncTime();
                return;
            }

            $rows      = collect($body['data'] ?? []);
            $newLines  = (int) $rows->where('task_name', 'new-order')
                ->where('status', 'Activated')
                ->sum(fn ($r) => (int) $r['count']);
            $transfers = (int) $rows->where('task_name', 'number-portability')
                ->where('status', 'Activated')
                ->sum(fn ($r) => (int) $r['count']);

            $clubs = Club::where('is_active', true)->orderBy('club_order')->get();

            Agent::withoutEvents(function () use ($newLines, $transfers, $clubs) {
                $this->processRow($newLines, $transfers, $clubs);
            });

        } catch (\Exception $e) {
            Log::error("AgentSelfSync فشل للوكيل {$this->agent->agent_id}: " . $e->getMessage());
        }

        $this->updateSyncTime();
    }

    private function processRow(int $newLines, int $transfers, $clubs): void
    {
        $agent = $this->agent->fresh();
        if (! $agent) return;

        $importedTotal = max(
            $agent->pre_campaign_count,
            $agent->pre_campaign_count + $newLines + $transfers
        );

        $agent->update([
            'transfer_count' => $transfers,
            'new_line_count' => $newLines,
            'current_total'  => $importedTotal,
        ]);

        // تحديث snapshot اليوم إن وُجد من import سابق — لا ننشئ snapshot جديداً لأن import_id NOT NULL
        DailySnapshot::where('data_date', today())
            ->where('agent_id', $agent->agent_id)
            ->update([
                'current_total'   => $importedTotal,
                'transfer_count'  => $transfers,
                'new_line_count'  => $newLines,
                'club_id_at_date' => $agent->current_club_id,
            ]);

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

        $existingPending = ClubChangeRequest::where('agent_id', $agent->agent_id)
            ->where('status', 'pending')
            ->first();

        $snapshot = [
            'campaign_increase' => $campaignIncrease,
            'transfer_count'    => $agent->transfer_count,
            'new_line_count'    => $agent->new_line_count,
            'current_total'     => $agent->current_total,
            'transfer_pct'      => ($currentClub && $currentClub->required_increase > 0)
                ? round($agent->transfer_count / $currentClub->required_increase * 100, 1)
                : 0,
        ];

        if ($newOrder > $currentOrder) {
            if ($existingPending) {
                if ($existingPending->change_type === 'promotion'
                    && $existingPending->to_club_id === $bestClub->club_id) {
                    $existingPending->update(['agent_stats_snapshot' => $snapshot]);
                } else {
                    $existingPending->update(['status' => 'auto_cancelled']);
                    ClubChangeRequest::create([
                        'agent_id'            => $agent->agent_id,
                        'import_id'           => null,
                        'from_club_id'        => $agent->current_club_id,
                        'to_club_id'          => $bestClub->club_id,
                        'change_type'         => 'promotion',
                        'agent_stats_snapshot' => $snapshot,
                        'status'              => 'pending',
                    ]);
                }
            } else {
                ClubChangeRequest::create([
                    'agent_id'            => $agent->agent_id,
                    'import_id'           => null,
                    'from_club_id'        => $agent->current_club_id,
                    'to_club_id'          => $bestClub->club_id,
                    'change_type'         => 'promotion',
                    'agent_stats_snapshot' => $snapshot,
                    'status'              => 'pending',
                ]);
            }
        } elseif ($currentClub && $newOrder < $currentOrder) {
            if ($existingPending) {
                if ($existingPending->change_type === 'demotion') {
                    $existingPending->update(['agent_stats_snapshot' => $snapshot]);
                } else {
                    $existingPending->update(['status' => 'auto_cancelled']);
                    ClubChangeRequest::create([
                        'agent_id'            => $agent->agent_id,
                        'import_id'           => null,
                        'from_club_id'        => $currentClub->club_id,
                        'to_club_id'          => $bestClub?->club_id,
                        'change_type'         => 'demotion',
                        'agent_stats_snapshot' => $snapshot,
                        'status'              => 'pending',
                    ]);
                }
            } else {
                ClubChangeRequest::create([
                    'agent_id'            => $agent->agent_id,
                    'import_id'           => null,
                    'from_club_id'        => $currentClub->club_id,
                    'to_club_id'          => $bestClub?->club_id,
                    'change_type'         => 'demotion',
                    'agent_stats_snapshot' => $snapshot,
                    'status'              => 'pending',
                ]);
            }
        }
    }

    private function updateSyncTime(): void
    {
        DB::table('agents')
            ->where('agent_id', $this->agent->agent_id)
            ->update(['last_self_sync_at' => now()]);
    }
}
