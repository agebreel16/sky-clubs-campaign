<?php

namespace App\Livewire\AgentPortal;

use Livewire\WithPagination;

class AgentNotifications extends AgentPortalPage
{
    use WithPagination;

    public string $filter = 'all';

    public function markRead(string $notificationId): void
    {
        $this->agent->agentNotifications()
            ->where('notification_id', $notificationId)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function markAllRead(): void
    {
        $this->agent->agentNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // أخبر NotificationBell component لتصفير العداد فوراً بدون انتظار الـ poll
        $this->dispatch('notifications-marked-all-read');
    }

    public function render()
    {
        $query = $this->agent->agentNotifications()->orderByDesc('sent_at');

        if ($this->filter === 'unread') {
            $query->where('is_read', false);
        } elseif (!in_array($this->filter, ['all', 'unread'])) {
            $query->where('notification_type', $this->filter);
        }

        return $this->renderWithLayout('livewire.agent-portal.notifications', [
            'notifications' => $query->paginate(15),
        ]);
    }
}
