<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\ClubChangeRequest;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class CampaignStatsOverview extends Widget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.campaign-stats-overview';

    public function getViewData(): array
    {
        $totalAgents   = Agent::count();
        $agentsInClubs = Agent::whereNotNull('current_club_id')->count();
        $agentsOut     = $totalAgents - $agentsInClubs;
        $violators     = Agent::where('is_violator', true)->count();
        $pendingCount  = ClubChangeRequest::where('status', 'pending')->count();

        $campaignStart = Carbon::create(2026, 5, 1);
        $campaignEnd   = Carbon::create(2027, 4, 30);
        $today         = now();

        $daysRemaining = max(0, (int) $today->diffInDays($campaignEnd, false));
        $totalDays     = (int) $campaignStart->diffInDays($campaignEnd);
        $daysPassed    = max(0, min((int) $campaignStart->diffInDays($today, false), $totalDays));
        $progress      = $totalDays > 0 ? round(($daysPassed / $totalDays) * 100) : 0;

        return compact(
            'totalAgents', 'agentsInClubs', 'agentsOut',
            'violators', 'pendingCount', 'daysRemaining', 'progress',
        );
    }
}
