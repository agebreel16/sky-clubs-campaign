<?php

namespace App\Filament\Widgets;

use App\Models\DataImport;
use App\Filament\Resources\DataImportResource;
use Filament\Widgets\Widget;

class ImportStatusWidget extends Widget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.import-status-widget';

    /**
     * Hidden from main dashboard — belongs in the data import section only.
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getViewData(): array
    {
        $lastImport = DataImport::orderByDesc('created_at')->first();

        return ['import' => $lastImport];
    }
}
