<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="vapid-key" content="{{ config('webpush.vapid.public_key') }}">
    <title>{{ $agent->agent_name }} — بوابة الوكيل</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/agent-portal.css') }}">

    @livewireStyles
</head>
<body data-agent-uuid="{{ $agent->agent_id }}">

{{-- Navbar --}}
<header class="navbar">
    <div class="navbar-inner">
        <div class="brand">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:white;width:22px;height:22px;">
                    <path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/>
                    <path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/>
                </svg>
            </div>
            <div class="brand-name">
                Sky Clubs
                <small>AGENT PORTAL</small>
            </div>
        </div>
        <div class="nav-spacer"></div>
        <div class="nav-club-pill">
            <span class="dot"></span>
            @if($agent->club) {{ $agent->club->club_name }} @else لم تنضم بعد @endif
        </div>
        <div class="nav-user">
            <div class="nav-avatar">{{ mb_substr($agent->agent_name, 0, 1) }}</div>
            <div class="nav-user-name">{{ $agent->agent_name }}</div>
        </div>
        <livewire:agent-portal.notification-bell :uuid="$agent->agent_id" />
        <form method="POST" action="{{ route('agent.portal.logout', ['uuid' => $agent->agent_id]) }}" style="display:inline;">
            @csrf
            <button type="submit" class="nav-logout">خروج</button>
        </form>
    </div>
</header>

{{-- Desktop Tabs --}}
@php $uuid = $agent->agent_id; @endphp
<div class="tabs">
    <div class="tabs-inner">
        <a href="{{ route('agent.portal.dashboard', $uuid) }}" class="tab {{ request()->routeIs('agent.portal.dashboard') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M3 12 12 3l9 9"/><path d="M5 10v10h14V10"/></svg>
            الرئيسية
        </a>
        <a href="{{ route('agent.portal.progress', $uuid) }}" class="tab {{ request()->routeIs('agent.portal.progress') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 5-7"/></svg>
            الأداء
        </a>
        <a href="{{ route('agent.portal.notifications', $uuid) }}" class="tab {{ request()->routeIs('agent.portal.notifications') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            الإشعارات
            @php $unreadCount = $agent->agentNotifications()->where('is_read', false)->count(); @endphp
            @if($unreadCount > 0)
                <span class="pill-count">{{ $unreadCount }}</span>
            @endif
        </a>
        <a href="{{ route('agent.portal.rewards', $uuid) }}" class="tab {{ request()->routeIs('agent.portal.rewards') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="12" cy="12" r="9"/><path d="M9 9h4.5a2.5 2.5 0 0 1 0 5H9V9z"/><path d="M9 14h5.5a2.5 2.5 0 0 1 0 5H9v-5z"/></svg>
            المكافآت
        </a>
        <a href="{{ route('agent.portal.opportunities', $uuid) }}" class="tab {{ request()->routeIs('agent.portal.opportunities') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4z"/><path d="M9 6v12"/></svg>
            فرص السحب
        </a>
        <a href="{{ route('agent.portal.history', $uuid) }}" class="tab {{ request()->routeIs('agent.portal.history') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
            السجل
        </a>
    </div>
</div>

<main class="container" style="padding-top:24px; padding-bottom:100px;">
    {{ $slot }}
</main>

{{-- Mobile Bottom Nav --}}
<div class="bottom-nav">
    <a href="{{ route('agent.portal.dashboard', $uuid) }}" class="bottom-tab {{ request()->routeIs('agent.portal.dashboard') ? 'active' : '' }}">
        <div class="icon-w"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M3 12 12 3l9 9"/><path d="M5 10v10h14V10"/></svg></div>
        <span>الرئيسية</span>
    </a>
    <a href="{{ route('agent.portal.progress', $uuid) }}" class="bottom-tab {{ request()->routeIs('agent.portal.progress') ? 'active' : '' }}">
        <div class="icon-w"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 5-7"/></svg></div>
        <span>الأداء</span>
    </a>
    <a href="{{ route('agent.portal.notifications', $uuid) }}" class="bottom-tab {{ request()->routeIs('agent.portal.notifications') ? 'active' : '' }}">
        <div class="icon-w" style="position:relative;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            @if($unreadCount > 0)
                <span style="position:absolute;top:-4px;left:-6px;min-width:14px;height:14px;padding:0 3px;background:var(--danger);color:white;font-size:9px;font-weight:700;border-radius:999px;display:grid;place-items:center;">{{ $unreadCount }}</span>
            @endif
        </div>
        <span>الإشعارات</span>
    </a>
    <a href="{{ route('agent.portal.rewards', $uuid) }}" class="bottom-tab {{ request()->routeIs('agent.portal.rewards') ? 'active' : '' }}">
        <div class="icon-w"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><circle cx="12" cy="12" r="9"/><path d="M9 9h4.5a2.5 2.5 0 0 1 0 5H9V9z"/><path d="M9 14h5.5a2.5 2.5 0 0 1 0 5H9v-5z"/></svg></div>
        <span>المكافآت</span>
    </a>
    <a href="{{ route('agent.portal.opportunities', $uuid) }}" class="bottom-tab {{ request()->routeIs('agent.portal.opportunities') ? 'active' : '' }}">
        <div class="icon-w"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4z"/><path d="M9 6v12"/></svg></div>
        <span>فرص السحب</span>
    </a>
    <a href="{{ route('agent.portal.history', $uuid) }}" class="bottom-tab {{ request()->routeIs('agent.portal.history') ? 'active' : '' }}">
        <div class="icon-w"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg></div>
        <span>السجل</span>
    </a>
</div>

@livewireScripts
<script>
// Count-up animation (Alpine.js compatible)
function countUp(target, duration = 900, decimals = 0) {
    return {
        displayed: 0,
        init() {
            const start = performance.now();
            const step = (now) => {
                const p = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - p, 3);
                this.displayed = target * eased;
                if (p < 1) requestAnimationFrame(step);
                else this.displayed = target;
            };
            requestAnimationFrame(step);
        },
        get formatted() {
            if (decimals > 0) return this.displayed.toFixed(decimals);
            return Math.round(this.displayed).toLocaleString('en-US');
        }
    };
}

// Progress bar fill on load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-fill-width]').forEach(el => {
        const target = el.dataset.fillWidth;
        setTimeout(() => { el.style.width = target; }, 100);
    });
    document.querySelectorAll('.tl-card').forEach((el, i) => {
        el.style.animationDelay = (i * 80) + 'ms';
    });
});

// Singleton — يمنع إنشاء أكثر من 6 AudioContexts (حد Chrome)
let _portalAudioCtx = null;

function playPortalNotifSound(type = 'info') {
    const TONES = {
        promotion:   [880, 1100, 1320],
        warning:     [600, 480],
        demotion:    [220],
        achievement: [880, 1100, 1320, 1760],
        info:        [660],
    };
    const freqs = TONES[type] || TONES.info;
    try {
        if (!_portalAudioCtx || _portalAudioCtx.state === 'closed') {
            _portalAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        const ctx = _portalAudioCtx;
        const play = () => {
            freqs.forEach((f, i) => {
                const o = ctx.createOscillator(), g = ctx.createGain();
                o.type = 'sine'; o.frequency.value = f;
                g.gain.value = 0;
                o.connect(g).connect(ctx.destination);
                const t = ctx.currentTime + i * 0.12;
                g.gain.setValueAtTime(0, t);
                g.gain.linearRampToValueAtTime(0.18, t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.001, t + 0.25);
                o.start(t); o.stop(t + 0.3);
            });
        };
        if (ctx.state === 'suspended') { ctx.resume().then(play); } else { play(); }
    } catch(e) {}
}

if ('serviceWorker' in navigator && document.body.dataset.agentUuid) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}
</script>

<style>
/* Extra styles for Blade/Livewire integration */
.page { animation: pageFade .3s ease-out; }
@keyframes pageFade { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

/* Count-up spans */
[x-data*="countUp"] span { display:inline; }

/* Toast close btn text */
.toast-close { font-size:14px; line-height:1; padding:4px; }

/* Nav links as tabs */
.tabs a.tab, .bottom-nav a.bottom-tab { text-decoration:none; }
.bottom-nav a.bottom-tab { color:inherit; }

/* Notification bell wrapper */
.nav-bell-wrap { position:relative; display:flex; align-items:center; }
</style>

</body>
</html>
