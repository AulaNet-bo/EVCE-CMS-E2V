<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button type="submit" size="lg">
                Process Batch
            </x-filament::button>
            <x-filament::button color="gray" tag="a" :href="App\Filament\Resources\RfidTagResource::getUrl('index')">
                Cancel
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>