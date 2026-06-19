<x-filament-widgets::widget>
    @php
        // SVG ring constants (100×100 viewBox, stroke-width 7)
        $ringR    = 42;
        $ringCirc = round(2 * M_PI * $ringR, 1); // ≈ 263.9
    @endphp

    {{-- ── Section Header ── --}}
    <div class="sc-clubs-section-header">
        <div>
            <div class="sc-clubs-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9H4a2 2 0 01-2-2V5h4"/>
                    <path d="M18 9h2a2 2 0 002-2V5h-4"/>
                    <path d="M6 2h12v7a6 6 0 01-12 0V2z"/>
                    <path d="M12 15v4M8 22h8"/>
                </svg>
                حالة الأندية 🏆
            </div>
            <div class="sc-clubs-subtitle">تحديث مباشر لتوزيع الوكلاء</div>
        </div>
    </div>

    {{-- ── Clubs Grid ── --}}
    <div class="sc-clubs-grid">
        @foreach($clubs as $item)
            @php
                $club         = $item['club'];
                $membersCount = $item['membersCount'];
                $percentage   = $item['percentage'];
                $latest       = $item['latestMember'];
                $colorVar     = $item['colorVar'];
                $glowColorVar = $item['glowColorVar'];
                $gradientCss  = $item['gradientCss'];
                $isFull       = $item['isFull'];
                $dashOffset   = round($ringCirc * (1 - min($percentage, 100) / 100), 1);
            @endphp

            <div class="sc-club-card" style="--cc: var({{ $colorVar }}); --cc-glow: var({{ $glowColorVar }})">

                {{-- ── Card Header ── --}}
                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 10px;">

                        {{-- Icon box --}}
                        <div class="sc-club-icon-box">
                            @if((int)$club->club_order === 1)
                                {{-- Rocket / launch --}}
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 00-2.91-.09z"/>
                                    <path d="M12 15l-3-3a22 22 0 012-3.95A12.88 12.88 0 0122 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 01-4 2z"/>
                                    <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/>
                                    <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>
                                </svg>
                            @elseif((int)$club->club_order === 2)
                                {{-- Sparkles / excellence --}}
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                                    <path d="M5 3v4"/><path d="M19 17v4"/>
                                    <path d="M3 5h4"/><path d="M17 19h4"/>
                                </svg>
                            @else
                                {{-- Trophy / peak --}}
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9H4a2 2 0 01-2-2V5h4"/>
                                    <path d="M18 9h2a2 2 0 002-2V5h-4"/>
                                    <path d="M6 2h12v7a6 6 0 01-12 0V2z"/>
                                    <path d="M12 15v4M8 22h8"/>
                                </svg>
                            @endif
                        </div>

                        <div>
                            <div class="sc-club-name">{{ $club->club_name }}</div>
                            <div class="sc-club-rank">مرتبة: {{ $club->club_order }}</div>
                        </div>
                    </div>

                    {{-- Status badge --}}
                    @if($isFull)
                        <span class="sc-club-badge-full">
                            <span class="sc-club-badge-full-dot"></span>
                            مكتمل
                        </span>
                    @else
                        <span class="sc-club-badge-active">جاري الملء</span>
                    @endif
                </div>

                {{-- ── SVG Ring Progress ── --}}
                <div style="display: flex; justify-content: center; margin-bottom: 12px;">
                    <div style="position: relative; width: 120px; height: 120px;">
                        <svg width="120" height="120" viewBox="0 0 100 100"
                             style="transform: rotate(-90deg); display: block; overflow: visible;">
                            {{-- Track circle --}}
                            <circle cx="50" cy="50" r="{{ $ringR }}" fill="none"
                                    style="stroke: var(--sc-surface2)"
                                    stroke-width="7"/>
                            {{-- Progress circle --}}
                            <circle cx="50" cy="50" r="{{ $ringR }}" fill="none"
                                    style="stroke: var(--cc)"
                                    stroke-width="7"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $ringCirc }}"
                                    stroke-dashoffset="{{ $dashOffset }}"
                                    class="sc-ring-progress-circle"/>
                        </svg>
                        {{-- Center label (not rotated) --}}
                        <div style="position: absolute; inset: 0; display: flex; flex-direction: column;
                                    align-items: center; justify-content: center; text-align: center;">
                            <span style="font-size: 26px; font-weight: 900; color: var(--cc);
                                         font-variant-numeric: tabular-nums; line-height: 1;">
                                {{ number_format($membersCount) }}
                            </span>
                            <span style="font-size: 10px; color: var(--sc-text3); margin-top: 3px; font-weight: 600;">
                                وكيل
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Members vs capacity --}}
                <div style="display: flex; justify-content: center; align-items: baseline; gap: 5px;
                            margin-bottom: 14px;">
                    <span style="font-size: 12px; color: var(--sc-text2);">من</span>
                    <span style="font-size: 16px; font-weight: 800; color: var(--sc-text);">
                        {{ number_format($club->seat_capacity) }}
                    </span>
                    <span style="font-size: 12px; color: var(--sc-text3);">الحد الأدنى</span>
                </div>

                {{-- ── Progress Bar ── --}}
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 6px;">
                        <span style="color: var(--sc-text3);">نسبة الإشغال</span>
                        <span style="color: var(--cc); font-weight: 700;">{{ $percentage }}%</span>
                    </div>
                    <div class="sc-club-progress-bar">
                        <div class="sc-club-progress-fill" style="width: {{ min($percentage, 100) }}%"></div>
                    </div>
                </div>

                {{-- ── Footer ── --}}
                <div style="border-top: 1px solid var(--sc-border); padding-top: 12px;
                            display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    @if($latest)
                        <div style="min-width: 0; flex: 1;">
                            <div style="font-size: 10px; color: var(--sc-text3); font-weight: 600;">آخر انضمام</div>
                            <div style="font-size: 12px; font-weight: 700; color: var(--sc-text2);
                                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ Str::limit($latest->agent_name, 16) }}
                            </div>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <a href="{{ url('/admin/agents?tableFilters[current_club_id][value]=' . $club->club_id) }}"
                       class="sc-club-detail-btn">
                        عرض التفاصيل
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </a>
                </div>

            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
