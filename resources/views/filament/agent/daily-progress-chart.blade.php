@php
    $state = $getState();
    $id    = $getId();
@endphp

@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
@endonce

<div wire:ignore class="p-2">
    <div id="chart-btns-{{ $id }}" style="display:flex;gap:8px;margin-bottom:12px;justify-content:flex-end;">
        <button onclick="switchAgentChart('{{ $id }}', 'daily', this)"
            data-period="daily"
            style="background:#2563eb;color:#fff;padding:4px 14px;border-radius:6px;font-size:0.8rem;font-weight:600;border:none;cursor:pointer;transition:all .15s;">يومي</button>
        <button onclick="switchAgentChart('{{ $id }}', 'weekly', this)"
            data-period="weekly"
            style="background:#f3f4f6;color:#4b5563;padding:4px 14px;border-radius:6px;font-size:0.8rem;font-weight:600;border:none;cursor:pointer;transition:all .15s;">أسبوعي</button>
        <button onclick="switchAgentChart('{{ $id }}', 'monthly', this)"
            data-period="monthly"
            style="background:#f3f4f6;color:#4b5563;padding:4px 14px;border-radius:6px;font-size:0.8rem;font-weight:600;border:none;cursor:pointer;transition:all .15s;">شهري</button>
    </div>
    <canvas id="chart-canvas-{{ $id }}" style="max-height:280px"></canvas>
</div>

@script
<script>
(function () {
    const id     = '{{ $id }}';
    const sets   = @json($state);
    const canvas = document.getElementById('chart-canvas-' + id);

    if (!canvas || typeof Chart === 'undefined') return;

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: sets.daily.labels,
            datasets: [{
                data: sets.daily.data,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => '+' + item.parsed.y + ' خط منذ بداية الحملة'
                    }
                }
            },
            scales: {
                x: { ticks: { maxTicksLimit: 15 } },
                y: { beginAtZero: true, ticks: { stepSize: 5 } }
            }
        }
    });

    window.__agentCharts       = window.__agentCharts || {};
    window.__agentChartSets    = window.__agentChartSets || {};
    window.__agentCharts[id]   = chart;
    window.__agentChartSets[id] = sets;

    window.switchAgentChart = function (chartId, period, clickedBtn) {
        const ch = window.__agentCharts[chartId];
        const st = window.__agentChartSets[chartId];
        if (!ch || !st) return;

        ch.data.labels = st[period].labels;
        ch.data.datasets[0].data = st[period].data;
        ch.update();

        clickedBtn.parentElement.querySelectorAll('button').forEach(function (btn) {
            const active = btn.dataset.period === period;
            btn.style.background = active ? '#2563eb' : '#f3f4f6';
            btn.style.color      = active ? '#fff'    : '#4b5563';
        });
    };
})();
</script>
@endscript
