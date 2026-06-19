<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubResource\Pages;
use App\Models\Club;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ClubResource extends Resource
{
    protected static ?string $model = Club::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-star'; }

    public static function getNavigationGroup(): ?string { return 'إدارة الحملة'; }

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'نادي';

    protected static ?string $pluralLabel = 'النوادي';

    protected static ?string $recordTitleAttribute = 'club_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('هوية النادي')
                ->columns(2)
                ->schema([
                    TextInput::make('club_name')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    TextInput::make('club_order')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

            Section::make('عتبات التأهيل')
                ->columns(3)
                ->schema([
                    TextInput::make('required_increase')
                        ->label('الأسطر الجديدة المطلوبة')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->suffix('أسطر'),
                    TextInput::make('required_transfer_count')
                        ->label('الحد الأدنى من أسطر التحويل')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->suffix('أسطر'),
                    TextInput::make('required_transfer_percentage')
                        ->label('نسبة التحويل (قاعدة 60%)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.01),
                ]),

            Section::make('إعدادات المكافآت')
                ->columns(3)
                ->schema([
                    TextInput::make('base_reward_amount')
                        ->label('مكافأة الدخول (شيكل)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₪'),
                    TextInput::make('first_arrival_reward_amount')
                        ->label('مكافأة الحضور الأول (شيكل)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₪'),
                    TextInput::make('first_arrival_count')
                        ->label('مقاعد الحضور الأول')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

            Section::make('إعدادات اليانصيب')
                ->columns(3)
                ->schema([
                    TextInput::make('seat_capacity')
                        ->label('الحد الأدنى من الوكلاء لفتح السحب')
                        ->required()
                        ->integer()
                        ->minValue(1),
                    TextInput::make('grand_prize_amount')
                        ->label('الجائزة الكبرى (شيكل)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₪'),
                    TextInput::make('entry_opportunities')
                        ->label('تذاكر السحب')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

            Section::make('المكافآت الإضافية')
                ->columns(2)
                ->schema([
                    Toggle::make('has_bonus_opportunities')
                        ->label('يوجد فرص مكافآت')
                        ->reactive(),
                    TextInput::make('bonus_per_numbers')
                        ->label('الأسطر لكل تذكرة مكافأة')
                        ->integer()
                        ->minValue(1)
                        ->nullable()
                        ->hidden(fn ($get) => ! $get('has_bonus_opportunities')),
                ]),

            Section::make('الحالة')
                ->schema([
                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('club_order')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),
                TextColumn::make('club_name')
                    ->label('النادي')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('required_increase')
                    ->label('الأسطر المطلوبة')
                    ->suffix(' أسطر')
                    ->sortable(),
                TextColumn::make('base_reward_amount')
                    ->label('مكافأة الدخول')
                    ->money('ILS')
                    ->sortable(),
                TextColumn::make('first_arrival_reward_amount')
                    ->label('مكافأة الحضور الأول')
                    ->money('ILS'),
                TextColumn::make('grand_prize_amount')
                    ->label('الجائزة الكبرى')
                    ->money('ILS')
                    ->sortable(),
                TextColumn::make('seat_capacity')
                    ->label('الحد الأدنى للسحب')
                    ->suffix(' وكلاء'),
                TextColumn::make('agents_count')
                    ->label('الأعضاء الحاليين')
                    ->counts('agents')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->defaultSort('club_order')
            ->filters([
                TernaryFilter::make('is_active')->label('الحالة'),
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
            'index'  => Pages\ListClubs::route('/'),
            'create' => Pages\CreateClub::route('/create'),
            'view'   => Pages\ViewClub::route('/{record}'),
            'edit'   => Pages\EditClub::route('/{record}/edit'),
        ];
    }
}
