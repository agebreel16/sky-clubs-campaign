<?php

namespace App\Filament\Resources\DistributorResource\Pages;

use App\Filament\Resources\DistributorResource;
use App\Models\Agent;
use App\Models\Distributor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewDistributor extends ViewRecord
{
    protected static string $resource = DistributorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assign_agents')
                ->label('تعيين وكلاء')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->modalHeading('تعيين وكلاء للموزع')
                ->modalSubmitActionLabel('تعيين الوكلاء المحددين')
                ->modalWidth('xl')
                ->form([
                    Select::make('agent_ids')
                        ->label('اختر الوكلاء')
                        ->options(function (): array {
                            $distributorId = $this->getRecord()->id;

                            return Agent::with(['distributor', 'club'])
                                ->get()
                                ->mapWithKeys(function (Agent $a) use ($distributorId): array {
                                    $label = $a->agent_name
                                        . ' — '
                                        . ($a->club?->club_name ?? 'خارج الأندية');

                                    if ($a->distributor_id && $a->distributor_id !== $distributorId) {
                                        $label .= ' ⟵ ' . ($a->distributor?->name ?? '?');
                                    }

                                    return [$a->agent_id => $label];
                                })
                                ->toArray();
                        })
                        ->multiple()
                        ->searchable()
                        ->required()
                        ->placeholder('ابحث باسم الوكيل واختر واحداً أو أكثر...')
                        ->helperText('الوكلاء المشار إليهم بـ ⟵ مرتبطون بموزع آخر حالياً — سيُعاد تعيينهم لهذا الموزع.'),
                ])
                ->action(function (array $data, Distributor $record): void {
                    $count = count($data['agent_ids']);
                    Agent::whereIn('agent_id', $data['agent_ids'])
                        ->update(['distributor_id' => $record->id]);

                    Notification::make()
                        ->success()
                        ->title('تم التعيين بنجاح')
                        ->body("تم تعيين {$count} " . ($count === 1 ? 'وكيل' : 'وكلاء') . " للموزع {$record->name}.")
                        ->send();
                }),

            EditAction::make()->label('تعديل'),
            DeleteAction::make()->label('حذف'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            Grid::make(2)->schema([

                Section::make('بيانات الموزع')
                    ->icon('heroicon-o-building-storefront')
                    ->iconColor('primary')
                    ->schema([
                        TextEntry::make('name')
                            ->label('الاسم الكامل')
                            ->size('lg')
                            ->weight('bold'),

                        TextEntry::make('phone')
                            ->label('رقم الجوال')
                            ->copyable()
                            ->icon('heroicon-m-phone'),

                        TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->copyable()
                            ->icon('heroicon-m-envelope'),

                        TextEntry::make('region')
                            ->label('المنطقة')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-map-pin'),
                    ]),

                Section::make('إحصائيات الوكلاء')
                    ->icon('heroicon-o-chart-pie')
                    ->iconColor('success')
                    ->schema([
                        TextEntry::make('total_agents')
                            ->label('إجمالي الوكلاء')
                            ->getStateUsing(fn (Distributor $record): int => $record->agents()->count())
                            ->badge()
                            ->color('info')
                            ->size('lg'),

                        TextEntry::make('agents_in_clubs')
                            ->label('داخل الأندية')
                            ->getStateUsing(fn (Distributor $record): int => $record->agents()->whereNotNull('current_club_id')->count())
                            ->badge()
                            ->color('success'),

                        TextEntry::make('agents_violators')
                            ->label('المخالفون')
                            ->getStateUsing(fn (Distributor $record): int => $record->agents()->where('is_violator', true)->count())
                            ->badge()
                            ->color('danger'),

                        TextEntry::make('agents_outside')
                            ->label('خارج الأندية')
                            ->getStateUsing(fn (Distributor $record): int => $record->agents()->whereNull('current_club_id')->count())
                            ->badge()
                            ->color('gray'),
                    ]),
            ]),

            Section::make('معلومات الحساب')
                ->icon('heroicon-o-cog-6-tooth')
                ->iconColor('gray')
                ->columns(3)
                ->schema([
                    IconEntry::make('is_active')
                        ->label('الحساب نشط')
                        ->boolean(),

                    TextEntry::make('created_at')
                        ->label('تاريخ الإنشاء')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('آخر تحديث')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }
}
