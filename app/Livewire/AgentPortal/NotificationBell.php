<?php

namespace App\Livewire\AgentPortal;

use App\Models\Agent;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public string $uuid;
    public int $unreadCount = 0;
    public string $lastSeenAt = '';

    public function mount(string $uuid): void
    {
        $this->uuid       = $uuid;
        $this->lastSeenAt = now()->toDateTimeString();
        $this->unreadCount = Agent::findOrFail($uuid)
            ->agentNotifications()
            ->where('is_read', false)
            ->count();
    }

    public function checkNew(): void
    {
        $agent = Agent::findOrFail($this->uuid);

        $newNotifs = $agent->agentNotifications()
            ->where('sent_at', '>', $this->lastSeenAt)
            ->orderBy('sent_at')
            ->get(['notification_id', 'title', 'body', 'notification_type', 'sent_at']);

        foreach ($newNotifs as $notif) {
            $this->dispatch('new-portal-notification',
                id:    $notif->notification_id,
                title: $notif->title,
                body:  $notif->body,
                type:  $notif->notification_type,
            );
            $this->lastSeenAt = $notif->sent_at->toDateTimeString();
        }

        $this->unreadCount = $agent->agentNotifications()
            ->where('is_read', false)
            ->count();
    }

    public function markRead(string $id): void
    {
        $agent = Agent::findOrFail($this->uuid);

        $agent->agentNotifications()
            ->where('notification_id', $id)
            ->update(['is_read' => true, 'read_at' => now()]);

        $this->unreadCount = $agent->agentNotifications()
            ->where('is_read', false)
            ->count();
    }

    #[On('notifications-marked-all-read')]
    public function handleAllRead(): void
    {
        $this->unreadCount = 0;
    }

    public function render()
    {
        return view('livewire.agent-portal.notification-bell');
    }
}
