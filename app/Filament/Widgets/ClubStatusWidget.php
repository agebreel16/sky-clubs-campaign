<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\Club;
use Filament\Widgets\Widget;

class ClubStatusWidget extends Widget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.club-status-widget';

    protected function getViewData(): array
    {
        $clubs = Club::orderBy('club_order')->get()->map(function (Club $club) {
            $membersCount = Agent::where('current_club_id', $club->club_id)
                ->where('is_violator', false)
                ->count();
            $percentage   = $club->seat_capacity > 0
                ? round(($membersCount / $club->seat_capacity) * 100)
                : 0;

            $latestMember = Agent::where('current_club_id', $club->club_id)
                ->where('is_violator', false)
                ->orderByDesc('entry_date')
                ->first();

            // Color CSS variable based on club order (design system tokens)
            [$colorVar, $glowColorVar, $gradientCss] = match ((int) $club->club_order) {
                1 => [
                    '--sc-accent',
                    '--sc-accent-glow',
                    'linear-gradient(135deg, oklch(0.60 0.22 245), oklch(0.50 0.22 270))',
                ],
                2 => [
                    '--sc-gold',
                    '--sc-gold-glow',
                    'linear-gradient(135deg, oklch(0.78 0.15 82), oklch(0.68 0.15 82))',
                ],
                3 => [
                    '--sc-purple',
                    '--sc-purple-glow',
                    'linear-gradient(135deg, oklch(0.62 0.20 295), oklch(0.72 0.20 295))',
                ],
                default => [
                    '--sc-text2',
                    '--sc-border',
                    'linear-gradient(135deg, #4b5563, #9ca3af)',
                ],
            };

            return [
                'club'          => $club,
                'membersCount'  => $membersCount,
                'percentage'    => $percentage,
                'latestMember'  => $latestMember,
                'colorVar'      => $colorVar,
                'glowColorVar'  => $glowColorVar,
                'gradientCss'   => $gradientCss,
                'isFull'        => $membersCount >= $club->seat_capacity,
            ];
        });

        return ['clubs' => $clubs];
    }
}
