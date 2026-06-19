<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\Opportunity;
use App\Notifications\AgentPortalNotification;
use Illuminate\Console\Command;

class CreateMonthlyMaintenanceOpportunities extends Command
{
    protected $signature   = 'app:monthly-maintenance-opportunities';
    protected $description = 'منح فرصة سحب صيانة شهرية لكل وكيل محافظ على عضويته في نادٍ';

    public function handle(): int
    {
        // Award for the month that just ended
        $targetMonth = now()->subMonth();
        $monthStart  = $targetMonth->copy()->startOfMonth();
        $monthEnd    = $targetMonth->copy()->endOfMonth();

        $agents  = Agent::whereNotNull('current_club_id')->with('club')->get();
        $created = 0;

        foreach ($agents as $agent) {
            // Idempotency: skip if already awarded for this month
            $alreadyExists = Opportunity::where('agent_id', $agent->agent_id)
                ->where('type', 'maintenance')
                ->whereBetween('earned_date', [$monthStart, $monthEnd])
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            Opportunity::create([
                'agent_id'    => $agent->agent_id,
                'club_id'     => $agent->current_club_id,
                'type'        => 'maintenance',
                'earned_date' => $monthEnd,
                'is_active'   => true,
            ]);

            $clubName = $agent->club->club_name;

            AgentNotification::create([
                'agent_id'          => $agent->agent_id,
                'club_id'           => $agent->current_club_id,
                'notification_type' => 'achievement',
                'title'             => 'فرصة سحب شهرية! 🎟️',
                'body'              => "حافظت على عضويتك في {$clubName} — حصلت على فرصة سحب إضافية.",
                'category'          => 'in_club',
                'sent_at'           => now(),
            ]);

            if ($agent->portal_token) {
                $agent->notify(new AgentPortalNotification(
                    title: 'فرصة سحب شهرية! 🎟️',
                    body:  "حافظت على عضويتك في {$clubName} — حصلت على فرصة سحب إضافية.",
                ));
            }

            $created++;
        }

        $this->info("✓ شهر {$targetMonth->format('Y-m')}: منح {$created} فرصة صيانة.");

        return self::SUCCESS;
    }
}
