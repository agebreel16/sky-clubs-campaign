<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RewardResource\Pages;
use App\Models\Club;
use App\Models\Reward;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-currency-dollar'; }

    public static function getNavigationGroup(): ?string { return 'إدارة الحملة'; }

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'مكافأة';

    protected static ?string $pluralLabel = 'المكافآت';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('تفاصيل المكافأة')
                ->columns(2)
                ->schema([
                    Select::make('agent_id')
                        ->relationship('agent', 'agent_name')
                        ->searchable()
                        ->required(),
                    Select::make('club_id')
                        ->label('النادي')
                        ->options(Club::all()->pluck('club_name', 'club_id'))
                        ->required(),
                    TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('₪')
                        ->minValue(0),
                    Toggle::make('is_first_arrival')
                        ->label('مكافأة الحضور الأول'),
                ]),

            Section::make('حالة الدفع')
                ->columns(2)
                ->schema([
                    Select::make('payment_status')
                        ->options([
                            'pending' => 'معلق',
                            'paid'    => 'مدفوع',
                            'failed'  => 'فشل',
                        ])
                        ->required()
                        ->default('pending'),
                    DateTimePicker::make('paid_date')
                        ->label('تاريخ الدفع')
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent.agent_name')
                    ->label('الوكيل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('club.club_name')
                    ->label('النادي')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('المبلغ (شيكل)')
                    ->money('ILS')
                    ->sortable(),
                IconColumn::make('is_first_arrival')
                    ->label('الحضور الأول')
                    ->boolean(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'paid'    => 'success',
                        'failed'  => 'danger',
                        'pending' => 'warning',
                        default   => 'gray',
                    }),
                TextColumn::make('paid_date')
                    ->label('تاريخ الدفع')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'معلق',
                        'paid'    => 'مدفوع',
                        'failed'  => 'فشل',
                    ]),
                SelectFilter::make('club_id')
                    ->label('النادي')
                    ->options(Club::all()->pluck('club_name', 'club_id')),
                TernaryFilter::make('is_first_arrival')->label('الحضور الأول'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRewards::route('/'),
            'create' => Pages\CreateReward::route('/create'),
            'view'   => Pages\ViewReward::route('/{record}'),
            'edit'   => Pages\EditReward::route('/{record}/edit'),
        ];
    }
}
