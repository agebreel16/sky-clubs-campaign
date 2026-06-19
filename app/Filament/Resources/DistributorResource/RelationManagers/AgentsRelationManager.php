<?php

namespace App\Filament\Resources\DistributorResource\RelationManagers;

use App\Models\Agent;
use App\Models\Club;
use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgentsRelationManager extends RelationManager
{
    protected static string $relationship = 'agents';

    protected static ?string $title = 'الوكلاء';

    protected static ?string $label = 'وكيل';

    protected static ?string $pluralLabel = 'الوكلاء';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('agent_name')
            ->heading('وكلاء هذا الموزع')
            ->description('اختر وكلاء موجودين في النظام لربطهم بهذا الموزع.')
            ->columns([
                TextColumn::make('agent_name')
                    ->label('اسم الوكيل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Agent $record): string => \App\Filament\Resources\AgentResource::getUrl('view', ['record' => $record])),

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

                TextColumn::make('current_total')
                    ->label('الإجمالي')
                    ->sortable(),

                TextColumn::make('campaign_increase')
                    ->label('الزيادة')
                    ->getStateUsing(fn (Agent $record): int => $record->transfer_count + $record->new_line_count)
                    ->badge()
                    ->color('success'),

                TextColumn::make('is_violator')
                    ->label('مخالف')
                    ->formatStateUsing(fn ($state) => $state ? '⚠ مخالف' : '')
                    ->badge()
                    ->color('danger'),

                IconColumn::make('is_first_arrival')
                    ->label('أوائل')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('current_club_id')
                    ->label('النادي')
                    ->options(Club::all()->pluck('club_name', 'club_id'))
                    ->placeholder('كل الأندية'),
            ])
            ->headerActions([
                AssociateAction::make()
                    ->label('إضافة وكلاء موجودين')
                    ->icon('heroicon-m-user-plus')
                    ->multiple()
                    ->recordSelectSearchColumns(['agent_name'])
                    ->recordTitle(fn (Agent $record): string =>
                        "{$record->agent_name} — " . ($record->club?->club_name ?? 'خارج الأندية')
                    ),
            ])
            ->actions([
                DissociateAction::make()
                    ->label('إزالة الارتباط')
                    ->requiresConfirmation()
                    ->modalHeading('إزالة الوكيل من هذا الموزع')
                    ->modalDescription('هل تريد إزالة ارتباط هذا الوكيل بالموزع؟ لن يُحذف الوكيل من النظام.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()->label('إزالة ارتباط المحددين'),
                ]),
            ])
            ->emptyStateHeading('لا يوجد وكلاء مرتبطون بهذا الموزع')
            ->emptyStateDescription('اضغط "إضافة وكيل موجود" لاختيار وكلاء من النظام.')
            ->emptyStateIcon('heroicon-o-users')
            ->defaultSort('agent_name', 'asc');
    }
}
