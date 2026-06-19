<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentImportResource\Pages;
use App\Models\AgentImportLog;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgentImportResource extends Resource
{
    protected static ?string $model = AgentImportLog::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-user-plus'; }

    public static function getNavigationGroup(): string { return 'إدارة الوكلاء'; }

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'agent-imports';

    protected static ?string $label = 'استيراد وكلاء';

    protected static ?string $pluralLabel = 'استيراد الوكلاء';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            View::make('filament.components.agent-import-excel-notice')
                ->columnSpanFull()
                ->visible(fn ($get) => $get('source_type') === 'excel' || $get('source_type') === null),

            View::make('filament.components.agent-import-api-notice')
                ->columnSpanFull()
                ->visible(fn ($get) => $get('source_type') === 'api'),

            Section::make('مصدر الاستيراد')
                ->columns(2)
                ->schema([
                    Select::make('source_type')
                        ->label('مصدر البيانات')
                        ->options([
                            'excel' => 'ملف Excel',
                            'api'   => 'API خارجي',
                        ])
                        ->required()
                        ->default('excel')
                        ->live(),

                    FileUpload::make('stored_filepath')
                        ->label('ملف Excel')
                        ->disk('local')
                        ->directory('agent-imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required()
                        ->visible(fn ($get) => $get('source_type') === 'excel')
                        ->storeFileNamesIn('original_filename'),

                    TextInput::make('api_url')
                        ->label('رابط الـ API')
                        ->url()
                        ->required()
                        ->placeholder('https://api.example.com/agents')
                        ->visible(fn ($get) => $get('source_type') === 'api'),

                    TextInput::make('api_token')
                        ->label('رمز المصادقة (Token)')
                        ->password()
                        ->revealable()
                        ->required()
                        ->visible(fn ($get) => $get('source_type') === 'api'),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('إحصائيات الاستيراد')->columns(4)->schema([
                TextEntry::make('status')->label('الحالة')->badge()
                    ->color(fn ($state) => match($state) {
                        'success'    => 'success',
                        'failed'     => 'danger',
                        'processing' => 'info',
                        default      => 'warning',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'success'    => 'مكتمل',
                        'failed'     => 'فشل',
                        'processing' => 'جارٍ',
                        default      => 'انتظار',
                    }),
                TextEntry::make('source_type')->label('المصدر')
                    ->formatStateUsing(fn ($state) => $state === 'excel' ? 'Excel' : 'API'),
                TextEntry::make('total_rows')->label('إجمالي الصفوف'),
                TextEntry::make('processing_duration_ms')->label('المدة (ms)'),
                TextEntry::make('created_count')->label('أُنشئ')->color('success'),
                TextEntry::make('skipped_count')->label('تجاهله')->color('warning'),
                TextEntry::make('rejected_count')->label('مرفوض')->color('danger'),
                TextEntry::make('errors_count')->label('أخطاء'),
                TextEntry::make('error_message')->label('رسالة الخطأ')
                    ->columnSpan(4)
                    ->visible(fn ($record) => ! empty($record->error_message)),
            ]),

            Section::make('الوكلاء الذين تمت إضافتهم')
                ->visible(fn ($record) => ! empty($record->success_details))
                ->schema([
                    RepeatableEntry::make('success_details')->label('')
                        ->schema([
                            TextEntry::make('agent_id')->label('المعرف')->copyable()->fontFamily('mono'),
                            TextEntry::make('agent_name')->label('الاسم'),
                        ])
                        ->columns(2),
                ]),

            Section::make('الصفوف التي بها مشكلة')
                ->visible(fn ($record) => ! empty($record->error_details))
                ->schema([
                    RepeatableEntry::make('error_details')->label('')
                        ->schema([
                            TextEntry::make('row')->label('رقم الصف'),
                            TextEntry::make('agent_id')->label('معرف الوكيل')->copyable()->fontFamily('mono'),
                            TextEntry::make('error')->label('السبب')->color('danger'),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_type')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'excel' ? 'Excel' : 'API')
                    ->color(fn ($state) => $state === 'excel' ? 'success' : 'info'),
                TextColumn::make('original_filename')
                    ->label('الملف')
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('total_rows')
                    ->label('الإجمالي')
                    ->sortable(),
                TextColumn::make('created_count')
                    ->label('أُنشئ')
                    ->sortable()
                    ->color('success'),
                TextColumn::make('skipped_count')
                    ->label('تم تجاهله')
                    ->sortable()
                    ->color('warning'),
                TextColumn::make('rejected_count')
                    ->label('مرفوض')
                    ->sortable()
                    ->color('danger'),
                TextColumn::make('status')
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
                        'processing' => 'جارٍ',
                        default      => 'في الانتظار',
                    }),
                TextColumn::make('created_at')
                    ->label('وقت الطلب')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending'    => 'في الانتظار',
                        'processing' => 'جارٍ',
                        'success'    => 'مكتمل',
                        'failed'     => 'فشل',
                    ]),
                SelectFilter::make('source_type')
                    ->label('المصدر')
                    ->options(['excel' => 'Excel', 'api' => 'API']),
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                Action::make('retry')
                    ->label('إعادة المحاولة')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'failed')
                    ->action(function (AgentImportLog $record) {
                        $record->update(['status' => 'pending', 'error_message' => null]);
                        \App\Jobs\ProcessAgentImport::dispatch($record);

                        Notification::make()
                            ->title('بدأت إعادة المعالجة')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAgentImports::route('/'),
            'create' => Pages\CreateAgentImport::route('/create'),
            'view'   => Pages\ViewAgentImport::route('/{record}'),
        ];
    }
}
