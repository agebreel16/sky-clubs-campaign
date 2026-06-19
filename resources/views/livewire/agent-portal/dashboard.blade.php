<div>
    {{-- Hero Card --}}
    @if($agent->club)
        @php
            $rankInClub = \App\Models\Agent::where('current_club_id', $agent->current_club_id)
                ->whereNotNull('current_club_id')
                ->orderByDesc(\Illuminate\Support\Facades\DB::raw('transfer_count + new_line_count'))
                ->pluck('agent_id')
                ->search($agent->agent_id);
            $rankInClub = ($rankInClub !== false) ? $rankInClub + 1 : '—';
            $totalInClub = \App\Models\Agent::where('current_club_id', $agent->current_club_id)->whereNotNull('current_club_id')->count();
            $daysIn = $agent->entry_date ? (int) $agent->entry_date->diffInDays(now()) : 0;
            $isTop = !\App\Models\Club::where('is_active', true)->where('club_order', '>', $agent->club->club_order)->exists();
            $isFirstArrival = $agent->is_first_arrival;
        @endphp
        <div class="hero">
            <div class="grid-bg"></div>
            <div class="hero-row">
                <div class="hero-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="30" height="30"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
                </div>
                <div style="flex:1;">
                    <div class="hero-eyebrow">ناديك الحالي</div>
                    <div class="hero-title">{{ $agent->club->club_name }}</div>
                    <div class="hero-sub">{{ $isTop ? 'المستوى الأعلى' : 'عضو نشط' }}</div>
                </div>
                @if($isFirstArrival)
                    <div class="first-arrival-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
                        أول وصول
                    </div>
                @endif
            </div>
            <div class="hero-divider"></div>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    انضممت منذ {{ $daysIn }} يوماً
                </div>
                <div class="rank-medal">
                    @if($rankInClub === 1) 🥇
                    @elseif($rankInClub === 2) 🥈
                    @elseif($rankInClub === 3) 🥉
                    @else #{{ $rankInClub }}
                    @endif
                    رتبتك #{{ $rankInClub }} من {{ $totalInClub }} في النادي
                </div>
                @if($agent->last_self_sync_at)
                    <div class="hero-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><polyline points="21 3 21 9 15 9"/></svg>
                        آخر مزامنة: {{ $agent->last_self_sync_at->diffForHumans() }}
                    </div>
                @endif
                <div class="hero-meta-item live-dot">اتصال حي</div>
            </div>
        </div>
    @else
        @php
            $firstClub = \App\Models\Club::where('is_active', true)->orderBy('club_order')->first();
            $needed = $firstClub ? max(0, $firstClub->required_increase - ($agent->transfer_count + $agent->new_line_count)) : 0;
        @endphp
        <div class="hero" style="background: linear-gradient(135deg, #475569 0%, #64748b 100%);">
            <div class="grid-bg"></div>
            <div class="hero-row">
                <div class="hero-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="30" height="30"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>
                </div>
                <div style="flex:1;">
                    <div class="hero-eyebrow">حالتك</div>
                    <div class="hero-title">لم تنضم لأي نادٍ بعد</div>
                    <div class="hero-sub">تحتاج {{ $needed }} زيادة فقط للوصول إلى {{ $firstClub?->club_name ?? 'نادي البداية' }}</div>
                </div>
            </div>
            <div class="hero-divider"></div>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <strong>ابدأ الآن وانضم إلى أول نادٍ</strong>
                    <span style="display:inline-block;animation:pulse 1.4s infinite;">←</span>
                </div>
                @if($agent->last_self_sync_at)
                    <div class="hero-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><polyline points="21 3 21 9 15 9"/></svg>
                        آخر مزامنة: {{ $agent->last_self_sync_at->diffForHumans() }}
                    </div>
                @endif
                <div class="hero-meta-item live-dot">اتصال حي</div>
            </div>
        </div>
    @endif

    {{-- Violator Banner --}}
    @if($agent->is_violator)
        <div class="warn" style="border-color:#ef4444;background:rgba(239,68,68,0.08);">
            <div class="warn-icon" style="background:rgba(239,68,68,0.15);color:#ef4444;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>
            </div>
            <div class="warn-body">
                <div class="warn-title" style="color:#ef4444;">تنبيه: حسابك مُعلَّق من قِبَل الإدارة</div>
                <div class="warn-desc">للاستفسار تواصل مع مشرفك المباشر.</div>
            </div>
        </div>
    @endif

    {{-- KPI Grid --}}
    <div>
        <div class="section-head">
            <h2>مؤشرات الأداء</h2>
            <span class="meta">آخر تحديث: منذ ثوانٍ · حي</span>
        </div>
        <div class="kpi-grid">
            @php
                $kpis = [
                    ['v' => $agent->campaign_increase,   'l' => 'زيادة الحملة',     'color' => '#10b981', 'neg' => false],
                    ['v' => $agent->transfer_count,      'l' => 'تحويلات/نيود',          'color' => '#8b5cf6', 'neg' => false],
                    ['v' => $agent->new_line_count,      'l' => 'خطوط جديدة',      'color' => '#f59e0b', 'neg' => false],
                    ['v' => $agent->transfer_percentage, 'l' => 'نسبة التحويل',    'color' => '#0ea5e9', 'neg' => false, 'suffix' => '%', 'dec' => 1],
                    ['v' => abs($agent->baseline_loss),  'l' => 'خطوط مفقودة',     'color' => '#ef4444', 'neg' => true],
                ];
            @endphp
            @foreach($kpis as $kpi)
                <div class="kpi" x-data="countUp({{ $kpi['v'] }}, 900, {{ $kpi['dec'] ?? 0 }})" x-init="init()">
                    <div class="kpi-icon" style="background:{{ $kpi['color'] }}22; color:{{ $kpi['color'] }};">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 5-7"/></svg>
                    </div>
                    <div style="height:18px;"></div>
                    <div class="kpi-value {{ $kpi['neg'] ? 'neg' : '' }}">
                        {{ $kpi['neg'] ? '-' : '' }}<span x-text="formatted"></span>{{ $kpi['suffix'] ?? '' }}
                    </div>
                    <div class="kpi-label">{{ $kpi['l'] }}</div>
                    <div class="kpi-spark">
                        <svg width="70" height="22" viewBox="0 0 70 22" preserveAspectRatio="none">
                            <polyline fill="none" stroke="{{ $kpi['color'] }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                points="{{ collect([0.6,0.7,0.8,0.9,1])->map(fn($f,$i) => ($i*17.5).','.max(2, 22-($f*20)))->implode(' ') }}" />
                            <circle cx="70" cy="2" r="2" fill="{{ $kpi['color'] }}" />
                        </svg>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Next Goal --}}
    <div>
        <div class="section-head">
            <h2>الهدف القادم</h2>
            <span class="meta">رحلتك في الأندية</span>
        </div>
        @php
            $clubs = \App\Models\Club::where('is_active', true)->orderBy('club_order')->get();
            $nextClub = $agent->club
                ? $clubs->where('club_order', '>', $agent->club->club_order)->first()
                : $clubs->first();
        @endphp
        @if($agent->club && !$nextClub)
            <div class="next-goal" style="background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border:1px solid #fde68a;">
                <div class="next-goal-head">
                    <div class="next-goal-target">
                        <div class="next-goal-target-icon" style="background:var(--grad-gold);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
                        </div>
                        <div>
                            <div class="next-goal-eyebrow">الحالة</div>
                            <div class="next-goal-name">🏆 أنت في القمة</div>
                        </div>
                    </div>
                    <div class="next-goal-need">
                        <div class="next-goal-need-num">100%</div>
                        <div class="next-goal-need-label">أعلى نادٍ في الحملة</div>
                    </div>
                </div>
                <div class="progress-track"><div class="progress-fill" style="width:0%;transition:width 1s ease-out;" data-fill-width="100%"></div></div>
                <div class="progress-need">استمر في التميز للحفاظ على مكانتك ✨</div>
            </div>
        @else
            @php
                $agentIncrease = $agent->transfer_count + $agent->new_line_count;
                $needed = $nextClub ? max(0, $nextClub->required_increase - $agentIncrease) : 0;
                $current = $agentIncrease;
                $required = $nextClub ? $nextClub->required_increase : 1;
                $fillPct = min(100, round(($current / $required) * 100));
            @endphp
            <div class="next-goal">
                <div class="next-goal-head">
                    <div class="next-goal-target">
                        <div class="next-goal-target-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M6 2h12l4 7-10 13L2 9z" opacity=".9"/></svg>
                        </div>
                        <div>
                            <div class="next-goal-eyebrow">الهدف القادم</div>
                            <div class="next-goal-name">{{ $nextClub?->club_name ?? 'نادي البداية' }}</div>
                        </div>
                    </div>
                    <div class="next-goal-need">
                        <div class="next-goal-need-num" x-data="countUp({{ $needed }})" x-init="init()">
                            +<span x-text="formatted"></span>
                            <small>زيادة</small>
                        </div>
                        <div class="next-goal-need-label">للوصول 🚀</div>
                    </div>
                </div>
                <div style="position:relative;">
                    <div class="progress-track">
                        <div class="progress-fill" style="width:0%;transition:width 1s ease-out;" data-fill-width="{{ $fillPct }}%"></div>
                    </div>
                    <div class="milestones">
                        @foreach($clubs->take(5) as $i => $c)
                            @php
                                $maxR = $clubs->max('required_increase') ?: 1;
                                $pct = round(($c->required_increase / $maxR) * 100);
                                $passed = $agent->club && $agent->club->club_order >= $c->club_order;
                                $current = $agent->club && $agent->current_club_id === $c->club_id;
                            @endphp
                            <div class="milestone {{ $passed ? 'passed' : '' }} {{ $current ? 'current' : '' }}" style="right:{{ $pct }}%;">
                                <div class="milestone-dot"></div>
                                <div class="milestone-label">{{ $c->club_name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="progress-need">
                    تحتاج <strong style="color:var(--primary);font-weight:700;">+{{ $needed }} زيادة</strong> إضافية للوصول إلى {{ $nextClub?->club_name ?? 'أول نادٍ' }} 🚀
                </div>
            </div>
        @endif
    </div>

    {{-- Leaderboard + Extras grid --}}
    @php
        $rankRows = \App\Models\Agent::where('current_club_id', $agent->current_club_id)
            ->whereNotNull('current_club_id')
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('transfer_count + new_line_count'))
            ->take(5)
            ->get(['agent_id', 'agent_name', 'transfer_count', 'new_line_count']);
        $maxVal = $rankRows->max(fn($r) => $r->transfer_count + $r->new_line_count) ?: 1;
    @endphp
    <div class="block-grid cols-2">
        {{-- Leaderboard --}}
        <div class="card card-pad">
            <div class="section-head" style="margin:0 0 12px;">
                <div>
                    <h2>موقعك بين الوكلاء</h2>
                    <div class="card-subtitle">{{ $agent->club?->club_name ?? 'غير منتسب' }} · هذا الشهر</div>
                </div>
                <div class="meta">الأسماء مجهولة</div>
            </div>
            @forelse($rankRows as $i => $row)
                @php
                    $isYou = $row->agent_id === $agent->agent_id;
                    $rowIncrease = $row->transfer_count + $row->new_line_count;
                    $arabicLetters = ['أ','ب','ج','د','هـ'];
                @endphp
                <div class="lb-row {{ $isYou ? 'you' : '' }}">
                    <div class="lb-rank {{ $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($isYou ? 'you' : '')) }}">
                        {{ $isYou ? '★' : ($i + 1) }}
                    </div>
                    <div class="lb-name {{ $isYou ? 'you' : '' }}">
                        {{ $isYou ? $row->agent_name : ('وكيل ' . ($arabicLetters[$i] ?? ($i+1))) }}
                        <span class="agent-tag">AGT-{{ strtoupper(substr($row->agent_id, 0, 3)) }}</span>
                    </div>
                    <div class="lb-bar">
                        <div class="lb-bar-fill" style="width:0%;transition:width 1.2s ease-out;" data-fill-width="{{ round($rowIncrease / $maxVal * 100) }}%"></div>
                    </div>
                    <div class="lb-value">{{ number_format($rowIncrease) }}</div>
                </div>
            @empty
                <div class="empty">لا توجد بيانات بعد</div>
            @endforelse
        </div>

        {{-- Pressure + Best Day + Streak --}}
        <div style="display:flex;flex-direction:column;gap:14px;">
            {{-- Pressure meter --}}
            @php
                $nextTicketAt = 30;
                $currentTransfers = $agent->transfer_count % $nextTicketAt;
            @endphp
            <div class="pressure-card">
                <div class="pressure-icon">🎯</div>
                <div class="pressure-body">
                    <div class="pressure-title">كم تبقى للحصول على فرصة سحب إضافية؟</div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                        <div style="font-size:18px;font-weight:700;">{{ $currentTransfers }} / {{ $nextTicketAt }} تحويل</div>
                        <div style="font-size:11px;color:var(--slate-500);">+{{ $nextTicketAt - $currentTransfers }} تحويلات إضافية</div>
                    </div>
                    <div class="pressure-track">
                        <div class="pressure-fill" style="width:0%;transition:width 1s;" data-fill-width="{{ round($currentTransfers / $nextTicketAt * 100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Best Day --}}
            @php
                $bestSnap = $agent->dailySnapshots()
                    ->orderByRaw('(transfer_count + new_line_count) DESC')
                    ->first();
            @endphp
            @if($bestSnap)
            <div class="best-day">
                <div class="best-day-icon">📅</div>
                <div>
                    <div class="best-day-eyebrow">أفضل يوم في هذا الشهر</div>
                    <div class="best-day-line">
                        {{ $bestSnap->data_date->translatedFormat('l j M') }} · +{{ $bestSnap->transfer_count + $bestSnap->new_line_count }} خط 🔥 أداء استثنائي
                    </div>
                </div>
            </div>
            @endif

            {{-- Streak --}}
            @php
                $recentSnaps = $agent->dailySnapshots()
                    ->orderByDesc('data_date')
                    ->take(14)
                    ->get()
                    ->reverse()
                    ->values();
                $streak = 0;
                foreach ($recentSnaps->reverse() as $s) {
                    if (($s->transfer_count + $s->new_line_count) > 0) $streak++;
                    else break;
                }
            @endphp
            <div class="streak">
                <div class="streak-head">
                    <div class="streak-flame">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" style="color:white;"><path d="M12 2c1 4 4 5 4 9a4 4 0 0 1-8 0c0-1.5.5-2.5 1-3.5C9.5 9.5 11 7 12 2z"/><path d="M12 14a3 3 0 0 0-3 3c0 2 1.5 4 3 4s3-2 3-4a3 3 0 0 0-3-3z"/></svg>
                    </div>
                    <div class="streak-label"><strong>{{ $streak }} أيام متتالية</strong> بزيادة إيجابية</div>
                </div>
                <div class="streak-dots">
                    @foreach($recentSnaps as $i => $s)
                        @php $isToday = $i === $recentSnaps->count() - 1; $on = ($s->transfer_count + $s->new_line_count) > 0; @endphp
                        <div class="dot {{ $isToday ? 'today' : ($on ? 'on' : '') }}"></div>
                    @endforeach
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:var(--slate-500);">
                    <span>قبل أسبوعين</span><span>اليوم</span>
                </div>
            </div>
        </div>
    </div>
</div>
