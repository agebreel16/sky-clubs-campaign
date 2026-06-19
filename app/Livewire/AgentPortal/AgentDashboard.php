<?php

namespace App\Livewire\AgentPortal;

class AgentDashboard extends AgentPortalPage
{
    public function render()
    {
        return $this->renderWithLayout('livewire.agent-portal.dashboard');
    }
}
