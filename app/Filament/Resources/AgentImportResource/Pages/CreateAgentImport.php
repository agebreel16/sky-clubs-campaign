<?php

namespace App\Filament\Resources\AgentImportResource\Pages;

use App\Filament\Resources\AgentImportResource;
use App\Jobs\ProcessAgentImport;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAgentImport extends CreateRecord
{
    protected static string $resource = AgentImportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status']      = 'pending';
        $data['imported_by'] = Auth::id();
        return $data;
    }

    protected function afterCreate(): void
    {
        ProcessAgentImport::dispatch($this->record);

        Notification::make()
            ->title('بدأ استيراد الوكلاء')
            ->body('يتم معالجة البيانات في الخلفية. ستظهر النتائج فور الانتهاء.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
