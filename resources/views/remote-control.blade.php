<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E2V - Control Remoto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
        .btn-start { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
        .btn-stop { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-blue-400">Panel de Emergencia</h1>
                <p class="text-slate-400">Control directo de cargadores</p>
            </div>
            <div class="bg-blue-500/20 px-4 py-2 rounded-full border border-blue-500/30 flex items-center gap-3">
                <span class="text-blue-400 font-mono text-sm">OPERADOR: {{ Session::get('remote_operator_name') }}</span>
                <a href="{{ route('remote.logout') }}" class="text-xs bg-red-500/20 hover:bg-red-500/40 text-red-400 px-2 py-1 rounded border border-red-500/30 transition-all">SALIR</a>
            </div>
        </header>

        @if(session('status'))
            <div class="mb-6 p-4 rounded-xl bg-blue-600/20 border border-blue-500/40 text-blue-100 flex items-center">
                <i class="fas fa-info-circle mr-3"></i> {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-6">
            @foreach($stations as $station)
                <div class="glass-card p-6 rounded-2xl shadow-xl">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h2 class="text-xl font-bold flex items-center">
                                <i class="fas fa-charging-station mr-3 text-blue-400"></i> {{ $station->label ?: $station->charge_box_id }}
                            </h2>
                            <p class="text-sm text-slate-400 font-mono">{{ $station->charge_box_id }}</p>
                        </div>
                        
                        <div class="flex flex-wrap gap-4 w-full md:w-auto">
                            <!-- CCS2 -->
                            <div class="flex-1 bg-slate-800/50 p-4 rounded-xl border border-slate-700">
                                <p class="text-xs font-bold text-blue-500 mb-2">CCS2</p>
                                <div class="flex gap-2">
                                    <form action="{{ route('remote.start', $station->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="connector_id" value="1">
                                        <button type="submit" class="btn-start px-4 py-2 rounded-lg font-bold text-sm shadow-lg hover:scale-105 transition-transform">START</button>
                                    </form>
                                    <form action="{{ route('remote.stop', $station->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="connector_id" value="1">
                                        <button type="submit" class="btn-stop px-4 py-2 rounded-lg font-bold text-sm shadow-lg hover:scale-105 transition-transform">STOP</button>
                                    </form>
                                </div>
                            </div>

                            <!-- GBT -->
                            <div class="flex-1 bg-slate-800/50 p-4 rounded-xl border border-slate-700">
                                <p class="text-xs font-bold text-green-500 mb-2">GBT</p>
                                <div class="flex gap-2">
                                    <form action="{{ route('remote.start', $station->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="connector_id" value="2">
                                        <button type="submit" class="btn-start px-4 py-2 rounded-lg font-bold text-sm shadow-lg hover:scale-105 transition-transform">START</button>
                                    </form>
                                    <form action="{{ route('remote.stop', $station->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="connector_id" value="2">
                                        <button type="submit" class="btn-stop px-4 py-2 rounded-lg font-bold text-sm shadow-lg hover:scale-105 transition-transform">STOP</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <footer class="mt-12 text-center text-slate-500 text-sm">
            &copy; 2026 ElectroPoint - Uso Exclusivo Administrativo
        </footer>
    </div>
</body>
</html>
