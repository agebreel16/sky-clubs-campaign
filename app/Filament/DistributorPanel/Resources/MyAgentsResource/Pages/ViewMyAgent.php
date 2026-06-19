<?php

namespace App\Filament\DistributorPanel\Resources\MyAgentsResource\Pages;

use App\Filament\DistributorPanel\Resources\MyAgentsResource;
use App\Models\Agent;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewMyAgent extends ViewRecord
{
    protected static string $resource = MyAgentsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            // ── بيانات الوكيل + حالة النادي ──────────────────────────────────
            Grid::make(2)->schema([

                Section::make('بيانات الوكيل')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('primary')
                    ->schema([
                        TextEntry::make('agent_name')
                            ->label('الاسم')
                            ->size('lg')
                            ->weight('bold'),

                        TextEntry::make('phone')
                            ->label('رقم الجوال')
                            ->icon('heroicon-m-phone')
                            ->default('—'),

                        TextEntry::make('created_at')
                            ->label('تاريخ التسجيل')
                            ->dateTime('d/m/Y')
                            ->icon('heroicon-m-calendar-days'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-m-arrow-path'),
                    ]),

                Section::make('حالة النادي')
                    ->icon('heroicon-o-trophy')
                    ->iconColor('warning')
                    ->visible(fn (Agent $record) => $record->current_club_id !== null)
                    ->schema([
                        TextEntry::make('club.club_name')
                            ->label('النادي الحالي')
                            ->badge()
                            ->size('lg')
                            ->weight('bold')
                            ->color(fn (Agent $record): string => match ((int) ($record->club?->club_order ?? 0)) {
                                1       => 'success',
                                2       => 'info',
                                3       => 'warning',
                                default => 'gray',
                            }),

                        Grid::make(2)->schema([
                            TextEntry::make('entry_date')
                                ->label('تاريخ الدخول')
                                ->dateTime('d/m/Y')
                                ->icon('heroicon-m-arrow-right-circle'),

                            TextEntry::make('days_in_club')
                                ->label('المدة في النادي')
                                ->getStateUsing(fn (Agent $record): string => !$record->entry_date
                                    ? '—'
                                    : (int) $record->entry_date->diffInDays(now()) . ' يوم')
                                ->icon('heroicon-m-clock'),

                            TextEntry::make('club_rank')
                                ->label('الترتيب في النادي')
                                ->getStateUsing(function (Agent $record): string {
                                    if (!$record->current_club_id) return '—';
                                    $rank  = Agent::where('current_club_id', $record->current_club_id)
                                        ->where('current_total', '>', $record->current_total)
                                        ->count() + 1;
                                    $total = Agent::where('current_club_id', $record->current_club_id)->count();
                                    return "#{$rank} من {$total}";
                                })
                                ->badge()
                                ->color('primary'),

                            IconEntry::make('is_first_arrival')
                                ->label('من الأوائل')
                                ->boolean()
                                ->trueIcon('heroicon-o-star')
                                ->trueColor('warning')
                                ->falseIcon('heroicon-o-minus-circle')
                                ->falseColor('gray'),
                        ]),
                    ]),
            ]),

            // ── الأرقام والإحصائيات ────────────────────────────────────────────
            Section::make('الأرقام والإحصائيات')
                ->icon('heroicon-o-chart-bar-square')
                ->iconColor('info')
                ->columns(4)
                ->schema([
                    TextEntry::make('baseline_count')
                        ->label('الأساس المجمّد')
                        ->size('lg')
                        ->weight('bold')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('pre_campaign_count')
                        ->label('الأرقام القديمة')
                        ->size('lg')
                        ->weight('bold')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('current_total')
                        ->label('الإجمالي الحالي')
                        ->size('lg')
                        ->weight('bold')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('campaign_increase_calc')
                        ->label('الزيادة في الحملة')
                        ->size('lg')
                        ->weight('bold')
                        ->getStateUsing(fn (Agent $record): int => $record->transfer_count + $record->new_line_count)
                        ->badge()
                        ->color('success'),

                    TextEntry::make('transfer_count')
                        ->label('التحويلات')
                        ->size('lg')
                        ->weight('bold')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('new_line_count')
                        ->label('الخطوط الجديدة')
                        ->size('lg')
                        ->weight('bold')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('transfer_pct_calc')
                        ->label('نسبة التحويل')
                        ->size('lg')
                        ->weight('bold')
                        ->getStateUsing(function (Agent $record): string {
                            if (!$record->club) return '—';
                            $req = (int) $record->club->required_increase;
                            if ($req === 0) return '0%';
                            return round(($record->transfer_count / $req) * 100, 1) . '%';
                        })
                        ->badge()
                        ->color(function (Agent $record): string {
                            if (!$record->club) return 'gray';
                            $req = (int) $record->club->required_increase;
                            if ($req === 0) return 'gray';
                            return ($record->transfer_count / $req) * 100 >= 60 ? 'success' : 'danger';
                        }),

                    TextEntry::make('baseline_loss_calc')
                        ->label('الأرقام المفقودة')
                        ->size('lg')
                        ->weight('bold')
                        ->getStateUsing(fn (Agent $record): int => $record->baseline_count - $record->pre_campaign_count)
                        ->badge()
                        ->color(fn (Agent $record): string => ($record->baseline_count - $record->pre_campaign_count) > 0 ? 'danger' : 'gray'),
                ]),

            // ── تنبيه المخالف ─────────────────────────────────────────────────
            Section::make('تنبيه')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->visible(fn (Agent $record) => $record->is_violator)
                ->schema([
                    TextEntry::make('violator_notice')
                        ->label('')
                        ->getStateUsing(fn () => 'هذا الوكيل مصنّف ضمن المخالفين من قِبَل الإدارة. للاستفسار تواصل مع مشرفك المباشر.')
                        ->columnSpanFull(),
                ]),

            // ── المكافآت ─────────────────────────────────────────────────────
            Section::make('المكافآت')
                ->icon('heroicon-o-banknotes')
                ->iconColor('success')
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('rewards')
                        ->label('')
                        ->schema([
                            TextEntry::make('club.club_name')
                                ->label('النادي')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('amount')
                                ->label('المبلغ')
                                ->suffix(' ₪')
                                ->weight('bold'),

                            IconEntry::make('is_first_arrival')
                                ->label('أوائل')
                                ->boolean()
                                ->trueIcon('heroicon-o-star')
                                ->trueColor('warning'),

                            TextEntry::make('payment_status')
                                ->label('الحالة')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'paid'    => 'success',
                                    'pending' => 'warning',
                                    'failed'  => 'danger',
                                    default   => 'gray',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'paid'    => 'مدفوعة',
                                    'pending' => 'قيد الانتظار',
                                    'failed'  => 'فشل الدفع',
                                    default   => $state,
                                }),
                        ])
                        ->columns(4)
                        ->placeholder('لا توجد مكافآت بعد'),
                ])
                ->visible(fn (Agent $record): bool => $record->rewards()->exists()),

            // ── فرص اليانصيب ──────────────────────────────────────────────────
            Section::make('فرص اليانصيب')
                ->icon('heroicon-o-ticket')
                ->iconColor('warning')
                ->columns(3)
                ->schema([
                    TextEntry::make('total_opportunities')
                        ->label('إجمالي الفرص')
                        ->getStateUsing(fn (Agent $record): int => $record->opportunities()->count())
                        ->badge()
                        ->color('info'),

                    TextEntry::make('active_opportunities')
                        ->label('الفرص النشطة')
                        ->getStateUsing(fn (Agent $record): int => $record->opportunities()->where('is_active', true)->count())
                        ->badge()
                        ->color('success'),

                    TextEntry::make('entry_opportunities')
                        ->label('فرص الدخول')
                        ->getStateUsing(fn (Agent $record): int => $record->opportunities()->where('type', 'entry')->where('is_active', true)->count())
                        ->badge()
                        ->color('primary'),
                ])
                ->visible(fn (Agent $record): bool => $record->opportunities()->exists()),
        ]);
    }
}
