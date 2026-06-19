<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Chart Card --}}
    <div class="chart-card">
        <div class="chart-head">
            <div>
                <div style="font-size:11px;letter-spacing:.15em;color:var(--slate-500);text-transform:uppercase;">مخطط الأداء</div>
                <div style="font-size:20px;font-weight:700;color:var(--slate-900);margin-top:2px;">رحلة الزيادة عبر الزمن</div>
            </div>
            <div class="chart-toggle" x-data="{ period: 'daily' }">
                <button :class="period==='daily'?'active':''" @click="period='daily'; switchChart('daily')">يومي</button>
                <button :class="period==='weekly'?'active':''" @click="period='weekly'; switchChart('weekly')">أسبوعي</button>
                <button :class="period==='monthly'?'active':''" @click="period='monthly'; switchChart('monthly')">شهري</button>
            </div>
        </div>

        {{-- Summary stats --}}
        @php
            $maxIncrease = collect($daily['data'] ?? [])->max() ?: 0;
            $totalInClub = \App\Models\Agent::where('current_club_id', $agent->current_club_id)->whereNotNull('current_club_id')->count();
            $rankInClub = \App\Models\Agent::where('current_club_id', $agent->current_club_id)
                ->whereNotNull('current_club_id')
                ->orderByDesc(\Illuminate\Support\Facades\DB::raw('transfer_count + new_line_count'))
                ->pluck('agent_id')
                ->search($agent->agent_id);
            $rankInClub = $rankInClub !== false ? ($rankInClub + 1) : '—';
        @endphp
        <div class="chart-summary">
            <div class="chart-stat">
                <div class="chart-stat-val">+{{ $maxIncrease }}</div>
                <div class="chart-stat-label">أعلى يوم في السجل</div>
            </div>
            <div class="chart-stat">
                <div class="chart-stat-val">{{ $agent->transfer_percentage }}%</div>
                <div class="chart-stat-label">معدل التحويل</div>
            </div>
            <div class="chart-stat">
                <div class="chart-stat-val">#{{ $rankInClub }}</div>
                <div class="chart-stat-label">ترتيبك في النادي</div>
            </div>
        </div>

        @if(count($daily['labels'] ?? []) > 0)
            {{-- wire:ignore prevents Livewire from clearing the canvas --}}
            <div wire:ignore>
                <div class="chart-area" id="chartArea" style="position:relative;overflow:visible;">
                    <canvas id="progressChart" style="max-height:300px;width:100%;"></canvas>
                    <div id="chartTooltip" style="display:none;position:absolute;background:white;border-radius:10px;padding:8px 14px;box-shadow:0 8px 24px rgba(0,0,0,.12);font-size:13px;pointer-events:none;white-space:nowrap;z-index:10;"></div>
                </div>
            </div>

            <div style="display:flex;gap:18px;margin-top:18px;font-size:12px;color:var(--slate-500);flex-wrap:wrap;">
                <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span style="width:14px;height:3px;background:var(--primary);border-radius:2px;display:inline-block;"></span>الزيادة
                </span>
                <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span style="width:14px;height:2px;background:var(--purple);border-radius:2px;display:inline-block;"></span>نسبة التحويل %
                </span>
                <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span style="width:14px;height:2px;background:var(--slate-300);border-radius:2px;display:inline-block;"></span>الهدف
                </span>
            </div>

            <script>
            const chartDatasets = {
                daily:   @json($daily),
                weekly:  @json($weekly),
                monthly: @json($monthly),
            };

            let progressChart;

            document.addEventListener('DOMContentLoaded', function () {
                if (!document.getElementById('progressChart')) return;
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js';
                script.onload = function() { initChart('daily'); };
                document.head.appendChild(script);
            });

            function initChart(period) {
                const ctx = document.getElementById('progressChart');
                if (!ctx) return;
                if (progressChart) progressChart.destroy();
                const d = chartDatasets[period];
                if (!d || !d.labels || !d.labels.length) return;
                progressChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: d.labels,
                        datasets: [{
                            label: 'الزيادة',
                            data: d.data,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14,165,233,.12)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 5,
                            pointBackgroundColor: '#0ea5e9',
                            borderWidth: 2.5,
                        }]
                    },
                    options: {
                        responsive: true,
                        animation: { duration: 900 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                rtl: true,
                                bodyFont: { family: 'Alexandria' },
                                titleFont: { family: 'Alexandria' },
                                callbacks: {
                                    label: (ctx) => ' ' + ctx.parsed.y.toLocaleString('en-US'),
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Alexandria' }, callback: (v) => v.toLocaleString('en-US') } },
                            x: { ticks: { font: { family: 'Alexandria' } } }
                        }
                    }
                });
            }

            function switchChart(period) {
                initChart(period);
            }
            </script>
        @else
            <div style="text-align:center;padding:60px;color:var(--slate-400);">
                <div style="font-size:48px;margin-bottom:12px;">📊</div>
                <div>لا توجد بيانات أداء بعد</div>
            </div>
        @endif
    </div>

    {{-- Comparison Card --}}
    @php
        $currentMonth = \App\Models\DailySnapshot::where('agent_id', $agent->agent_id)
            ->whereYear('data_date', now()->year)
            ->whereMonth('data_date', now()->month)
            ->get();
        $prevMonth = \App\Models\DailySnapshot::where('agent_id', $agent->agent_id)
            ->whereYear('data_date', now()->subMonth()->year)
            ->whereMonth('data_date', now()->subMonth()->month)
            ->get();

        $curIncrease = $currentMonth->sum(fn($s) => $s->transfer_count + $s->new_line_count);
        $prevIncrease = $prevMonth->sum(fn($s) => $s->transfer_count + $s->new_line_count);
        $curTransfer = $currentMonth->avg(fn($s) => $s->transfer_count) ? round($currentMonth->sum('transfer_count') / max(1, $currentMonth->count()), 1) : 0;
        $prevTransfer = $prevMonth->count() ? round($prevMonth->sum('transfer_count') / max(1, $prevMonth->count()), 1) : 0;
        $increaseChange = $prevIncrease > 0 ? round((($curIncrease - $prevIncrease) / $prevIncrease) * 100, 1) : 0;
    @endphp
    <div class="card card-pad">
        <div class="section-head" style="margin:0 0 4px;">
            <div>
                <h2>مقارنة الأداء</h2>
                <div class="card-subtitle">هذا الشهر مقابل الشهر الماضي</div>
            </div>
        </div>
        @php
            $rows = [
                ['label' => 'زيادة الحملة',  'old' => $prevIncrease, 'new' => $curIncrease,  'delta' => ($increaseChange >= 0 ? '+' : '').$increaseChange.'%', 'up' => $curIncrease >= $prevIncrease],
                ['label' => 'نسبة التحويل', 'old' => $prevTransfer, 'new' => $curTransfer, 'delta' => ($curTransfer >= $prevTransfer ? '+' : '').round($curTransfer - $prevTransfer, 1).' pts', 'up' => $curTransfer >= $prevTransfer],
                ['label' => 'خطوط مفقودة', 'old' => abs($agent->baseline_loss), 'new' => abs($agent->baseline_loss), 'delta' => '—', 'up' => true],
            ];
        @endphp
        @foreach($rows as $r)
        <div class="compare-row">
            <div class="compare-label">{{ $r['label'] }}</div>
            <div class="compare-numbers">
                <span class="compare-old">{{ $r['old'] }}</span>
                <span class="compare-arrow">←</span>
                <span class="compare-new">{{ $r['new'] }}</span>
            </div>
            <span class="compare-pill {{ $r['up'] ? 'up' : 'down' }}">
                {{ $r['up'] ? '↑' : '↓' }} {{ $r['delta'] }}
            </span>
        </div>
        @endforeach
    </div>

</div>
