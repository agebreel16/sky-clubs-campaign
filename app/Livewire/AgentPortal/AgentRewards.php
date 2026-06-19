<?php

namespace App\Livewire\AgentPortal;

class AgentRewards extends AgentPortalPage
{
    public function render()
    {
        $rewards = $this->agent->rewards()->with('club')->orderByDesc('created_at')->get();

        return $this->renderWithLayout('livewire.agent-portal.rewards', [
            'rewards' => $rewards,
            'total'   => $rewards->sum('amount'),
            'paid'    => $rewards->where('payment_status', 'paid')->sum('amount'),
        ]);
    }
}
