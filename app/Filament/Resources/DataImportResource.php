<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataImportResource\Pages;
use App\Models\DataImport;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Tables\Table;

class DataImportResource extends Resource
{
    protected static ?string $model = DataImport::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-arrow-up-tray'; }

    public static function getNavigationGroup(): ?string { return 'إدارة البيانات'; }

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'استيراد بيانات';

    protected static ?string $pluralLabel = 'استيراد البيانات';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            View::make('filament.components.excel-fields-notice')
                ->columnSpanFull(),
            Section::make('بيانات الاستيراد')
                ->columns(2)
                ->schema([
                    DatePicker::make('data_date')
                        ->label('تاريخ البيانات')
                        ->required(),
                    Select::make('source_type')
                        ->label('نوع المصدر')
                        ->options([
                            'excel' => 'ملف Excel',
                            'api'   => 'API',
                        ])
                        ->required()
                        ->default('excel')
                        ->live(),
                    FileUpload::make('stored_filepath')
                        ->label('ملف Excel')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(fn (Get $get) => $get('source_type') === 'excel')
                        ->visible(fn (Get $get) => $get('source_type') === 'excel')
                        ->storeFileNamesIn('original_filename'),

                    TextInput::make('api_url')
                        ->label('رابط API')
                        ->url()
                        ->required(fn (Get $get) => $get('source_type') === 'api')
                        ->visible(fn (Get $get) => $get('source_type') === 'api')
                        ->placeholder('https://example.com/api/agent-stats'),

                    TextInput::make('api_token')
                        ->label('توكن API')
                        ->password()
                        ->revealable()
                        ->required(fn (Get $get) => $get('source_type') === 'api')
                        ->visible(fn (Get $get) => $get('source_type') === 'api'),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('إحصائيات المعالجة')
                ->columns(4)
                ->schema([
                    TextEntry::make('status')
                        ->label('الحالة')
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'success'    => 'success',
                            'failed'     => 'danger',
                            'processing' => 'info',
                            default      => 'warning',
                        })
                        ->formatStateUsing(fn ($state) => match($state) {
                            'success'    => 'مكتمل',
                            'failed'     => 'فشل',
                            'processing' => 'جارٍ المعالجة',
                            default      => 'في الانتظار',
                        }),
                    TextEntry::make('source_type')
                        ->label('نوع المصدر')
                        ->formatStateUsing(fn ($state) => match($state) {
                            'excel'     => 'Excel',
                            'api'       => 'API أرقام',
                            'deals_api' => 'API خطوط الوكلاء',
                            default     => $state,
                        }),
                    TextEntry::make('data_date')->label('تاريخ البيانات')->date('d/m/Y'),
                    TextEntry::make('processing_duration_ms')->label('المدة (ms)'),
                    TextEntry::make('total_agents')->label('الإجمالي'),
                    TextEntry::make('processed')->label('نجح')->color('success'),
                    TextEntry::make('rejected')->label('مرفوض')->color('danger'),
                    TextEntry::make('errors_count')->label('أخطاء'),
                    TextEntry::make('promotions_count')->label('ترقيات'),
                    TextEntry::make('demotions_count')->label('تهبيطات'),
                    TextEntry::make('warnings_count')->label('تحذيرات'),
                    TextEntry::make('error_message')->label('رسالة الخطأ')->columnSpan(4)->visible(fn ($record) => ! empty($record->error_message)),
                ]),

            Section::make('تفاصيل الأخطاء')
                ->visible(fn ($record) => ! empty($record->error_details))
                ->schema([
                    RepeatableEntry::make('error_details')
                        ->label('')
                        ->schema([
                            TextEntry::make('agent_id')->label('معرف الوكيل')->copyable()->fontFamily('mono'),
                            TextEntry::make('error')->label('السبب')->color('danger'),
                        ])
                        ->columns(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('data_date')
                    ->label('تاريخ البيانات')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('original_filename')
                    ->label('اسم الملف')
                    ->limit(35),
                TextColumn::make('total_agents')
                    ->label('إجمالي')
                    ->sortable(),
                TextColumn::make('processed')
                    ->label('معالج')
                    ->sortable(),
                TextColumn::make('rejected')
                    ->label('مرفوض')
                    ->sortable(),
                TextColumn::make('promotions_count')
                    ->label('ترقيات')
                    ->sortable(),
                TextColumn::make('demotions_count')
                    ->label('تهبيطات')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'success')    { return 'success'; }
                        if ($state === 'failed')     { return 'danger'; }
                        if ($state === 'processing') { return 'info'; }
                        if ($state === 'pending')    { return 'warning'; }
                        return 'gray';
                    })
                    ->formatStateUsing(function (string $state): string {
                        if ($state === 'success')    { return 'مكتمل'; }
                        if ($state === 'failed')     { return 'فشل'; }
                        if ($state === 'processing') { return 'جارٍ المعالجة'; }
                        if ($state === 'pending')    { return 'في الانتظار'; }
                        return $state;
                    }),
                TextColumn::make('created_at')
                    ->label('وقت الرفع')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending'    => 'في الانتظار',
                        'processing' => 'جارٍ المعالجة',
                        'success'    => 'مكتمل',
                        'failed'     => 'فشل',
                    ]),
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                Action::make('process')
                    ->label('إعادة المعالجة')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status === 'success')
                    ->action(function (DataImport $record) {
                        \App\Jobs\ProcessDataImport::dispatch($record);

                        Notification::make()
                            ->title('بدأت عملية المعالجة')
                            ->body('يتم الآن معالجة البيانات في الخلفية.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDataImports::route('/'),
            'create' => Pages\CreateDataImport::route('/create'),
            'view'   => Pages\ViewDataImport::route('/{record}'),
        ];
    }
}
