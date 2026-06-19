<?php

namespace App\Livewire\AgentPortal;

class AgentHistory extends AgentPortalPage
{
    public function render()
    {
        $logs = $this->agent->historyLogs()
            ->with(['fromClub', 'toClub'])
            ->orderByDesc('event_timestamp')
            ->get();

        return $this->renderWithLayout('livewire.agent-portal.history', compact('logs'));
    }
}
