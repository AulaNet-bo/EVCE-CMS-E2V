<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="loadReport">
            {{ $this->form }}
        </form>
    </x-filament::section>

    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        @if(!empty($data))
                            @foreach(array_keys($data[0]) as $column)
                                <th class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                    {{ str_replace('_', ' ', $column) }}
                                </th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($data as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            @foreach($row as $value)
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No hay datos disponibles para este reporte.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
