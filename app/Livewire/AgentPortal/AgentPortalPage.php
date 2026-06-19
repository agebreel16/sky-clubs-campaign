<?php

namespace App\Livewire\AgentPortal;

use App\Models\Agent;
use Livewire\Component;

abstract class AgentPortalPage extends Component
{
    public Agent $agent;

    public function mount(string $uuid): void
    {
        // Session already validated by AgentPortalAuth middleware
        $this->agent = Agent::with('club')->findOrFail($uuid);
    }

    // Livewire 3: layout() must receive $agent explicitly — #[Layout] doesn't pass component properties
    protected function renderWithLayout(string $view, array $data = []): \Illuminate\View\View
    {
        return view($view, $data)
            ->layout('layouts.agent-portal', ['agent' => $this->agent]);
    }
}
