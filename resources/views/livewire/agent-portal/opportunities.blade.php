<div>
    {{-- Lottery Hero --}}
    @php
        $totalCampaign = \App\Models\Opportunity::count() ?: 1;
        $myPct = round(($total / $totalCampaign) * 100, 1);
        $circumference = 2 * M_PI * 56; // r=56
        $dash = ($total / $totalCampaign) * $circumference;
    @endphp
    <div class="lott-hero">
        <div style="position:relative;display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:24px;">
            <div>
                <div class="lott-hero-eyebrow">🎟️ تذاكر السحب</div>
                <div class="lott-hero-num" x-data="countUp({{ $total }})" x-init="init()">
                    <span x-text="formatted"></span><small>تذكرة</small>
                </div>
                <div class="lott-hero-sub">من أصل {{ number_format($totalCampaign) }} تذكرة في الحملة كاملة</div>
            </div>
            <div style="position:relative;">
                <svg width="140" height="140" viewBox="0 0 140 140">
                    <circle cx="70" cy="70" r="56" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="14"/>
                    <circle cx="70" cy="70" r="56" fill="none"
                        stroke="url(#lottGrad)" stroke-width="14"
                        stroke-dasharray="{{ round($dash, 2) }} {{ round($circumference, 2) }}"
                        stroke-linecap="round"
                        transform="rotate(-90 70 70)"
                        style="filter:drop-shadow(0 0 8px #38bdf8);"/>
                    <defs>
                        <linearGradient id="lottGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#38bdf8"/>
                            <stop offset="100%" stop-color="#a78bfa"/>
                        </linearGradient>
                    </defs>
                    <text x="70" y="68" text-anchor="middle" fill="white" font-size="28" font-weight="700">{{ $myPct }}%</text>
                    <text x="70" y="86" text-anchor="middle" fill="rgba(255,255,255,.7)" font-size="11">من الكعكة 🥧</text>
                </svg>
            </div>
        </div>
        <div class="lott-pie">
            <div class="lott-pie-bar">
                <div style="display:flex;height:100%;">
                    @foreach($grouped as $clubId => $clubOpps)
                        @php
                            $colors = ['#06b6d4','#eab308','#94a3b8','#8b5cf6','#10b981'];
                            $pctBar = $total > 0 ? round($clubOpps->count() / $total * 100) : 0;
                        @endphp
                        <div style="width:{{ $pctBar }}%;background:{{ $colors[$loop->index % count($colors)] }};"></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div style="position:relative;display:flex;gap:18px;margin-top:10px;font-size:11px;opacity:.85;flex-wrap:wrap;">
            @foreach($grouped as $clubId => $clubOpps)
                @php $color = ['#06b6d4','#eab308','#94a3b8','#8b5cf6','#10b981'][$loop->index % 5]; @endphp
                <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span style="width:8px;height:8px;border-radius:2px;background:{{ $color }};display:inline-block;"></span>
                    {{ $clubOpps->first()->club?->club_name ?? 'غير محدد' }} ({{ $clubOpps->count() }})
                </span>
            @endforeach
        </div>
    </div>

    {{-- Club Sections --}}
    <div style="margin-top:18px;">
        @forelse($grouped as $clubId => $clubOpps)
            @php $clubName = $clubOpps->first()->club?->club_name ?? 'غير محدد'; @endphp
            <div class="lott-club">
                <div class="lott-club-head">
                    <div class="lott-club-icon entry">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
                    </div>
                    <div class="lott-club-name">{{ $clubName }}</div>
                    <div class="lott-club-total">إجمالي: <strong>{{ $clubOpps->count() }} تذكرة</strong></div>
                </div>
                @foreach($clubOpps->groupBy('type') as $type => $typeOpps)
                    @php
                        $typeLabels = ['entry' => 'دخول النادي', 'first_arrival' => 'أول وصول', 'bonus' => 'مكافأة'];
                        $typeIcons  = ['entry' => '🎟️', 'first_arrival' => '⭐', 'bonus' => '🎁'];
                        $label = $typeLabels[$type] ?? $type;
                        $icon  = $typeIcons[$type] ?? '🎟️';
                        $barPct = $clubOpps->count() > 0 ? round($typeOpps->count() / $clubOpps->count() * 100) : 0;
                    @endphp
                    <div class="ticket-row">
                        <div class="ticket-icon">{{ $icon }}</div>
                        <div class="ticket-label">{{ $label }}</div>
                        <div class="ticket-bar">
                            <div class="ticket-bar-fill {{ $type }}" style="width:0%;transition:width 1.2s;" data-fill-width="{{ $barPct }}%"></div>
                        </div>
                        <div class="ticket-count">{{ $typeOpps->count() }} تذكرة</div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="empty">🎟️ لا توجد فرص سحب بعد</div>
        @endforelse

        {{-- Jackpot Potential --}}
        @if($total > 0)
        <div class="card card-pad" style="background:linear-gradient(135deg,#faf5ff 0%,#ecfeff 100%);border:1px solid #c4b5fd;display:flex;align-items:center;gap:14px;margin-top:14px;">
            <div style="font-size:32px;">🎰</div>
            <div style="flex:1;">
                <div style="font-size:14px;font-weight:700;color:var(--slate-900);">Jackpot Potential</div>
                <div style="font-size:12px;color:var(--slate-600);margin-top:2px;">
                    كل 10 تذاكر = فرصة سحب إضافية · لديك حالياً <strong style="color:var(--purple);">{{ floor($total / 10) }} فرصة سحب</strong>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
