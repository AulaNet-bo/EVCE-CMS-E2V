<x-filament-panels::page>
@php
    $columnTranslations = [
        'sap_client_code' => 'Código SAP',
        'name' => 'Nombre',
        'email' => 'Correo',
        'razon_social' => 'Razón Social',
        'nit_ci' => 'NIT/CI',
        'doc_type' => 'Tipo Doc',
        'company_id' => 'ID Empresa',
        'company_name' => 'Empresa',
        'is_admin' => 'Es Admin',
        'balance' => 'Saldo',
        'synced_at' => 'Sincronizado SAP',
        'customer_name' => 'Cliente',
        'nit' => 'NIT',
        'date' => 'Fecha',
        'amount' => 'Monto',
        'currency' => 'Moneda',
        'payment_method' => 'Método Pago',
        'bank_receipt' => 'Recibo',
        'pos_correlative' => 'Correlativo POS',
        'external_id_pos' => 'ID Externo',
        'internal_ref' => 'Ref. Interna',
        'transaction_type_label' => 'Tipo Tx',
        'rfid_tag' => 'Tag RFID',
        'invoice_number' => 'Nro Factura',
        'invoice_url' => 'Link Factura',
        'payment_evidence_path' => 'Evidencia',
        'status' => 'Estado',
        'station' => 'Estación',
        'start_time' => 'Inicio',
        'end_time' => 'Fin',
        'energy_kwh' => 'Energía kWh',
        'item_code' => 'Cod. Item',
        'item_description' => 'Desc. Item',
        'price_unit' => 'Precio Unit.',
        'total_amount' => 'Monto Total',
        'rfid_tag_id' => 'ID RFID',
        'session_fee' => 'Tarifa Fija',
        'time_fee' => 'Tarifa Tiempo',
        'energy_cost' => 'Costo Energía',
        'utility_cost' => 'Costo Utilidad',
        'margin' => 'Margen',
    ];
@endphp
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
                                    {{ mb_strtoupper($columnTranslations[$column] ?? str_replace('_', ' ', $column)) }}
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
