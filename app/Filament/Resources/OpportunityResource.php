<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpportunityResource\Pages;
use App\Models\Club;
use App\Models\Opportunity;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-ticket'; }

    public static function getNavigationGroup(): ?string { return 'إدارة الحملة'; }

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'فرصة سحب';

    protected static ?string $pluralLabel = 'فرص السحب';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('تفاصيل الفرصة')
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
                    Select::make('type')
                        ->options([
                            'entry'         => 'دخول',
                            'maintenance'   => 'صيانة',
                            'bonus'         => 'مكافأة',
                            'first_arrival' => 'الحضور الأول',
                        ])
                        ->required(),
                    DateTimePicker::make('earned_date')
                        ->required(),
                    Toggle::make('is_active')
                        ->label('نشطة (غير ملغاة)')
                        ->default(true),
                    TextInput::make('cancellation_reason')
                        ->maxLength(500)
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
                    ->badge()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'entry'         => 'success',
                        'maintenance'   => 'info',
                        'bonus'         => 'warning',
                        'first_arrival' => 'danger',
                        default         => 'gray',
                    }),
                TextColumn::make('earned_date')
                    ->label('تاريخ الكسب')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean(),
                TextColumn::make('cancellation_reason')
                    ->label('سبب الإلغاء')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('earned_date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'entry'         => 'دخول',
                        'maintenance'   => 'صيانة',
                        'bonus'         => 'مكافأة',
                        'first_arrival' => 'الحضور الأول',
                    ]),
                SelectFilter::make('club_id')
                    ->label('النادي')
                    ->options(Club::all()->pluck('club_name', 'club_id')),
                TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'view'  => Pages\ViewOpportunity::route('/{record}'),
        ];
    }
}
