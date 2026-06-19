<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubChangeRequestResource\Pages;
use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\ClubChangeRequest;
use App\Models\HistoryLog;
use App\Models\Opportunity;
use App\Models\Reward;
use App\Notifications\AgentPortalNotification;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClubChangeRequestResource extends Resource
{
    protected static ?string $model = ClubChangeRequest::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-clipboard-document-check'; }
    public static function getNavigationGroup(): string { return 'إدارة الحملة'; }
    public static function getNavigationLabel(): string { return 'طلبات تغيير النادي'; }
    public static function getModelLabel(): string { return 'طلب تغيير النادي'; }
    public static function getPluralModelLabel(): string { return 'طلبات تغيير النادي'; }
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = ClubChangeRequest::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('agent.agent_name')
                    ->label('الوكيل')
                    ->description(fn ($record) => $record->agent?->distributor?->name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('change_type')
                    ->label('نوع التغيير')
                    ->badge()
                    ->color(fn ($state) => $state === 'promotion' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 'promotion' ? '↑ ترقية' : '↓ تهبيط'),

                TextColumn::make('fromClub.club_name')
                    ->label('من')
                    ->default('خارج الأندية')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('toClub.club_name')
                    ->label('إلى')
                    ->default('خارج الأندية')
                    ->badge()
                    ->color(fn ($record) => $record->change_type === 'promotion' ? 'success' : 'warning'),

                TextColumn::make('agent_stats_snapshot.campaign_increase')
                    ->label('إجمالي الزيادة')
                    ->suffix(' خط')
                    ->numeric()
                    ->sortable(false),

                TextColumn::make('agent_stats_snapshot.transfer_pct')
                    ->label('نسبة التحويل')
                    ->suffix('%')
                    ->sortable(false),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending'        => 'warning',
                        'approved'       => 'success',
                        'rejected'       => 'danger',
                        'auto_cancelled' => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending'        => 'معلّق',
                        'approved'       => 'مقبول',
                        'rejected'       => 'مرفوض',
                        'auto_cancelled' => 'ملغى',
                    }),

                TextColumn::make('reviewer.name')
                    ->label('راجعه')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label('تاريخ الاكتشاف')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending'        => 'معلّق',
                        'approved'       => 'مقبول',
                        'rejected'       => 'مرفوض',
                        'auto_cancelled' => 'ملغى',
                    ])
                    ->default('pending'),

                SelectFilter::make('change_type')
                    ->label('النوع')
                    ->options([
                        'promotion' => 'ترقية',
                        'demotion'  => 'تهبيط',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('قبول')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalWidth('md')
                    ->modalAlignment(\Filament\Support\Enums\Alignment::Center)
                    ->modalHeading('تأكيد القبول')
                    ->modalDescription('سيُطبَّق تغيير النادي فوراً على حساب الوكيل.')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function (ClubChangeRequest $record) {
                        static::approveRequest($record);
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->modalWidth('xl')
                    ->modalAlignment(\Filament\Support\Enums\Alignment::Center)
                    ->modalHeading('رفض طلب تغيير النادي')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form(fn ($record) => [
                        Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Toggle::make('mark_as_violator')
                            ->label('تحويل الوكيل إلى قائمة المخالفين')
                            ->helperText('استخدم هذا الخيار عند الاشتباه في التلاعب بالأرقام')
                            ->visible($record->change_type === 'promotion')
                            ->default(false),
                    ])
                    ->action(function (ClubChangeRequest $record, array $data) {
                        static::rejectRequest($record, $data);
                    }),
            ])
            ->bulkActions([])
            ->poll('30s');
    }

    protected static function approveRequest(ClubChangeRequest $record): void
    {
        // حارس ضد double-approve (race condition أو تحديث الصفحة المزدوج)
        $fresh = $record->fresh();
        if (! $fresh || $fresh->status !== 'pending') {
            Notification::make()->warning()->title('تمت معالجة هذا الطلب مسبقاً')->send();
            return;
        }

        $agent  = $record->agent;
        $toClub = $record->toClub;

        if (! $agent) {
            Notification::make()->danger()->title('الوكيل غير موجود')->send();
            return;
        }

        $updateData = ['entry_date' => now()];

        if ($record->change_type === 'promotion' && $toClub) {
            $isFirst = Agent::where('current_club_id', $toClub->club_id)->count() < $toClub->first_arrival_count;
            $updateData['current_club_id'] = $toClub->club_id;
            $updateData['is_first_arrival'] = $isFirst;

            // Query Builder — يتجاوز Observer لمنع ازدواجية Reward/Opportunity
            Agent::where('agent_id', $agent->agent_id)->update($updateData);

            HistoryLog::create([
                'agent_id'        => $agent->agent_id,
                'event_type'      => 'promotion',
                'from_club_id'    => $record->from_club_id,
                'to_club_id'      => $toClub->club_id,
                'reason'          => 'قبول طلب الترقية من قِبَل الإدارة',
                'event_timestamp' => now(),
            ]);

            Reward::create([
                'agent_id'         => $agent->agent_id,
                'club_id'          => $toClub->club_id,
                'amount'           => $isFirst
                                        ? $toClub->first_arrival_reward_amount
                                        : $toClub->base_reward_amount,
                'is_first_arrival' => $isFirst,
                'payment_status'   => 'pending',
            ]);

            $entryCount = $toClub->entry_opportunities ?? 1;
            for ($i = 0; $i < $entryCount; $i++) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $toClub->club_id,
                    'type'        => 'entry',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }

            if ($isFirst) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $toClub->club_id,
                    'type'        => 'first_arrival',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }

            // الإشعار للوكيل — فقط عند قبول الترقية
            $promoTitle = 'مبروك الترقية! 🏆';
            $promoBody  = "تهانينا! لقد انضممت إلى {$toClub->club_name}.";

            AgentNotification::create([
                'agent_id'          => $agent->agent_id,
                'club_id'           => $toClub->club_id,
                'notification_type' => 'promotion',
                'title'             => $promoTitle,
                'body'              => $promoBody,
                'category'          => 'in_club',
                'sent_at'           => now(),
            ]);

            if ($agent->portal_token) {
                $agent->notify(new AgentPortalNotification(
                    title:   $promoTitle,
                    body:    $promoBody,
                    sendSms: false,
                ));
            }
        } else {
            // تهبيط — تحديث النادي فقط، لا إشعار
            $updateData['current_club_id'] = $record->to_club_id; // null إذا خارج الأندية
            Agent::where('agent_id', $agent->agent_id)->update($updateData);

            HistoryLog::create([
                'agent_id'        => $agent->agent_id,
                'event_type'      => 'demotion',
                'from_club_id'    => $record->from_club_id,
                'to_club_id'      => $record->to_club_id,
                'reason'          => 'قبول طلب التهبيط من قِبَل الإدارة',
                'event_timestamp' => now(),
            ]);
        }

        $record->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        Notification::make()->success()->title('تم القبول وتطبيق التغيير')->send();
    }

    protected static function rejectRequest(ClubChangeRequest $record, array $data): void
    {
        // حارس ضد double-reject
        $fresh = $record->fresh();
        if (! $fresh || $fresh->status !== 'pending') {
            Notification::make()->warning()->title('تمت معالجة هذا الطلب مسبقاً')->send();
            return;
        }

        $agent = $record->agent;

        $record->update([
            'status'           => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        HistoryLog::create([
            'agent_id'        => $agent->agent_id,
            'event_type'      => 'rejection',
            'from_club_id'    => $record->from_club_id,
            'to_club_id'      => $record->to_club_id,
            'reason'          => $data['rejection_reason'],
            'event_timestamp' => now(),
        ]);
        // لا إشعار للوكيل عند الرفض

        if (! empty($data['mark_as_violator'])) {
            Agent::where('agent_id', $agent->agent_id)->update([
                'is_violator'     => true,
                'violator_since'  => now(),
                'violator_reason' => $data['rejection_reason'],
            ]);

            HistoryLog::create([
                'agent_id'        => $agent->agent_id,
                'event_type'      => 'violation',
                'from_club_id'    => null,
                'to_club_id'      => $agent->current_club_id,
                'reason'          => $data['rejection_reason'],
                'event_timestamp' => now(),
            ]);
            // لا إشعار للوكيل عند تصنيف المخالفة

            Notification::make()->success()->title('تم الرفض وتصنيف الوكيل كمخالف')->send();
        } else {
            Notification::make()->success()->title('تم رفض الطلب')->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClubChangeRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
