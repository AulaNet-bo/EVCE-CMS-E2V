<x-filament-panels::page>
    <div class="space-y-10 pb-10">
        {{-- Hero Header --}}
        <div class="p-8 bg-gray-900 rounded-xl text-white shadow-lg border border-white/10">
            <h1 class="text-3xl font-bold mb-2">Manual Maestro Electro Point</h1>
            <p class="text-gray-400">
                Guía oficial para la administración, monitoreo y conciliación financiera del ecosistema EVCE.
            </p>
        </div>

        {{-- Section 1: Infraestructura --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-wrench-screwdriver class="w-6 h-6 text-primary-500" />
                    <span>Módulo de Infraestructura</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div class="prose dark:prose-invert max-w-none">
                    <h3 class="text-lg font-bold">Estaciones y Ubicaciones</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Las <strong>Estaciones</strong> representan el hardware físico. Cada una debe estar vinculada a una <strong>Ubicación</strong> para ser visible en la App.
                    </p>
                    <ul class="text-sm space-y-2 mt-4">
                        <li><strong>Charge Box ID:</strong> ID único OCPP del cargador.</li>
                        <li><strong>Conectores:</strong> Supervisión en tiempo real de mangueras (Libre, Cargando, Falla).</li>
                    </ul>
                </div>
                <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-white/10 shadow-sm bg-gray-50 dark:bg-gray-800">
                    <img src="{{ asset('images/manual/estaciones.png') }}" alt="Estaciones" class="w-full">
                </div>
            </div>
        </x-filament::section>

        {{-- Section 2: Operaciones --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user-group class="w-6 h-6 text-primary-500" />
                    <span>Operaciones y Clientes</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-white/10 shadow-sm bg-gray-50 dark:bg-gray-800 order-2 lg:order-1">
                    <img src="{{ asset('images/manual/tarjetas_rfid.png') }}" alt="RFID" class="w-full">
                </div>
                <div class="prose dark:prose-invert max-w-none order-1 lg:order-2">
                    <h3 class="text-lg font-bold">Clientes y Tarjetas RFID</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Gestión de usuarios de la App y sus <strong>Tarjetas RFID</strong>.
                    </p>
                    <div class="mt-4 p-4 bg-primary-50 dark:bg-primary-950/30 rounded-lg border border-primary-100 dark:border-primary-900/50">
                        <h4 class="font-bold text-sm mb-1">Recarga Manual</h4>
                        <p class="text-xs">Usa esta opción para pagos en efectivo. Permite emitir facturas SIAT vía Libélula instantáneamente.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Section 3: Finanzas --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-banknotes class="w-6 h-6 text-primary-500" />
                    <span>Finanzas & Integración SAP</span>
                </div>
            </x-slot>

            <div class="space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <h3 class="font-bold">Transacciones</h3>
                        <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-white/10 shadow-sm">
                            <img src="{{ asset('images/manual/transacciones.png') }}" alt="Transacciones" class="w-full">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h3 class="font-bold">Reporte SAP</h3>
                        <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-white/10 shadow-sm">
                            <img src="{{ asset('images/manual/reporte_sap.png') }}" alt="SAP" class="w-full">
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-white/5 p-6 rounded-xl border border-gray-100 dark:border-white/10">
                    <h3 class="font-bold mb-4 flex items-center gap-2">
                        <x-heroicon-o-code-bracket class="w-5 h-5 text-gray-400" />
                        Integración API SAP
                    </h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <p>Endpoint: <code>/api/v1/sap/export</code></p>
                        <p>Los reportes exportan llaves en inglés (standard) pero se visualizan en español en el CMS para comodidad del operador.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <div class="text-center opacity-40 py-10">
            <p class="text-xs">Documentación Oficial Electro Point - Actualizado {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>
</x-filament-panels::page>
