<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->profileForm }}

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check">
                تحديث البيانات
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
