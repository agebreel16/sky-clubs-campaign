<?php

namespace App\Livewire\AgentPortal;

class AgentOpportunities extends AgentPortalPage
{
    public function render()
    {
        $opportunities = $this->agent->opportunities()->with('club')->get();

        return $this->renderWithLayout('livewire.agent-portal.opportunities', [
            'grouped' => $opportunities->groupBy('club_id'),
            'total'   => $opportunities->count(),
        ]);
    }
}
