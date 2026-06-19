<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Exports\AgentsExport;
use App\Exports\ApproachingAgentsExport;
use App\Filament\Resources\AgentResource;
use App\Filament\Widgets\AgentsStatsWidget;
use App\Models\Agent;
use App\Models\Club;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListAgents extends ListRecords
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة وكيل')
                ->icon('heroicon-o-plus'),
            Action::make('export_agents')
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $agents = Agent::with(['club', 'distributor'])->orderBy('agent_name')->get();
                    return app(AgentsExport::class)->download($agents);
                }),
            Action::make('export_approaching')
                ->label('وكلاء قريبون من نادي الانطلاق')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('warning')
                ->action(function () {
                    $antelaaq     = Club::where('club_order', 1)->where('is_active', true)->first();
                    $halfIncrease = (int) ceil(($antelaaq?->required_increase ?? 25) / 2);
                    $halfTransfer = (int) ceil(($antelaaq?->required_transfer_count ?? 15) / 2);

                    $agents = Agent::with('distributor')
                        ->whereNull('current_club_id')
                        ->where(function (Builder $q) use ($halfIncrease, $halfTransfer) {
                            $q->whereRaw('(new_line_count + transfer_count) >= ?', [$halfIncrease])
                              ->orWhere('transfer_count', '>=', $halfTransfer);
                        })
                        ->orderByRaw('(new_line_count + transfer_count) DESC')
                        ->get();

                    return app(ApproachingAgentsExport::class)->download($agents, $antelaaq);
                }),
        ];
    }

    /**
     * Stats widget shown above the table (4 KPI cards from the design).
     */
    protected function getHeaderWidgets(): array
    {
        return [
            AgentsStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $clubs = Club::where('is_active', true)
            ->orderBy('club_order')
            ->get(['club_id', 'club_name', 'club_order']);

        // استعلامان بدلاً من N+1: واحد للإجماليات وواحد مجمَّع للأندية
        $totals = DB::table('agents')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_violator = 1 THEN 1 ELSE 0 END) as violators,
                SUM(CASE WHEN current_club_id IS NULL AND is_violator = 0 THEN 1 ELSE 0 END) as outside
            ')
            ->first();

        $clubCounts = DB::table('agents')
            ->selectRaw('current_club_id, COUNT(*) as cnt')
            ->where('is_violator', false)
            ->whereIn('current_club_id', $clubs->pluck('club_id')->toArray())
            ->groupBy('current_club_id')
            ->pluck('cnt', 'current_club_id');

        $tabs = [
            'all' => Tab::make('الكل')
                ->badge((int) $totals->total)
                ->badgeColor('primary'),
        ];

        foreach ($clubs as $club) {
            $badgeColor = match ((int) $club->club_order) {
                1       => 'info',
                2       => 'warning',
                3       => 'primary',
                default => 'gray',
            };

            $tabs[(string) $club->club_id] = Tab::make($club->club_name)
                ->badge((int) ($clubCounts[$club->club_id] ?? 0))
                ->badgeColor($badgeColor)
                ->modifyQueryUsing(
                    fn (Builder $query) => $query->where('current_club_id', $club->club_id)->where('is_violator', false)
                );
        }

        $outsideCount = (int) $totals->outside;
        if ($outsideCount > 0) {
            $tabs['outside'] = Tab::make('خارج الأندية')
                ->badge($outsideCount)
                ->badgeColor('gray')
                ->modifyQueryUsing(
                    fn (Builder $query) => $query->whereNull('current_club_id')->where('is_violator', false)
                );
        }

        $violatorsCount = (int) $totals->violators;
        if ($violatorsCount > 0) {
            $tabs['violators'] = Tab::make('المخالفون')
                ->badge($violatorsCount)
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn (Builder $query) => $query->where('is_violator', true)
                );
        }

        return $tabs;
    }
}
