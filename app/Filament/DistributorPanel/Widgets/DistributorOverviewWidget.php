<?php

namespace App\Filament\DistributorPanel\Widgets;

use App\Models\Agent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DistributorOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'نظرة عامة على مجموعتك';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $distributorId = auth('distributor')->id();

        $total      = Agent::where('distributor_id', $distributorId)->count();
        $inClubs    = Agent::where('distributor_id', $distributorId)->whereNotNull('current_club_id')->count();
        $outside    = $total - $inClubs;
        $violators  = Agent::where('distributor_id', $distributorId)->where('is_violator', true)->count();
        $firstArrivals = Agent::where('distributor_id', $distributorId)->where('is_first_arrival', true)->count();

        $totalIncrease = Agent::where('distributor_id', $distributorId)
            ->selectRaw('SUM(transfer_count + new_line_count) as total_increase')
            ->value('total_increase') ?? 0;

        $totalRewards = DB::table('rewards')
            ->join('agents', 'agents.agent_id', '=', 'rewards.agent_id')
            ->where('agents.distributor_id', $distributorId)
            ->sum('rewards.amount');

        return [
            Stat::make('إجمالي وكلائي', number_format($total))
                ->description('المسجلون في حسابك')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([max(0, $total - 3), max(0, $total - 2), max(0, $total - 1), $total]),

            Stat::make('داخل الأندية', number_format($inClubs))
                ->description("{$outside} خارج الأندية")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('المخالفون', number_format($violators))
                ->description($violators > 0 ? 'وكلاء مصنّفون كمخالفين' : 'لا يوجد مخالفون')
                ->descriptionIcon($violators > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($violators > 0 ? 'danger' : 'success'),

            Stat::make('الأوائل', number_format($firstArrivals))
                ->description('حصلوا على مكافأة الأوائل')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('إجمالي الزيادة', number_format((int) $totalIncrease) . ' رقم')
                ->description('مجموع زيادات كل وكلائك')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('إجمالي المكافآت', number_format((float) $totalRewards, 0) . ' ₪')
                ->description('مجموع مكافآت مجموعتك')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
