<div
    wire:poll.5s="checkNew"
    x-data="notifBell()"
    @new-portal-notification.window="show($event.detail)"
    style="display:contents;"
>
    {{-- Bell button in navbar --}}
    <button
        class="nav-bell"
        :class="wiggle ? 'wiggle' : ''"
        onclick="window.location.href='{{ route('agent.portal.notifications', $uuid) }}'"
        style="position:relative;color:white;display:flex;align-items:center;background:none;border:none;cursor:pointer;padding:4px;"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        @if($unreadCount > 0)
            <span class="badge" x-data x-init="$el.style.animation='none'; void $el.offsetWidth; $el.style.animation=''">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Toast Stack --}}
    <div class="toast-stack">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                class="toast"
                :class="'t-' + toast.type"
                x-show="toast.visible"
                x-transition:enter="toast-enter"
                x-transition:enter-start="toast-enter-from"
                x-transition:enter-end="toast-enter-to"
                x-transition:leave="toast-leave"
                x-transition:leave-start="toast-leave-from"
                x-transition:leave-end="toast-leave-to"
                @click="handleClick(toast)"
            >
                <div class="toast-icon" x-text="toast.icon"></div>
                <div class="toast-body">
                    <div class="toast-title" x-text="toast.title"></div>
                    <div class="toast-desc" x-text="toast.body"></div>
                    <div class="toast-time">منذ لحظات</div>
                </div>
                <button class="toast-close" @click.stop="dismiss(toast.id)">✕</button>
                <div class="toast-progress">
                    <div class="toast-progress-fill" :style="'animation-duration:' + (toast.dur || 8000) + 'ms'"></div>
                </div>
            </div>
        </template>
    </div>
</div>

<style>
@keyframes toastDeplete {
    from { width: 100%; }
    to   { width: 0%; }
}
.toast-enter      { transition: opacity .4s cubic-bezier(0,0,.2,1), transform .4s cubic-bezier(0,0,.2,1); }
.toast-enter-from { opacity: 0; transform: translateY(-20px) scale(.94); }
.toast-enter-to   { opacity: 1; transform: translateY(0) scale(1); }
.toast-leave      { transition: opacity .3s cubic-bezier(.4,0,1,1), transform .3s cubic-bezier(.4,0,1,1); }
.toast-leave-from { opacity: 1; transform: translateY(0) scale(1); }
.toast-leave-to   { opacity: 0; transform: translateY(-14px) scale(.96); }
.toast-progress-fill { animation: toastDeplete linear forwards; }
@keyframes navBellWiggle {
    0%,100%{transform:rotate(0)}
    15%{transform:rotate(-18deg)}
    35%{transform:rotate(18deg)}
    55%{transform:rotate(-12deg)}
    75%{transform:rotate(8deg)}
}
.nav-bell.wiggle svg { animation: navBellWiggle .6s ease-in-out; }
</style>

<script>
function notifBell() {
    const TONES = {
        promotion: [880, 1100, 1320],
        warning:   [600, 480],
        demotion:  [220],
        achievement: [880, 1100, 1320, 1760],
        info:      [660],
    };
    const DURS = {
        promotion: 10000, warning: 10000, demotion: 12000, achievement: 10000, info: 8000,
    };
    const ICONS = {
        promotion: '🏆', demotion: '📉', warning: '⚠️', achievement: '🌟',
    };

    return {
        toasts: [],
        wiggle: false,

        show(detail) {
            const type = detail.type || 'info';
            const icon = ICONS[type] || '🔔';
            const dur  = DURS[type] || 8000;
            const id   = detail.id;

            // max 3 toasts
            if (this.toasts.length >= 3) {
                const oldest = this.toasts[0];
                oldest.visible = false;
                setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== oldest.id); }, 350);
            }

            this.toasts.push({ ...detail, icon, dur, visible: true });

            // sound
            playPortalNotifSound(type);

            // wiggle bell
            this.wiggle = true;
            setTimeout(() => { this.wiggle = false; }, 700);

            // auto-dismiss
            setTimeout(() => this.dismiss(id, false), dur);
        },

        dismiss(id) {
            const t = this.toasts.find(t => t.id === id);
            if (t) t.visible = false;
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 400);
        },

        handleClick(toast) {
            this.$wire.markRead(toast.id);
            this.dismiss(toast.id);
        },
    };
}
</script>
