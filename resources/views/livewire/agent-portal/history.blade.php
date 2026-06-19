<div>
    <div class="section-head">
        <div>
            <h2 style="font-size:22px;">📋 سجل الأحداث</h2>
            <div class="card-subtitle">{{ $logs->count() }} حدث · من الأحدث للأقدم</div>
        </div>
        @if($logs->isNotEmpty())
            <div class="meta">
                منذ {{ $logs->last()->event_timestamp?->diffForHumans(now(), true) }}
            </div>
        @endif
    </div>

    <div class="tl">
        @forelse($logs as $i => $log)
            @php
                $typeMap = [
                    'promotion'   => ['icon' => '⬆️', 'tc' => 'promo', 'label' => 'ترقية'],
                    'demotion'    => ['icon' => '⬇️', 'tc' => 'demo',  'label' => 'تهبيط'],
                    'warning'     => ['icon' => '⚠️', 'tc' => 'warn',  'label' => 'تحذير'],
                    'achievement' => ['icon' => '🌟', 'tc' => 'ach',   'label' => 'إنجاز'],
                    'info'        => ['icon' => '💰', 'tc' => 'info',  'label' => 'معلومة'],
                ];
                $cfg = $typeMap[$log->event_type] ?? ['icon' => '📋', 'tc' => 'info', 'label' => $log->event_type];
                $from = $log->fromClub?->club_name ?? 'خارج الأندية';
                $to   = $log->toClub?->club_name;
            @endphp
            <div class="tl-item t-{{ $cfg['tc'] }}">
                <div class="tl-dot">{{ $cfg['icon'] }}</div>
                <div class="tl-card">
                    <div class="tl-head">
                        <div class="tl-title">
                            {{ $cfg['label'] }}
                            @if($to)
                                <span style="font-weight:400;color:var(--slate-600);font-size:13px;margin-right:6px;">{{ $from }} → {{ $to }}</span>
                            @endif
                        </div>
                        <div class="tl-date">{{ $log->event_timestamp?->format('Y-m-d') }}</div>
                    </div>
                    @if($log->reason)
                        <div class="tl-desc">{{ $log->reason }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty" style="text-align:center;padding:60px;">
                <div style="font-size:48px;margin-bottom:12px;">📋</div>
                <div>لا توجد أحداث مسجّلة بعد</div>
            </div>
        @endforelse
    </div>
</div>
