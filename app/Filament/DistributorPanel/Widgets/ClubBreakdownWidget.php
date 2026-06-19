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

        $clubs = Club::orderBy('club_order')->get()->map(function (Club $club) use ($distributorId) {
            $myCount = Agent::where('distributor_id', $distributorId)
                ->where('current_club_id', $club->club_id)
                ->where('is_violator', false)
                ->count();

            $firstArrivals = Agent::where('distributor_id', $distributorId)
                ->where('current_club_id', $club->club_id)
                ->where('is_first_arrival', true)
                ->where('is_violator', false)
                ->count();

            $latestMember = Agent::where('distributor_id', $distributorId)
                ->where('current_club_id', $club->club_id)
                ->where('is_violator', false)
                ->orderByDesc('entry_date')
                ->first();

            $totalInClub = Agent::where('current_club_id', $club->club_id)->where('is_violator', false)->count();

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

        return ['clubs' => $clubs];
    }
}
