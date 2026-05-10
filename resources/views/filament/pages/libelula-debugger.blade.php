<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" color="primary">
                Enviar a Libélula
            </x-filament::button>
        </div>
    </form>

    @if($lastRequest)
        <div class="mt-8 space-y-6">
            <x-filament::section>
                <x-slot name="heading">Payload Enviado a Libélula</x-slot>
                <div class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto font-mono text-sm">
                    <pre>{{ json_encode($lastRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Respuesta de Libélula (HTTP Status: {{ $lastResponse['http_status'] }})
                </x-slot>
                <div class="bg-gray-900 text-{{ $lastResponse['http_status'] == 200 ? 'green' : 'red' }}-400 p-4 rounded overflow-x-auto font-mono text-sm">
                    <pre>{{ json_encode($lastResponse['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
