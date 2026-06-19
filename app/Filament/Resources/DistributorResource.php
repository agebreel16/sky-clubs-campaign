<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistributorResource\Pages;
use App\Filament\Resources\DistributorResource\RelationManagers\AgentsRelationManager;
use App\Models\Distributor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DistributorResource extends Resource
{
    protected static ?string $model = Distributor::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-building-storefront'; }

    public static function getNavigationGroup(): string { return 'إدارة الوكلاء'; }

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'موزع';

    protected static ?string $pluralLabel = 'الموزعون';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('بيانات الموزع')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('الاسم الكامل')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('أدخل الاسم الكامل'),

                    TextInput::make('phone')
                        ->label('رقم الجوال')
                        ->required()
                        ->tel()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true)
                        ->placeholder('05xxxxxxxx'),

                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->required()
                        ->email()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->placeholder('example@domain.com'),

                    TextInput::make('region')
                        ->label('المنطقة')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('أدخل المنطقة'),
                ]),

            Section::make('بيانات الحساب')
                ->description(fn (string $operation): string => $operation === 'edit'
                    ? 'اترك كلمة المرور فارغة إذا لم ترد تغييرها.'
                    : 'كلمة المرور التي سيستخدمها الموزع لتسجيل الدخول.')
                ->columns(2)
                ->schema([
                    TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->minLength(8)
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->placeholder('8 أحرف على الأقل'),

                    TextInput::make('password_confirmation')
                        ->label('تأكيد كلمة المرور')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->same('password')
                        ->dehydrated(false)
                        ->placeholder('أعد كتابة كلمة المرور'),
                ]),

            Section::make('الحالة')
                ->schema([
                    Toggle::make('is_active')
                        ->label('حساب نشط')
                        ->default(true)
                        ->helperText('الموزعون غير النشطين لا يستطيعون تسجيل الدخول.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('الجوال')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email')
                    ->label('البريد')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('region')
                    ->label('المنطقة')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('agents_count')
                    ->label('عدد الوكلاء')
                    ->getStateUsing(fn (Distributor $record): int => $record->agents()->count())
                    ->badge()
                    ->color(fn (Distributor $record): string => match (true) {
                        $record->agents()->count() === 0 => 'gray',
                        $record->agents()->count() < 5   => 'warning',
                        default                          => 'success',
                    })
                    ->sortable(false),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('بحث بالاسم أو الجوال أو البريد...')
            ->filters([
                SelectFilter::make('region')
                    ->label('المنطقة')
                    ->options(fn () => Distributor::distinct()->pluck('region', 'region')->toArray())
                    ->placeholder('كل المناطق'),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('النشطون فقط')
                    ->falseLabel('غير النشطين فقط'),
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AgentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDistributors::route('/'),
            'create' => Pages\CreateDistributor::route('/create'),
            'view'   => Pages\ViewDistributor::route('/{record}'),
            'edit'   => Pages\EditDistributor::route('/{record}/edit'),
        ];
    }
}
