<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use Filament\Widgets\Widget;

class AgentsStatsWidget extends Widget
{
    /**
     * The view to render — non-static in Filament v5.
     */
    protected string $view = 'filament.widgets.agents-stats-widget';

    /**
     * Don't lazy-load: stats appear immediately with the page.
     */
    protected static bool $isLazy = false;

    /**
     * Full-width across the page header area.
     */
    protected int | string | array $columnSpan = 'full';

    /**
     * Exclude from auto-discovered dashboard widgets.
     * This widget is used only as a header widget in AgentResource/ListAgents.
     */
    public static function canView(): bool
    {
        return false;
    }

    public function getViewData(): array
    {
        return [
            'total'        => Agent::count(),
            'inClubs'      => Agent::whereNotNull('current_club_id')->count(),
            'violators'    => Agent::where('is_violator', true)->count(),
            'firstArrival' => Agent::where('is_first_arrival', true)->count(),
        ];
    }
}
