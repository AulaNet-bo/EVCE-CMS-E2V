<x-filament-panels::page>
    <div class="flex justify-between items-center mb-6 no-print">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Dispenser QR Codes</h2>
            <p class="text-gray-500">Generate and print QR codes for each charger connector.</p>
        </div>
        <x-filament::button 
            color="gray" 
            icon="heroicon-m-printer"
            onclick="window.print()"
        >
            Print All
        </x-filament::button>
    </div>

    <div class="mb-8 no-print">
        {{ $this->form }}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 qr-container">
        @foreach($this->connectors as $connector)
            @php
                $qrData = json_encode([
                    'charge_box_id' => $connector->station->charge_box_id,
                    'connector_id' => $connector->connector_id,
                    'type' => 'evce_dispenser'
                ]);
                $encodedData = urlencode($qrData);
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$encodedData}";
            @endphp
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col items-center text-center page-break-inside-avoid qr-card">
                <div class="mb-4 font-bold text-lg text-primary-600">
                    {{ $connector->station->name }}
                </div>
                
                <div class="bg-white p-2 rounded-lg mb-4 border border-gray-100 shadow-inner">
                    <img src="{{ $qrUrl }}" alt="QR Code" class="w-48 h-48">
                </div>

                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Connector #{{ $connector->connector_id }}
                </div>
                
                <div class="text-xs text-gray-400 font-mono break-all px-4">
                    {{ $connector->station->charge_box_id }}
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 w-full text-[10px] text-gray-400 uppercase tracking-widest">
                    EVCE Charging Network
                </div>
            </div>
        @endforeach
    </div>

    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, .no-print, .fi-panel-disable-blade-icon-components {
                display: none !important;
            }
            .fi-main-ctn {
                padding: 0 !important;
                margin: 0 !important;
            }
            .qr-container {
                display: block !important;
                width: 100% !important;
            }
            .qr-card {
                width: 45% !important;
                float: left !important;
                margin: 2% !important;
                border: 1px solid #eee !important;
                box-shadow: none !important;
                page-break-inside: avoid;
                color: black !important;
            }
            .qr-card img {
                filter: brightness(0);
            }
            body {
                background: white !important;
            }
        }
    </style>
</x-filament-panels::page>
