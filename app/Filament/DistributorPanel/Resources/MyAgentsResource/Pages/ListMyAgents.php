<?php

namespace App\Filament\DistributorPanel\Resources\MyAgentsResource\Pages;

use App\Filament\DistributorPanel\Resources\MyAgentsResource;
use Filament\Resources\Pages\ListRecords;

class ListMyAgents extends ListRecords
{
    protected static string $resource = MyAgentsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
