<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\ClubChangeRequest;
use App\Models\HistoryLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayActivityWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'نشاط اليوم';

    /**
     * Hidden from dashboard — data is covered by CampaignStatsOverview.
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        $promotions = HistoryLog::where('event_type', 'promotion')
            ->where('event_timestamp', '>=', $today)
            ->count();

        $demotions = HistoryLog::where('event_type', 'demotion')
            ->where('event_timestamp', '>=', $today)
            ->count();

        $firstArrivals = Agent::where('is_first_arrival', true)
            ->where('created_at', '>=', $today)
            ->count();

        $pendingToday = ClubChangeRequest::whereDate('created_at', today())
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('ترقيات ⬆️', $promotions)
                ->description('وكلاء انتقلوا لنادٍ أعلى')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('تهبيطات ⬇️', $demotions)
                ->description('وكلاء هبطوا لنادٍ أدنى')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('أوائل جدد ⭐', $firstArrivals)
                ->description('أوائل الداخلين لنادٍ')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('طلبات جديدة اليوم', $pendingToday)
                ->description('طلبات تغيير النادي المعلّقة اليوم')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingToday > 0 ? 'warning' : 'gray'),
        ];
    }
}
