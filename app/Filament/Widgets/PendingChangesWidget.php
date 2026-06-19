<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ClubChangeRequestResource;
use App\Models\ClubChangeRequest;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingChangesWidget extends TableWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'طلبات تغيير النادي المعلّقة';

    public static function canView(): bool
    {
        return ClubChangeRequest::where('status', 'pending')->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClubChangeRequest::query()
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('agent.agent_name')
                    ->label('الوكيل')
                    ->searchable(false),

                TextColumn::make('change_type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn ($state) => $state === 'promotion' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 'promotion' ? '↑ ترقية' : '↓ تهبيط'),

                TextColumn::make('fromClub.club_name')
                    ->label('من')
                    ->default('خارج الأندية'),

                TextColumn::make('toClub.club_name')
                    ->label('إلى')
                    ->default('خارج الأندية'),

                TextColumn::make('created_at')
                    ->label('منذ')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('view_all')
                    ->label('مراجعة الكل')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(ClubChangeRequestResource::getUrl()),
            ])
            ->paginated(false);
    }
}
