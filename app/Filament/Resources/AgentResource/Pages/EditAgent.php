<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\Club;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditAgent extends EditRecord
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('تحذير: وكيل مخالف')
                ->columns(2)
                ->hidden(fn ($record) => ! $record?->is_violator)
                ->extraAttributes(['style' => 'border: 2px solid #ef4444; background-color: #fef2f2;'])
                ->schema([
                    Placeholder::make('violator_since_display')
                        ->label('تاريخ التصنيف')
                        ->content(fn ($record) => $record?->violator_since?->format('d/m/Y H:i') ?? '—'),

                    Placeholder::make('violator_reason_display')
                        ->label('سبب التصنيف')
                        ->content(fn ($record) => $record?->violator_reason ?? '—'),

                    Toggle::make('is_violator')
                        ->label('إلغاء تصنيف المخالفة')
                        ->helperText('أوقف هذا الخيار واحفظ لإعادة الوكيل للنظام الطبيعي')
                        ->columnSpanFull(),
                ]),

            Section::make('هوية الوكيل')
                ->schema([
                    TextInput::make('agent_name')
                        ->label('اسم الوكيل')
                        ->required()
                        ->minLength(3)
                        ->maxLength(200),

                    TextInput::make('phone')
                        ->label('رقم الجوال')
                        ->nullable()
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('05XXXXXXXX'),
                ]),

            Section::make('بيانات الأرقام')
                ->columns(3)
                ->description('الأساس مجمّد ولا يمكن تعديله. يجب أن يكون الإجمالي الحالي >= الأرقام القديمة.')
                ->schema([
                    TextInput::make('baseline_count')
                        ->label('الأساس (مجمّد)')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('pre_campaign_count')
                        ->label('الأرقام القديمة')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->live()
                        ->rules(['required', 'integer', 'min:0']),

                    TextInput::make('current_total')
                        ->label('الإجمالي الحالي')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->live()
                        ->rules(['required', 'integer', 'min:0']),

                    TextInput::make('transfer_count')
                        ->label('التحويلات')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->live(),

                    TextInput::make('new_line_count')
                        ->label('الخطوط الجديدة')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->live(),

                    Placeholder::make('campaign_increase_preview')
                        ->label('الزيادة في الحملة')
                        ->content(function ($get): string {
                            $transfer = (int) $get('transfer_count');
                            $newLine  = (int) $get('new_line_count');
                            return ($transfer + $newLine) . ' خط';
                        }),
                ]),

            Section::make('عضوية النادي')
                ->columns(2)
                ->schema([
                    Select::make('current_club_id')
                        ->label('النادي الحالي')
                        ->options(Club::all()->pluck('club_name', 'club_id'))
                        ->nullable()
                        ->placeholder('خارج الأندية'),

                    DateTimePicker::make('entry_date')
                        ->label('تاريخ الدخول للنادي')
                        ->nullable(),

                    Toggle::make('is_first_arrival')
                        ->label('من الأوائل'),
                ]),

            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->nullable()
                        ->rows(4)
                        ->maxLength(1000),
                ]),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure baseline_count is never changed
        $original = $this->getRecord()->baseline_count;
        $data['baseline_count'] = $original;

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('تم حفظ البيانات بنجاح ✓');
    }
}
