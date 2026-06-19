<?php

namespace App\Filament\DistributorPanel\Resources;

use App\Filament\DistributorPanel\Resources\MyAgentsResource\Pages;
use App\Models\Agent;
use App\Models\Club;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response as AuthResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MyAgentsResource extends Resource
{
    protected static ?string $model = Agent::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-users'; }

    public static function getNavigationGroup(): string { return 'وكلائي'; }

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'وكيل';

    protected static ?string $pluralLabel = 'وكلائي';

    protected static ?string $recordTitleAttribute = 'agent_name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('distributor_id', auth('distributor')->id());
    }

    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): AuthResponse
    {
        $key = $action instanceof \UnitEnum ? $action->name : $action;

        return match($key) {
            'viewAny' => AuthResponse::allow(),
            'view'    => ($record?->distributor_id === auth('distributor')->id())
                             ? AuthResponse::allow()
                             : AuthResponse::deny(),
            default   => AuthResponse::deny(),
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent_name')
                    ->label('اسم الوكيل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('club.club_name')
                    ->label('النادي الحالي')
                    ->badge()
                    ->color(fn (Agent $record): string => match ((int) ($record->club?->club_order ?? 0)) {
                        1       => 'success',
                        2       => 'info',
                        3       => 'warning',
                        default => 'gray',
                    })
                    ->default('خارج الأندية'),

                TextColumn::make('campaign_increase')
                    ->label('الزيادة')
                    ->getStateUsing(fn (Agent $record): int => $record->transfer_count + $record->new_line_count)
                    ->badge()
                    ->color('success')
                    ->sortable(false),

                TextColumn::make('transfer_pct')
                    ->label('نسبة التحويل %')
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
                    })
                    ->sortable(false),

                IconColumn::make('is_first_arrival')
                    ->label('أوائل')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->falseColor('gray'),

                TextColumn::make('is_violator')
                    ->label('مخالف')
                    ->formatStateUsing(fn ($state) => $state ? '⚠ مخالف' : '')
                    ->badge()
                    ->color('danger')
                    ->sortable(false),
            ])
            ->defaultSort('agent_name', 'asc')
            ->searchPlaceholder('بحث بالاسم...')
            ->filters([
                SelectFilter::make('current_club_id')
                    ->label('النادي')
                    ->options(Club::all()->pluck('club_name', 'club_id'))
                    ->placeholder('كل الأندية'),

                TernaryFilter::make('is_first_arrival')
                    ->label('من الأوائل'),

                TernaryFilter::make('is_violator')
                    ->label('مخالف'),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()->label('عرض التفاصيل'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('لا يوجد وكلاء مرتبطون بحسابك')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateDescription('يرجى التواصل مع الإدارة لإضافة وكلاء لحسابك.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyAgents::route('/'),
            'view'  => Pages\ViewMyAgent::route('/{record}'),
        ];
    }
}
