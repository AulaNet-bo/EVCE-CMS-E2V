<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::card>
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-primary-100 rounded-lg text-primary-600">
                    <x-heroicon-o-clock class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">Última Actividad</p>
                    <p class="text-lg font-bold">{{ $this->getSyncStatus()['last_monitor'] }}</p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-green-100 rounded-lg text-green-600">
                    <x-heroicon-o-bolt class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">Cargas Activas (CMS)</p>
                    <p class="text-lg font-bold">{{ $this->getSyncStatus()['active_sessions_cms'] }}</p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-yellow-100 rounded-lg text-yellow-600">
                    <x-heroicon-o-arrow-path class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">En Proceso de Inicio</p>
                    <p class="text-lg font-bold">{{ $this->getSyncStatus()['starting_sessions_cms'] }}</p>
                </div>
            </div>
        </x-filament::card>
    </div>

    <x-filament::card>
        <div class="mb-4">
            <h3 class="text-lg font-medium">Últimas Transacciones en SteVe</h3>
            <p class="text-sm text-gray-500">Muestra la realidad de la base de datos de SteVe y su vínculo con el CMS.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3 border-b">TX ID</th>
                        <th class="px-4 py-3 border-b">Tag RFID</th>
                        <th class="px-4 py-3 border-b">Inicio (SteVe)</th>
                        <th class="px-4 py-3 border-b">Estado CMS</th>
                        <th class="px-4 py-3 border-b">Vínculo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getSteveTransactions() as $tx)
                        <tr class="hover:bg-gray-50 border-b">
                            <td class="px-4 py-3 font-mono text-sm">#{{ $tx['tx_id'] }}</td>
                            <td class="px-4 py-3 font-mono text-sm">{{ $tx['tag'] }}</td>
                            <td class="px-4 py-3 text-sm">{{ $tx['start'] }}</td>
                            <td class="px-4 py-3">
                                @if($tx['linked'])
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                        {{ $tx['cms_status'] }} (ID: {{ $tx['cms_id'] }})
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                        No vinculado
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($tx['collision'])
                                    <div class="flex items-center text-red-600 space-x-1" title="Se detectó una colisión con una sesión antigua. El motor ignoró el registro viejo.">
                                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                                        <span class="text-xs font-bold">COLISIÓN</span>
                                    </div>
                                @elseif($tx['linked'])
                                    <div class="flex items-center text-green-600 space-x-1">
                                        <x-heroicon-o-check-circle class="w-5 h-5" />
                                        <span class="text-xs">OK</span>
                                    </div>
                                @else
                                    <div class="flex items-center text-gray-400 space-x-1">
                                        <x-heroicon-o-question-mark-circle class="w-5 h-5" />
                                        <span class="text-xs">Pendiente / Externo</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::card>
</x-filament-panels::page>
