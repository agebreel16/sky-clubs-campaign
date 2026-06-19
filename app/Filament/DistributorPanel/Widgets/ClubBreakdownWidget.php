<?php

namespace App\Filament\DistributorPanel\Widgets;

use App\Models\Agent;
use App\Models\Club;
use Filament\Widgets\Widget;

class ClubBreakdownWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.distributor.widgets.club-breakdown-widget';

    protected function getViewData(): array
    {
        $distributorId = auth('distributor')->id();
        $clubs         = Club::orderBy('club_order')->get();

        // query واحدة: أعداد وكلاء الموزع لكل نادٍ + first arrivals
        $distGroups = Agent::where('distributor_id', $distributorId)
            ->where('is_violator', false)
            ->whereNotNull('current_club_id')
            ->selectRaw('current_club_id, COUNT(*) as total, SUM(is_first_arrival) as first_arrivals')
            ->groupBy('current_club_id')
            ->get()
            ->keyBy('current_club_id');

        // query واحدة: الأعداد الكلية لكل نادٍ (كل الموزعين)
        $globalGroups = Agent::whereNotNull('current_club_id')
            ->where('is_violator', false)
            ->selectRaw('current_club_id, COUNT(*) as total')
            ->groupBy('current_club_id')
            ->get()
            ->keyBy('current_club_id');

        $mappedClubs = $clubs->map(function (Club $club) use ($distributorId, $distGroups, $globalGroups) {
            $myCount       = (int) ($distGroups[$club->club_id]->total          ?? 0);
            $firstArrivals = (int) ($distGroups[$club->club_id]->first_arrivals ?? 0);
            $totalInClub   = (int) ($globalGroups[$club->club_id]->total        ?? 0);

            // query لكل نادٍ (3 queries بدلاً من 12 — أفضل من تحميل كل الوكلاء)
            $latestMember = Agent::where('distributor_id', $distributorId)
                ->where('current_club_id', $club->club_id)
                ->where('is_violator', false)
                ->orderByDesc('entry_date')
                ->select(['agent_id', 'agent_name', 'entry_date'])
                ->first();

            $myPercentage = $club->seat_capacity > 0
                ? round(($myCount / $club->seat_capacity) * 100)
                : 0;

            return [
                'club'          => $club,
                'myCount'       => $myCount,
                'firstArrivals' => $firstArrivals,
                'latestMember'  => $latestMember,
                'totalInClub'   => $totalInClub,
                'myPercentage'  => $myPercentage,
            ];
        });

        return ['clubs' => $mappedClubs];
    }
}
