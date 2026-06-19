<?php

namespace App\Observers;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Club;
use App\Models\HistoryLog;
use App\Models\Opportunity;
use App\Models\Reward;
use App\Models\AgentNotification;
use App\Notifications\AgentPortalNotification;

class AgentObserver
{
    public function updated(Agent $agent): void
    {
        // 1. Detect direct Club Change (admin edits current_club_id in form)
        if ($agent->wasChanged('current_club_id')) {
            $this->handleClubChange($agent);
        }

        // 2. Detect violator removal (admin unchecks is_violator in EditAgent)
        if ($agent->wasChanged('is_violator') && ! $agent->is_violator) {
            $this->handleViolatorRemoval($agent);
        }

        // 3. Audit Log (للتعديلات اليدوية — Import يعمل بـ withoutEvents فلا يصل هنا)
        $this->logAudit($agent);

        // 4. إعادة تقييم النادي إذا تغيّرت الأرقام يدوياً — ترقية فقط، لا تهبيط آلي
        // الحارس: إذا غيّر الأدمن current_club_id يدوياً في نفس الحفظ، يتجاوز هذا الشرط
        // لمنع إنشاء Reward+Opportunity مكررة (handleClubChange يتولى ذلك أعلاه)
        if ($agent->wasChanged(['current_total', 'transfer_count', 'new_line_count', 'pre_campaign_count'])
            && ! $agent->wasChanged('current_club_id')) {
            $this->checkAndApplyPromotion($agent);
        }
    }

    private function handleClubChange(Agent $agent): void
    {
        $fromClubId = $agent->getOriginal('current_club_id');
        $toClubId   = $agent->current_club_id;

        $fromClub = $fromClubId ? Club::find($fromClubId) : null;
        $toClub   = $toClubId   ? Club::find($toClubId)   : null;

        if (!$toClub) {
            $eventType = 'demotion';
        } elseif (!$fromClub) {
            $eventType = 'promotion';
        } else {
            $eventType = $toClub->club_order > $fromClub->club_order ? 'promotion' : 'demotion';
        }

        HistoryLog::create([
            'agent_id'        => $agent->agent_id,
            'event_type'      => $eventType,
            'from_club_id'    => $fromClubId,
            'to_club_id'      => $toClubId,
            'reason'          => $eventType === 'promotion' ? 'تحقيق شروط الترقية' : 'قرار إداري',
            'event_timestamp' => now(),
        ]);

        if ($eventType === 'promotion' && $toClub) {
            Reward::create([
                'agent_id'         => $agent->agent_id,
                'club_id'          => $toClubId,
                'amount'           => $agent->is_first_arrival
                                        ? $toClub->first_arrival_reward_amount
                                        : $toClub->base_reward_amount,
                'is_first_arrival' => $agent->is_first_arrival,
                'payment_status'   => 'pending',
            ]);

            $entryCount = $toClub->entry_opportunities ?? 1;
            for ($i = 0; $i < $entryCount; $i++) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $toClubId,
                    'type'        => 'entry',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }

            if ($agent->is_first_arrival) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $toClubId,
                    'type'        => 'first_arrival',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }

            // الإشعار يُرسَل للترقية فقط
            $notifTitle = 'مبروك الترقية!';
            $notifBody  = "تهانينا! لقد انضممت إلى {$toClub->club_name}.";

            AgentNotification::create([
                'agent_id'          => $agent->agent_id,
                'club_id'           => $toClubId,
                'notification_type' => 'promotion',
                'title'             => $notifTitle,
                'body'              => $notifBody,
                'category'          => 'in_club',
                'sent_at'           => now(),
            ]);

            if ($agent->portal_token) {
                $agent->notify(new AgentPortalNotification(title: $notifTitle, body: $notifBody));
            }
        }
        // التهبيط اليدوي: HistoryLog فقط — لا إشعار للوكيل
    }

    private function handleViolatorRemoval(Agent $agent): void
    {
        // سجّل القيم القديمة قبل مسحها (Query Builder لا يُحدِّث $agent فلن تظهر في logAudit)
        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'update',
            'model_type'  => 'Agent',
            'model_id'    => $agent->agent_id,
            'old_values'  => [
                'violator_since'  => $agent->violator_since,
                'violator_reason' => $agent->violator_reason,
            ],
            'new_values'  => ['violator_since' => null, 'violator_reason' => null],
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'description' => 'إلغاء تصنيف المخالفة',
            'status'      => 'success',
        ]);

        Agent::where('agent_id', $agent->agent_id)->update([
            'violator_since'  => null,
            'violator_reason' => null,
        ]);

        HistoryLog::create([
            'agent_id'        => $agent->agent_id,
            'event_type'      => 'achievement',
            'from_club_id'    => null,
            'to_club_id'      => $agent->current_club_id,
            'reason'          => 'إلغاء تصنيف المخالفة',
            'event_timestamp' => now(),
        ]);
        // لا إشعار للوكيل عند إلغاء المخالفة
    }

    private function checkAndApplyPromotion(Agent $agent): void
    {
        $agent->refresh();

        $clubs            = Club::where('is_active', true)->orderBy('club_order')->get();
        $campaignIncrease = $agent->transfer_count + $agent->new_line_count;
        $currentClub      = $agent->club;
        $currentOrder     = $currentClub ? (int) $currentClub->club_order : 0;

        $bestClub = null;
        foreach ($clubs as $club) {
            if ($campaignIncrease >= (int) $club->required_increase
                && $agent->transfer_count >= (int) $club->required_transfer_count) {
                $bestClub = $club;
            }
        }

        $newOrder = $bestClub ? (int) $bestClub->club_order : 0;

        // ترقية فقط — التهبيط يُعالَج عبر Import Job وطلبات المراجعة
        if ($newOrder <= $currentOrder) {
            return;
        }

        $isFirst = Agent::where('current_club_id', $bestClub->club_id)->count() < $bestClub->first_arrival_count;

        Agent::where('agent_id', $agent->agent_id)->update([
            'current_club_id' => $bestClub->club_id,
            'entry_date'      => now(),
            'is_first_arrival' => $isFirst,
        ]);

        HistoryLog::create([
            'agent_id'        => $agent->agent_id,
            'event_type'      => 'promotion',
            'from_club_id'    => $agent->current_club_id,
            'to_club_id'      => $bestClub->club_id,
            'reason'          => "تحقيق {$campaignIncrease} خط جديد",
            'event_timestamp' => now(),
        ]);

        Reward::create([
            'agent_id'         => $agent->agent_id,
            'club_id'          => $bestClub->club_id,
            'amount'           => $isFirst
                                    ? $bestClub->first_arrival_reward_amount
                                    : $bestClub->base_reward_amount,
            'is_first_arrival' => $isFirst,
            'payment_status'   => 'pending',
        ]);

        $entryCount = $bestClub->entry_opportunities ?? 1;
        for ($i = 0; $i < $entryCount; $i++) {
            Opportunity::create([
                'agent_id'    => $agent->agent_id,
                'club_id'     => $bestClub->club_id,
                'type'        => 'entry',
                'earned_date' => now(),
                'is_active'   => true,
            ]);
        }

        $notifTitle = 'مبروك الترقية!';
        $notifBody  = "تهانينا! لقد انضممت إلى {$bestClub->club_name}.";

        AgentNotification::create([
            'agent_id'          => $agent->agent_id,
            'club_id'           => $bestClub->club_id,
            'notification_type' => 'promotion',
            'title'             => $notifTitle,
            'body'              => $notifBody,
            'category'          => 'in_club',
            'sent_at'           => now(),
        ]);

        if ($agent->portal_token) {
            $agent->notify(new AgentPortalNotification(title: $notifTitle, body: $notifBody));
        }
    }

    private function logAudit(Agent $agent): void
    {
        $changes = $agent->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'update',
            'model_type'  => 'Agent',
            'model_id'    => $agent->agent_id,
            'old_values'  => array_intersect_key($agent->getOriginal(), $changes),
            'new_values'  => $changes,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'description' => 'تعديل بيانات الوكيل: ' . implode(', ', array_keys($changes)),
            'status'      => 'success',
        ]);
    }
}
