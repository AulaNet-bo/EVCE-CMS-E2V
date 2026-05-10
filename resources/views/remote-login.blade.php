<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E2V - Acceso Operador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full glass-card p-8 rounded-3xl shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-400 mb-2">ElectroPoint</h1>
            <p class="text-slate-400">Control Remoto de Emergencia</p>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-600/20 border border-red-500/40 text-red-200 text-sm flex items-center">
                <i class="fas fa-exclamation-triangle mr-3"></i> {{ session('error') }}
            </div>
        @endif

        <form action="/remote/login" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Usuario Operador</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="username" required 
                        class="block w-full pl-10 pr-3 py-3 bg-slate-800/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                        placeholder="Ingresa tu usuario">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Contraseña</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required 
                        class="block w-full pl-10 pr-3 py-3 bg-slate-800/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                        placeholder="••••••••">
                </div>
            </div>

            <button type="submit" 
                class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-lg shadow-blue-600/20 transition-all transform active:scale-95">
                ENTRAR AL PANEL
            </button>
        </form>

        <p class="mt-8 text-center text-xs text-slate-500">
            Acceso restringido. Cada operación es auditada.
        </p>
    </div>
</body>
</html>
