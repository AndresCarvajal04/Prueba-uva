<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Gestión Logística') — UVA3 Corporativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col">

    <!-- ENCABEZADO CORPORATIVO SUPERIOR -->
    <header class="bg-slate-900 text-white sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <a href="{{ route('pedidos.index') }}" class="flex items-center gap-3.5 group">
                <div class="w-9 h-9 rounded-md bg-blue-700 group-hover:bg-blue-600 transition flex items-center justify-center font-bold text-sm tracking-wider border border-blue-500/40">
                    UVA3
                </div>
                <div class="border-l border-slate-700 pl-3.5">
                    <h1 class="font-semibold text-sm sm:text-base text-white tracking-tight leading-tight">
                        Sistema de Gestión Logística y Despachos Nacionales
                    </h1>
                    <p class="text-[11px] text-slate-400">
                        Plataforma Corporativa de Órdenes, Clientes y Facturación Mensual
                    </p>
                </div>
            </a>

            <!-- MENÚ DESPLEGABLE DE PERFIL (EMPLEADO OPERATIVO / ADMINISTRADOR GENERAL) -->
            <div class="flex items-center gap-3">
                <form action="{{ route('pedidos.cambiar-rol') }}" method="POST" class="flex items-center gap-2.5 bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-md">
                    @csrf
                    <label for="select-perfil-activo" class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 whitespace-nowrap">
                        Perfil en Sesión:
                    </label>
                    <select name="rol" id="select-perfil-activo" onchange="this.form.submit()"
                            class="bg-slate-900 text-xs font-semibold rounded px-2.5 py-1 border focus:outline-none cursor-pointer {{ ($esAdmin ?? false) ? 'text-amber-300 border-amber-500/50' : 'text-sky-300 border-slate-600' }}">
                        <option value="empleado" @selected(!($esAdmin ?? false))>Empleado Operativo</option>
                        <option value="admin" @selected($esAdmin ?? false)>Administrador General</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- BARRA DE NAVEGACIÓN POR SECCIONES -->
        <nav class="bg-slate-800/95 border-t border-slate-700/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between overflow-x-auto">
                <div class="flex items-center space-x-1 py-1.5">
                    @php
                        $navItems = [
                            ['id' => 'ordenes', 'route' => 'pedidos.index', 'label' => '1. Órdenes de Pedido', 'badge' => $conteosEstados['todos'] ?? null],
                            ['id' => 'crear', 'route' => 'pedidos.crear', 'label' => '2. Registrar Orden', 'badge' => null],
                            ['id' => 'clientes', 'route' => 'clientes.index', 'label' => '3. Maestro de Clientes', 'badge' => null],
                            ['id' => 'reportes', 'route' => 'reportes.ventas', 'label' => '4. Ventas del Mes', 'badge' => null],
                            ['id' => 'monitor', 'route' => 'pedidos.monitor', 'label' => '5. Tablero Dinámico', 'badge' => null],
                            ['id' => 'guia', 'route' => 'pedidos.guia', 'label' => '6. Flujo e Instrucciones', 'badge' => null],
                        ];
                        $actual = $seccionActiva ?? 'ordenes';
                    @endphp

                    @foreach($navItems as $item)
                        <a href="{{ route($item['route']) }}"
                           class="px-3.5 py-2 rounded-md text-xs font-semibold whitespace-nowrap transition flex items-center gap-2 {{ $actual === $item['id'] ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' }}">
                            <span>{{ $item['label'] }}</span>
                            @if($item['badge'] !== null)
                                <span class="px-1.5 py-0.5 text-[10px] rounded bg-slate-900/60 text-slate-200 tabular-nums">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="hidden lg:flex items-center gap-2 text-[11px] py-1.5 pl-4">
                    @if($esAdmin ?? false)
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span class="text-amber-200 font-medium">Nivel de Acceso: Administrador (Edición Total Habilitada)</span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                        <span class="text-slate-300 font-medium">Nivel de Acceso: Empleado (Órdenes Entregadas Protegidas)</span>
                    @endif
                </div>
            </div>
        </nav>
    </header>

    <!-- CONTENIDO DE LA SECCIÓN ACTIVA -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 flex-1 w-full">

        <!-- ALERTAS DE OPERACIÓN ESTANDARIZADAS -->
        @if(session('success'))
            <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-950 flex items-center justify-between text-xs font-medium shadow-sm">
                <div>
                    <strong class="font-bold uppercase tracking-wide text-emerald-800 mr-1">Confirmación:</strong>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-lg bg-red-50 border border-red-300 text-red-950 flex items-center justify-between text-xs font-medium shadow-sm">
                <div>
                    <strong class="font-bold uppercase tracking-wide text-red-800 mr-1">Control de Acceso:</strong>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-lg bg-red-50 border border-red-300 text-red-900 text-xs space-y-1">
                <div class="font-bold uppercase tracking-wide">Verifique los campos del formulario:</div>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- PIE DE PÁGINA CORPORATIVO -->
    <footer class="bg-white border-t border-slate-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between text-xs text-slate-500">
            <span>UVA3 Logística Nacional · Sistema Corporativo de Gestión de Pedidos</span>
            <span>Operación Nacional Estandarizada · Perfil activo: <strong>{{ ($esAdmin ?? false) ? 'Administrador General' : 'Empleado Operativo' }}</strong></span>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
