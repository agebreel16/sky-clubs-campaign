<?php

namespace App\Livewire\AgentPortal;

use App\Jobs\ProcessAgentSelfSync;

class AgentSyncing extends AgentPortalPage
{
    public function mount(string $uuid): void
    {
        parent::mount($uuid);
    }

    public function runSync(): void
    {
        $job = new ProcessAgentSelfSync($this->agent);
        $job->handle();

        $this->redirectRoute('agent.portal.dashboard', ['uuid' => $this->agent->agent_id]);
    }

    public function render(): \Illuminate\View\View
    {
        return $this->renderWithLayout('livewire.agent-portal.syncing');
    }
}
