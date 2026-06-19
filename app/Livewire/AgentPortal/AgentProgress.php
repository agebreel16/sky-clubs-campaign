<?php

namespace App\Livewire\AgentPortal;

class AgentProgress extends AgentPortalPage
{
    public array $daily   = [];
    public array $weekly  = [];
    public array $monthly = [];

    public function mount(string $uuid): void
    {
        parent::mount($uuid);
        $this->buildChartDatasets();
    }

    private function buildChartDatasets(): void
    {
        $snapshots = $this->agent->dailySnapshots()
            ->orderBy('data_date')
            ->get(['data_date', 'transfer_count', 'new_line_count']);

        $dailyLabels = [];
        $dailyData   = [];
        $weeklyMap   = [];
        $monthlyMap  = [];

        foreach ($snapshots as $snap) {
            $increase = $snap->transfer_count + $snap->new_line_count;

            $dailyLabels[] = $snap->data_date->format('d/m');
            $dailyData[]   = $increase;

            $week = $snap->data_date->format('Y-W');
            $weeklyMap[$week] = ['label' => 'أسبوع ' . $snap->data_date->format('W'), 'value' => $increase];

            $month = $snap->data_date->format('Y-m');
            $monthlyMap[$month] = ['label' => $snap->data_date->translatedFormat('M Y'), 'value' => $increase];
        }

        $this->daily   = ['labels' => $dailyLabels, 'data' => $dailyData];
        $this->weekly  = [
            'labels' => array_column(array_values($weeklyMap), 'label'),
            'data'   => array_column(array_values($weeklyMap), 'value'),
        ];
        $this->monthly = [
            'labels' => array_column(array_values($monthlyMap), 'label'),
            'data'   => array_column(array_values($monthlyMap), 'value'),
        ];
    }

    public function render()
    {
        return $this->renderWithLayout('livewire.agent-portal.progress');
    }
}
