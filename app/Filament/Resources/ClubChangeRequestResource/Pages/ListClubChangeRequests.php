<?php

namespace App\Filament\Resources\ClubChangeRequestResource\Pages;

use App\Filament\Resources\ClubChangeRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListClubChangeRequests extends ListRecords
{
    protected static string $resource = ClubChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
