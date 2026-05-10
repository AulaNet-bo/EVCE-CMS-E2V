<x-filament-panels::page>
    <div class="space-y-12 pb-12">
        {{-- Hero Header --}}
        <div class="relative overflow-hidden bg-primary-600 rounded-3xl p-10 text-white shadow-2xl">
            <div class="relative z-10 max-w-3xl">
                <h1 class="text-4xl font-extrabold tracking-tight mb-4">Manual Maestro Electro Point</h1>
                <p class="text-xl text-primary-100 leading-relaxed">
                    Guía oficial para la administración, monitoreo y conciliación financiera del ecosistema EVCE. 
                    Optimiza la operación de tu red de carga con este manual detallado.
                </p>
            </div>
            <div class="absolute -right-20 -bottom-20 opacity-10">
                <x-heroicon-o-book-open class="w-80 h-80" />
            </div>
        </div>

        {{-- Navigation Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="#infraestructura" class="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm hover:shadow-md transition-all group">
                <x-heroicon-o-bolt class="w-8 h-8 text-primary-500 mb-4 group-hover:scale-110 transition-transform" />
                <h3 class="font-bold text-lg">Infraestructura</h3>
                <p class="text-sm text-gray-500 mt-1">Cargadores, estaciones y conectores.</p>
            </a>
            <a href="#operaciones" class="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm hover:shadow-md transition-all group">
                <x-heroicon-o-credit-card class="w-8 h-8 text-primary-500 mb-4 group-hover:scale-110 transition-transform" />
                <h3 class="font-bold text-lg">Operaciones</h3>
                <p class="text-sm text-gray-500 mt-1">Usuarios, tarjetas RFID y recargas.</p>
            </a>
            <a href="#finanzas" class="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm hover:shadow-md transition-all group">
                <x-heroicon-o-banknotes class="w-8 h-8 text-primary-500 mb-4 group-hover:scale-110 transition-transform" />
                <h3 class="font-bold text-lg">Finanzas & SAP</h3>
                <p class="text-sm text-gray-500 mt-1">Facturación y exportación contable.</p>
            </a>
        </div>

        {{-- Section 1: Infraestructura --}}
        <div id="infraestructura" class="space-y-6 scroll-mt-10">
            <h2 class="text-2xl font-bold flex items-center gap-3 border-b border-gray-100 dark:border-white/5 pb-4">
                <x-heroicon-o-wrench-screwdriver class="w-7 h-7 text-primary-500" />
                Módulo de Infraestructura
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-bold mb-2">Estaciones y Ubicaciones</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                            Las <strong>Estaciones</strong> representan el hardware físico instalado. Cada una debe estar vinculada a una <strong>Ubicación</strong> (punto geográfico) para ser visible en la App.
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-white/5 p-5 rounded-2xl">
                        <ul class="space-y-3 text-sm">
                            <li class="flex gap-2">
                                <span class="text-primary-500 font-bold">•</span>
                                <span><strong>Charge Box ID:</strong> Es el identificador único OCPP del cargador. No debe cambiarse una vez configurado.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-primary-500 font-bold">•</span>
                                <span><strong>Conectores:</strong> Cada cargador puede tener múltiples conectores (Mangueras). El sistema supervisa si están libres, cargando o en falla.</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="rounded-3xl overflow-hidden border border-gray-200 dark:border-white/10 shadow-2xl">
                    <img src="{{ asset('images/manual/estaciones.png') }}" alt="Estaciones" class="w-full">
                </div>
            </div>
        </div>

        {{-- Section 2: Operaciones y Usuarios --}}
        <div id="operaciones" class="space-y-6 scroll-mt-10">
            <h2 class="text-2xl font-bold flex items-center gap-3 border-b border-gray-100 dark:border-white/5 pb-4">
                <x-heroicon-o-user-group class="w-7 h-7 text-primary-500" />
                Operaciones y Gestión de Clientes
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="rounded-3xl overflow-hidden border border-gray-200 dark:border-white/10 shadow-2xl order-2 lg:order-1">
                    <img src="{{ asset('images/manual/tarjetas_rfid.png') }}" alt="RFID" class="w-full">
                </div>
                <div class="space-y-6 order-1 lg:order-2">
                    <div>
                        <h3 class="text-lg font-bold mb-2">Clientes y Tarjetas RFID</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                            El sistema gestiona dos tipos de usuarios: los de la App móvil (Clientes) y los administradores del CMS. 
                            Las <strong>Tarjetas RFID</strong> se vinculan a un usuario para permitirle cargar sin usar la App.
                        </p>
                    </div>
                    <div class="p-5 bg-primary-50 dark:bg-primary-900/10 rounded-2xl border border-primary-100 dark:border-primary-800/20">
                        <h4 class="font-bold text-primary-800 dark:text-primary-400 text-sm mb-2">Recarga Manual (Caja)</h4>
                        <p class="text-xs text-primary-700 dark:text-primary-500 leading-relaxed">
                            Para clientes que pagan en efectivo en sucursal:
                            <br>1. Busca la tarjeta o billetera del cliente.
                            <br>2. Haz clic en <strong>Recarga Manual</strong>.
                            <br>3. Ingresa el monto y selecciona si deseas emitir factura SIAT vía Libélula.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Finanzas --}}
        <div id="finanzas" class="space-y-6 scroll-mt-10">
            <h2 class="text-2xl font-bold flex items-center gap-3 border-b border-gray-100 dark:border-white/5 pb-4">
                <x-heroicon-o-calculator class="w-7 h-7 text-primary-500" />
                Administración Financiera y SAP
            </h2>
            
            <div class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold">Transacciones y Validaciones</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                            Todas las recargas y consumos generan una <strong>Transacción</strong>. En el listado podrás ver el estado (PENDIENTE, COMPLETADO, FALLIDO) y los enlaces de pago o facturas asociadas.
                        </p>
                        <div class="rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 shadow-lg">
                            <img src="{{ asset('images/manual/transacciones.png') }}" alt="Transacciones" class="w-full">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold">Exportación Contable (SAP)</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                            El módulo SAP extrae los datos necesarios para asientos contables. Los reportes están divididos en Clientes, Pagos y Sesiones.
                        </p>
                        <div class="rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 shadow-lg">
                            <img src="{{ asset('images/manual/reporte_sap.png') }}" alt="SAP Report" class="w-full">
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 rounded-3xl p-8 text-white">
                    <h3 class="text-xl font-bold mb-6 flex items-center gap-3">
                        <x-heroicon-o-code-bracket class="w-6 h-6 text-primary-400" />
                        Integración Técnica (API)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <h4 class="text-primary-400 font-bold text-sm uppercase">Reportes Externos</h4>
                            <p class="text-xs text-gray-400 leading-relaxed">
                                El endpoint <code>/api/v1/sap/export</code> provee JSON con llaves estándar en inglés para integraciones automáticas. 
                                <br><br>Campos nuevos incluidos: Desglose de costos (energía, utilidad, margen), ID de empresa y enlaces de factura.
                            </p>
                        </div>
                        <div class="space-y-3 border-l border-white/10 pl-8">
                            <h4 class="text-primary-400 font-bold text-sm uppercase">Webhooks de Pago</h4>
                            <p class="text-xs text-gray-400 leading-relaxed">
                                El sistema recibe notificaciones de Libélula automáticamente. Si un pago no se acredita, revisa el <strong>Libélula Debugger</strong> para ver el log de errores.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center pt-10 border-t border-gray-100 dark:border-white/5 opacity-50">
            <p class="text-xs">Documentación Oficial Electro Point - Versión 2.5 - Actualizado {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>
</x-filament-panels::page>
