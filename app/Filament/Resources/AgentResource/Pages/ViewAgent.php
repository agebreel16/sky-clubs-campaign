<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\Agent;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAgent extends ViewRecord
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            DeleteAction::make()->label('حذف'),

            // Generate token if missing, then show copyable URL in modal
            Action::make('share_portal_link')
                ->label('شارك الرابط')
                ->icon('heroicon-o-share')
                ->color('success')
                ->mountUsing(function (Agent $record) {
                    if (!$record->portal_token) {
                        $record->generatePortalToken();
                    }
                })
                ->modalHeading('رابط بوابة الوكيل')
                ->modalContent(fn (Agent $record) => view('filament.agent.portal-link-modal', [
                    'url' => $record->fresh()->getPortalUrl(),
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق'),

            // Invalidate old token and generate a new one
            Action::make('regenerate_portal_link')
                ->label('تجديد الرابط')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('تجديد رابط البوابة')
                ->modalDescription('سيُبطَل الرابط القديم فوراً. الوكيل لن يتمكن من الدخول بالرابط السابق.')
                ->action(function (Agent $record) {
                    $record->generatePortalToken();
                    Notification::make()->title('تم تجديد الرابط بنجاح')->success()->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            // ── Row 1: Identity + Club Side by Side ───────────────────────────
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

                        TextEntry::make('agent_id')
                            ->label('المعرّف')
                            ->copyable()
                            ->fontFamily('mono')
                            ->color('gray'),

                        TextEntry::make('created_at')
                            ->label('تاريخ التسجيل')
                            ->dateTime('d/m/Y H:i')
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
                                ->label('الترتيب')
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
                                ->falseIcon('heroicon-o-minus-circle')
                                ->trueColor('warning')
                                ->falseColor('gray'),
                        ]),
                    ]),

            ]),

            // ── Row 2: KPI Stats Grid ──────────────────────────────────────────
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
                        ->label('الخطوط القديمة المتبقية')
                        ->size('lg')
                        ->weight('bold')
                        ->badge()
                        ->color('gray')
                        ->helperText(fn (Agent $record) => 'من أصل ' . $record->baseline_count . ' خط'),

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
                        ->getStateUsing(fn (Agent $record) => $record->transfer_count + $record->new_line_count)
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
                        ->label('الخطوط القديمة المفقودة')
                        ->size('lg')
                        ->weight('bold')
                        ->getStateUsing(fn (Agent $record) => $record->baseline_count - $record->pre_campaign_count)
                        ->suffix(' خط')
                        ->badge()
                        ->color(fn (Agent $record) => ($record->baseline_count - $record->pre_campaign_count) > 0 ? 'danger' : 'success'),
                ]),

            // ── Row 3: Violator Warning ───────────────────────────────────────
            Section::make('تحذير — هذا الوكيل مصنّف ضمن المخالفين')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->visible(fn (Agent $record) => $record->is_violator)
                ->columns(2)
                ->schema([
                    TextEntry::make('violator_since')
                        ->label('تاريخ التصنيف')
                        ->dateTime('d/m/Y')
                        ->icon('heroicon-m-clock')
                        ->badge()
                        ->color('danger'),

                    TextEntry::make('violator_reason')
                        ->label('السبب')
                        ->badge()
                        ->color('danger')
                        ->columnSpanFull(),
                ]),

            // ── Row 4: Notes ──────────────────────────────────────────────────
            Section::make('ملاحظات')
                ->icon('heroicon-o-document-text')
                ->iconColor('gray')
                ->visible(fn (Agent $record) => !empty($record->notes))
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->columnSpanFull(),
                ]),

            // ── Row 5: Daily Progress Chart ────────────────────────────────────
            Section::make('المخطط الزمني للأداء')
                ->icon('heroicon-o-chart-bar')
                ->iconColor('primary')
                ->collapsible()
                ->visible(fn (Agent $record): bool => $record->dailySnapshots()->exists())
                ->schema([
                    ViewEntry::make('daily_progress')
                        ->view('filament.agent.daily-progress-chart')
                        ->state(function (Agent $record): array {
                            $all  = $record->dailySnapshots()
                                ->orderBy('data_date')
                                ->get(['data_date', 'current_total']);

                            $daily = [
                                'labels' => $all->map(fn ($s) => $s->data_date->format('d/m'))->toArray(),
                                'data'   => $all->map(fn ($s) => $s->transfer_count + $s->new_line_count)->toArray(),
                            ];

                            $byWeek = $all->groupBy(fn ($s) => $s->data_date->format('Y-W'))->map->last()->values();
                            $weekly = [
                                'labels' => $byWeek->map(fn ($s) => $s->data_date->format('d/m'))->toArray(),
                                'data'   => $byWeek->map(fn ($s) => $s->transfer_count + $s->new_line_count)->toArray(),
                            ];

                            $byMonth = $all->groupBy(fn ($s) => $s->data_date->format('Y-m'))->map->last()->values();
                            $monthly = [
                                'labels' => $byMonth->map(fn ($s) => $s->data_date->format('m/Y'))->toArray(),
                                'data'   => $byMonth->map(fn ($s) => $s->transfer_count + $s->new_line_count)->toArray(),
                            ];

                            return compact('daily', 'weekly', 'monthly');
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
