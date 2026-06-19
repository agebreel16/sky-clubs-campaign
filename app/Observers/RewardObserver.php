<?php

namespace App\Observers;

use App\Models\AgentNotification;
use App\Models\Reward;
use App\Notifications\AgentPortalNotification;

class RewardObserver
{
    public function updated(Reward $reward): void
    {
        if (!$reward->isDirty('payment_status')) {
            return;
        }

        $old = $reward->getOriginal('payment_status');
        $new = $reward->payment_status;

        if ($new === 'paid' && $old !== 'paid') {
            $this->notifyPaid($reward);
        } elseif ($new === 'failed' && $old !== 'failed') {
            $this->notifyFailed($reward);
        }
    }

    private function notifyPaid(Reward $reward): void
    {
        $agent   = $reward->agent;
        $amt     = number_format($reward->amount, 0);
        $tag     = $reward->is_first_arrival ? ' (مكافأة الأوائل)' : '';
        $club    = $reward->club;

        AgentNotification::create([
            'agent_id'          => $agent->agent_id,
            'club_id'           => $reward->club_id,
            'notification_type' => 'achievement',
            'title'             => "تم صرف مكافأتك{$tag}!",
            'body'              => "تم إيداع {$amt} ₪ في حسابك عن نادي {$club->club_name}.",
            'category'          => 'in_club',
            'sent_at'           => now(),
        ]);

        if ($agent->portal_token) {
            $agent->notify(new AgentPortalNotification(
                title: "تم صرف مكافأتك{$tag}! 🎉",
                body:  "تم إيداع {$amt} ₪ في حسابك.",
            ));
        }
    }

    private function notifyFailed(Reward $reward): void
    {
        $agent = $reward->agent;
        $amt   = number_format($reward->amount, 0);

        AgentNotification::create([
            'agent_id'          => $agent->agent_id,
            'club_id'           => $reward->club_id,
            'notification_type' => 'warning',
            'title'             => 'تعذّر صرف المكافأة',
            'body'              => "تعذّر إيداع {$amt} ₪، يرجى التواصل مع الإدارة.",
            'category'          => 'in_club',
            'sent_at'           => now(),
        ]);

        if ($agent->portal_token) {
            $agent->notify(new AgentPortalNotification(
                title: 'تعذّر صرف المكافأة ⚠️',
                body:  "تعذّر إيداع {$amt} ₪، يرجى التواصل مع الإدارة.",
                sendSms: true,
            ));
        }
    }
}
