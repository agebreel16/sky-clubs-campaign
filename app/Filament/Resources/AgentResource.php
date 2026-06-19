<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Models\Agent;
use App\Models\Club;
use App\Models\Distributor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-users'; }

    public static function getNavigationGroup(): string { return 'إدارة الوكلاء'; }

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'وكيل';

    protected static ?string $pluralLabel = 'الوكلاء';

    protected static ?string $recordTitleAttribute = 'agent_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('هوية الوكيل')
                ->schema([
                    TextInput::make('agent_name')
                        ->label('اسم الوكيل')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('أدخل الاسم الكامل'),

                    TextInput::make('phone')
                        ->label('رقم الجوال')
                        ->nullable()
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('05XXXXXXXX'),
                ]),

            Section::make('بيانات الأرقام')
                ->columns(3)
                ->description('الأساس مجمّد منذ بداية الحملة ولا يمكن تعديله.')
                ->schema([
                    TextInput::make('baseline_count')
                        ->label('الأساس (مجمّد)')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->disabledOn('edit')
                        ->dehydratedWhenHidden(),

                    TextInput::make('pre_campaign_count')
                        ->label('الأرقام القديمة')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, string $operation) {
                            if ($operation !== 'create') return;
                            AgentResource::autoAssignClub($set, $get);
                        }),

                    TextInput::make('current_total')
                        ->label('الإجمالي الحالي')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, string $operation) {
                            if ($operation !== 'create') return;
                            AgentResource::autoAssignClub($set, $get);
                        }),

                    TextInput::make('transfer_count')
                        ->label('التحويلات')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, string $operation) {
                            if ($operation !== 'create') return;
                            AgentResource::autoAssignClub($set, $get);
                        }),

                    TextInput::make('new_line_count')
                        ->label('الخطوط الجديدة')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, string $operation) {
                            if ($operation !== 'create') return;
                            AgentResource::autoAssignClub($set, $get);
                        }),
                ]),

            Section::make('عضوية النادي')
                ->columns(2)
                ->schema([
                    Select::make('distributor_id')
                        ->label('الموزع')
                        ->options(Distributor::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('اختر الموزع')
                        ->columnSpanFull(),

                    Select::make('current_club_id')
                        ->label('النادي الحالي')
                        ->options(Club::all()->pluck('club_name', 'club_id'))
                        ->nullable()
                        ->placeholder('خارج الأندية')
                        ->disabled(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(),

                    DateTimePicker::make('entry_date')
                        ->label('تاريخ الدخول للنادي')
                        ->nullable()
                        ->default(fn () => now()->max(\Carbon\Carbon::parse('2026-05-01')))
                        ->minDate(\Carbon\Carbon::parse('2026-05-01'))
                        ->validationMessages([
                            'min_date' => 'تاريخ الدخول للنادي لا يمكن أن يكون قبل بداية الحملة (2026-05-01).',
                        ]),

                    Toggle::make('is_first_arrival')
                        ->label('من الأوائل'),
                ]),

            Section::make('حالة المخالفة')
                ->icon('heroicon-o-exclamation-triangle')
                ->schema([
                    Toggle::make('is_violator')
                        ->label('إلغاء تصنيف المخالفة')
                        ->helperText('عند الإلغاء سيعود الوكيل لتبويب ناديه ويُعالَج في الاستيراد التالي')
                        ->offIcon('heroicon-m-exclamation-triangle')
                        ->onIcon('heroicon-m-check-circle')
                        ->formatStateUsing(fn ($state) => ! $state),

                    \Filament\Forms\Components\Placeholder::make('violator_reason_view')
                        ->label('سبب المخالفة')
                        ->content(fn ($record) => $record?->violator_reason ?? '—'),

                    \Filament\Forms\Components\Placeholder::make('violator_since_view')
                        ->label('منذ')
                        ->content(fn ($record) => $record?->violator_since?->format('d/m/Y') ?? '—'),
                ])
                ->visible(fn ($record) => $record?->is_violator),

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

    public static function autoAssignClub(Set $set, Get $get): void
    {
        $increase      = (int) $get('transfer_count') + (int) $get('new_line_count');
        $transferCount = (int) $get('transfer_count');
        $club = Club::where('is_active', true)
            ->where('required_increase', '<=', max(0, $increase))
            ->where('required_transfer_count', '<=', max(0, $transferCount))
            ->orderByDesc('club_order')
            ->first();
        $set('current_club_id', $club?->club_id);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ── اسم الوكيل مع avatar + تاريخ الانضمام ──────────────────
                TextColumn::make('agent_name')
                    ->label('اسم الوكيل')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Agent $r): string => 'منذ ' . $r->created_at->diffForHumans())
                    ->formatStateUsing(function (Agent $record): string {
                        $initials = collect(explode(' ', $record->agent_name))
                            ->filter()
                            ->map(fn ($w) => mb_substr($w, 0, 1))
                            ->take(2)
                            ->join('');

                        $palette = [
                            '#3b82f6','#f59e0b','#a855f7','#10b981',
                            '#ef4444','#06b6d4','#ec4899','#8b5cf6',
                        ];
                        $color = $palette[crc32($record->agent_id) % count($palette)];

                        return <<<HTML
                            <div class="sc-agent-cell">
                                <div class="sc-agent-avatar" style="background:{$color}">{$initials}</div>
                                <div>
                                    <div class="sc-agent-name">{$record->agent_name}</div>
                                </div>
                            </div>
                        HTML;
                    })
                    ->html(),

                // ── رقم الجوال ───────────────────────────────────────────────
                TextColumn::make('phone')
                    ->label('الجوال')
                    ->searchable()
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── الموزع ───────────────────────────────────────────────────
                TextColumn::make('distributor.name')
                    ->label('الموزع')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->default('—')
                    ->toggleable(),

                // ── النادي — ألوان التصميم (أزرق/ذهبي/بنفسجي) ──────────────
                TextColumn::make('club.club_name')
                    ->label('النادي')
                    ->formatStateUsing(function ($state, Agent $record): string {
                        if (!$record->club) {
                            return '<span class="fi-badge sc-badge-outside rounded-full px-2 py-0.5 text-xs font-semibold">خارج الأندية</span>';
                        }
                        $order = (int) $record->club->club_order;
                        $cls = match ($order) {
                            1 => 'sc-badge-club-1',
                            2 => 'sc-badge-club-2',
                            3 => 'sc-badge-club-3',
                            default => 'sc-badge-outside',
                        };
                        $name = e($record->club->club_name);
                        return "<span class=\"fi-badge {$cls} rounded-full px-2 py-0.5 text-xs font-semibold\">{$name}</span>";
                    })
                    ->html()
                    ->sortable(false),

                // ── إجمالي الأرقام ───────────────────────────────────────────
                TextColumn::make('current_total')
                    ->label('إجمالي الأرقام')
                    ->sortable()
                    ->weight('bold'),

                // ── زيادة الحملة — growth badge أخضر ──────────────────────
                TextColumn::make('campaign_increase_display')
                    ->label('الزيادة')
                    ->getStateUsing(fn (Agent $record): int => $record->transfer_count + $record->new_line_count)
                    ->formatStateUsing(fn ($state): string => "<span class=\"sc-growth\">+{$state}</span>")
                    ->html()
                    ->sortable(false),

                // ── نسبة التحويل — progress bar + نسبة مئوية ──────────────
                TextColumn::make('transfer_percentage_display')
                    ->label('نسبة التحويل')
                    ->formatStateUsing(function ($state, Agent $record): string {
                        if (!$record->club) {
                            return '<span style="color:var(--sc-text3)">—</span>';
                        }
                        $required = (int) $record->club->required_increase;
                        if ($required === 0) {
                            return '<span style="color:var(--sc-text3)">0%</span>';
                        }
                        $pct    = min(100, round(($record->transfer_count / $required) * 100, 1));
                        $color  = $pct >= 60 ? 'var(--sc-green)' : 'var(--sc-red)';
                        return <<<HTML
                            <div class="sc-conv-cell">
                                <div class="sc-conv-bar">
                                    <div class="sc-conv-fill" style="width:{$pct}%;background:{$color}"></div>
                                </div>
                                <span class="sc-conv-text" style="color:{$color}">{$pct}%</span>
                            </div>
                        HTML;
                    })
                    ->html()
                    ->sortable(false),

                // ── أول وصول — نجمة ذهبية ────────────────────────────────
                TextColumn::make('is_first_arrival')
                    ->label('أوائل')
                    ->formatStateUsing(function ($state): string {
                        $cls = $state ? 'sc-star-yes' : 'sc-star-no';
                        $filled = $state ? 'currentColor' : 'none';
                        return <<<HTML
                            <span class="{$cls}" style="display:flex;justify-content:center">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="{$filled}"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            </span>
                        HTML;
                    })
                    ->html()
                    ->sortable(false),

                // ── مخالف — أيقونة تحذير حمراء ──────────────────────────────
                TextColumn::make('is_violator')
                    ->label('مخالف')
                    ->formatStateUsing(function ($state): string {
                        if (! $state) return '';
                        return <<<HTML
                            <span title="مخالف" style="color:var(--sc-red);display:flex;justify-content:center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </span>
                        HTML;
                    })
                    ->html()
                    ->sortable(false),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('بحث بالاسم...')
            ->filters([
                SelectFilter::make('current_club_id')
                    ->label('النادي')
                    ->options(Club::all()->pluck('club_name', 'club_id'))
                    ->placeholder('كل الأندية'),

                SelectFilter::make('distributor_id')
                    ->label('الموزع')
                    ->options(Distributor::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('كل الموزعين'),

                TernaryFilter::make('is_first_arrival')
                    ->label('من الأوائل'),

                TernaryFilter::make('is_violator')
                    ->label('مخالف'),
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
            \App\Filament\Resources\AgentResource\RelationManagers\HistoryLogsRelationManager::class,
            \App\Filament\Resources\AgentResource\RelationManagers\OpportunitiesRelationManager::class,
            \App\Filament\Resources\AgentResource\RelationManagers\RewardsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'view'   => Pages\ViewAgent::route('/{record}'),
            'edit'   => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
