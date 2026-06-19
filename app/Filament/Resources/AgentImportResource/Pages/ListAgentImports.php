<?php

namespace App\Filament\Resources\AgentImportResource\Pages;

use App\Filament\Resources\AgentImportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgentImports extends ListRecords
{
    protected static string $resource = AgentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('استيراد وكلاء جدد'),
        ];
    }
}
