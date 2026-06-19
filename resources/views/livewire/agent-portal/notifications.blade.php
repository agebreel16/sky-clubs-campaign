<div>
    <div class="section-head">
        <div>
            <h2 style="font-size:22px;">🔔 الإشعارات</h2>
            <div class="card-subtitle">{{ $notifications->total() }} إشعار إجمالاً</div>
        </div>
    </div>

    <div class="notif-filters">
        <button wire:click="$set('filter','all')" class="chip {{ $filter === 'all' ? 'active' : '' }}">الكل</button>
        <button wire:click="$set('filter','unread')" class="chip {{ $filter === 'unread' ? 'active' : '' }}">
            غير مقروءة
            @php $unreadCount = $agent->agentNotifications()->where('is_read', false)->count(); @endphp
            @if($unreadCount > 0)<span class="count">{{ $unreadCount }}</span>@endif
        </button>
        <button wire:click="$set('filter','promotion')" class="chip {{ $filter === 'promotion' ? 'active' : '' }}">🏆 ترقية</button>
        <button wire:click="$set('filter','demotion')" class="chip {{ $filter === 'demotion' ? 'active' : '' }}">📉 تهبيط</button>
        <button wire:click="$set('filter','warning')" class="chip {{ $filter === 'warning' ? 'active' : '' }}">⚠️ تحذير</button>
        <button wire:click="markAllRead" class="mark-all">✓ علّم الكل مقروءاً</button>
    </div>

    @forelse($notifications as $notif)
        @php
            $icons = ['promotion' => '🏆', 'demotion' => '📉', 'warning' => '⚠️', 'achievement' => '🌟'];
            $typeClass = ['promotion' => 'promo', 'demotion' => 'demo', 'warning' => 'warn', 'achievement' => 'ach'];
            $icon = $icons[$notif->notification_type] ?? '🔔';
            $tc = $typeClass[$notif->notification_type] ?? 'info';
        @endphp
        <div class="notif t-{{ $tc }}{{ !$notif->is_read ? ' unread' : '' }}"
             wire:click="markRead('{{ $notif->notification_id }}')">
            <div class="notif-icon">{{ $icon }}</div>
            <div class="notif-body">
                <div class="notif-title">{{ $notif->title }}</div>
                <div class="notif-desc">{{ $notif->body }}</div>
                <div class="notif-foot">
                    <span class="notif-time">{{ $notif->sent_at?->diffForHumans() }}</span>
                    <span class="notif-read">{{ $notif->is_read ? '✓ مقروء' : '◯ غير مقروء' }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="empty">لا توجد إشعارات في هذا الفلتر</div>
    @endforelse

    <div style="margin-top:16px;">{{ $notifications->links() }}</div>
</div>
