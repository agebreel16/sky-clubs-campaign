<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\Club;
use Filament\Resources\Pages\CreateRecord;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $increase      = (int) ($data['transfer_count'] ?? 0) + (int) ($data['new_line_count'] ?? 0);
        $transferCount = (int) ($data['transfer_count'] ?? 0);
        $club = Club::where('is_active', true)
            ->where('required_increase', '<=', max(0, $increase))
            ->where('required_transfer_count', '<=', max(0, $transferCount))
            ->orderByDesc('club_order')
            ->first();
        $data['current_club_id'] = $club?->club_id;
        return $data;
    }
}
