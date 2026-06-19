@php
    $tooltipText = $lastSyncTime
        ? "آخر مزامنة {$lastSyncTime} · {$lastProcessed} وكيل · {$lastPromotions} ترقية · {$lastDemotions} تهبيط"
        : 'مزامنة خطوط الوكلاء تلقائياً كل ساعتين';

    $styleMap = [
        'processing' => 'background:rgba(59,130,246,0.14);border:1px solid rgba(59,130,246,0.36);color:#60a5fa;box-shadow:0 0 0 3px rgba(59,130,246,0.07);',
        'pending'    => 'background:rgba(245,158,11,0.14);border:1px solid rgba(245,158,11,0.36);color:#fbbf24;box-shadow:0 0 0 3px rgba(245,158,11,0.07);',
        'success'    => 'background:rgba(16,185,129,0.14);border:1px solid rgba(16,185,129,0.36);color:#34d399;box-shadow:0 0 0 3px rgba(16,185,129,0.07);',
        'idle'       => 'background:rgba(156,163,175,0.08);border:1px solid rgba(156,163,175,0.18);color:#9ca3af;',
    ];
    $pillStyle = $styleMap[$displayStatus] ?? $styleMap['idle'];
@endphp

<div wire:poll.30000ms="refresh">

<div
    x-data="{
        nextSync: new Date('{{ $nextSyncAt }}'),
        countdown: '--:--',
        triggered: false,
        init() { this.tick(); setInterval(() => this.tick(), 1000); },
        tick() {
            const diff = Math.max(0, this.nextSync - Date.now());
            if (diff === 0 && !this.triggered && '{{ $displayStatus }}' === 'idle') {
                this.triggered = true;
                $wire.autoSync();
            }
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            this.countdown = h > 0
                ? String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0')
                : String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        }
    }"
    title="{{ $tooltipText }}"
    style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap;cursor:default;user-select:none;transition:all .35s ease; {{ $pillStyle }}"
>

    {{-- Icon --}}
    @if($displayStatus === 'processing')
        <svg style="width:12px;height:12px;flex-shrink:0;animation:_sbspin .9s linear infinite;"
             viewBox="0 0 24 24" fill="none">
            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
            <path style="opacity:.95" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>

    @elseif($displayStatus === 'pending')
        <span style="position:relative;width:8px;height:8px;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;">
            <span style="position:absolute;width:100%;height:100%;border-radius:50%;background:currentColor;opacity:.3;animation:_sbripple 1.4s ease-out infinite;"></span>
            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0;"></span>
        </span>

    @elseif($displayStatus === 'success')
        <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12"/>
        </svg>

    @else
        <svg style="width:12px;height:12px;flex-shrink:0;opacity:.8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
        </svg>
    @endif

    {{-- Text --}}
    @if($displayStatus === 'processing')
        <span>مزامنة الآن</span>
        <span style="opacity:.35;font-weight:300;margin:0 1px;">·</span>
        <span style="font-variant-numeric:tabular-nums;font-family:monospace;font-size:10.5px;">{{ $progress }}%</span>
        <span style="width:36px;height:2px;background:rgba(96,165,250,.2);border-radius:99px;overflow:hidden;display:inline-block;flex-shrink:0;">
            <span style="display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#3b82f6,#60a5fa);width:{{ $progress }}%;transition:width .7s ease;box-shadow:0 0 4px rgba(96,165,250,.6);"></span>
        </span>

    @elseif($displayStatus === 'pending')
        <span>في الانتظار</span>

    @elseif($displayStatus === 'success')
        <span>اكتملت</span>
        @if($lastProcessed > 0)
            <span style="opacity:.35;font-weight:300;margin:0 1px;">·</span>
            <span style="font-variant-numeric:tabular-nums;font-size:11px;">{{ number_format($lastProcessed) }} وكيل</span>
        @endif

    @else
        <span style="opacity:.7;">التزامن</span>
        @if($nextSyncAt)
            <span style="opacity:.3;font-weight:300;margin:0 1px;">·</span>
            <span x-text="countdown" style="font-variant-numeric:tabular-nums;font-family:monospace;font-size:10.5px;letter-spacing:.2px;"></span>
        @else
            <span style="opacity:.35;font-size:10px;margin-right:2px;">معطّل</span>
        @endif
    @endif

</div>

</div>

<style>
@keyframes _sbspin   { to { transform: rotate(360deg); } }
@keyframes _sbripple { 0% { transform:scale(1); opacity:.3; } 70% { transform:scale(2.2); opacity:0; } 100% { opacity:0; } }
</style>
