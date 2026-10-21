<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>UVA3 Logística Nacional — Sistema Corporativo de Gestión de Pedidos y Despachos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
    </style>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col">

    <!-- ENCABEZADO CORPORATIVO PRINCIPAL -->
    <header class="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-9 h-9 rounded-md bg-blue-700 flex items-center justify-center font-bold text-sm tracking-wider border border-blue-500/40">
                    UVA3
                </div>
                <div class="border-l border-slate-700 pl-3.5">
                    <h1 class="font-semibold text-sm sm:text-base text-white tracking-tight leading-tight">
                        Sistema de Gestión Logística y Despachos Nacionales
                    </h1>
                    <p class="text-[11px] text-slate-400">
                        Control de órdenes, liquidación automática de fletes y consolidado comercial
                    </p>
                </div>
            </div>

            <!-- CONTROLES DE SESIÓN Y MENÚ DESPLEGABLE DE PERFIL (ADMINISTRADOR / EMPLEADO) -->
            <div class="flex items-center gap-4">
                <a href="/api/pedidos?estado=pendiente" target="_blank"
                   class="hidden md:inline-flex items-center gap-1.5 text-xs font-mono text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 px-3 py-1.5 rounded-md transition">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>API JSON</span>
                </a>

                <form action="{{ route('pedidos.cambiar-rol') }}" method="POST" id="form-selector-perfil" class="flex items-center gap-2.5 bg-slate-800/90 border border-slate-700 px-3 py-1.5 rounded-md">
                    @csrf
                    <label for="select-perfil-activo" class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 whitespace-nowrap">
                        Perfil Activo:
                    </label>
                    <select name="rol" id="select-perfil-activo" onchange="this.form.submit()"
                            class="bg-slate-900 text-xs font-semibold rounded px-2.5 py-1 border focus:outline-none cursor-pointer {{ $esAdmin ? 'text-amber-300 border-amber-500/50' : 'text-sky-300 border-slate-600' }}">
                        <option value="empleado" @selected(!$esAdmin)>Empleado Operativo</option>
                        <option value="admin" @selected($esAdmin)>Administrador General</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- BARRA DE ESTADO DEL PERFIL ACTIVO -->
        <div class="{{ $esAdmin ? 'bg-amber-950/90 border-t border-amber-800/60 text-amber-100' : 'bg-slate-800/90 border-t border-slate-700/80 text-slate-300' }}">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-1.5 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-2">
                    @if($esAdmin)
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>
                            <strong>Modo Administrador General activo:</strong> Tiene acceso total al CRUD de órdenes y clientes. Puede editar, recotizar o eliminar cualquier pedido (incluidos los pedidos cerrados en estado <em>Entregado</em>).
                        </span>
                    @else
                        <span class="inline-block w-2 h-2 rounded-full bg-sky-400"></span>
                        <span>
                            <strong>Modo Empleado Operativo activo:</strong> Puede registrar pedidos y avanzar estados de despacho. Las órdenes en estado <em>Entregado</em> se encuentran bloqueadas por seguridad.
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-4 text-[11px]">
                    <a href="#seccion-flujo" class="hover:underline">Flujo Operativo</a>
                    <a href="#seccion-ordenes" class="hover:underline">Órdenes de Pedido</a>
                    <a href="#seccion-clientes" class="hover:underline">Maestro de Clientes</a>
                    <a href="#seccion-reporte" class="hover:underline">Consolidado Mensual</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 flex-1 w-full">

        <!-- FLUJO OPERATIVO PASO A PASO -->
        <section id="seccion-flujo" class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-4 mb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                        Flujo de Operación del Sistema Logístico
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Siga esta secuencia de cuatro etapas para operar el ciclo completo de un pedido desde su recepción hasta el cierre contable.
                    </p>
                </div>
                <span class="text-xs font-medium px-2.5 py-1 rounded bg-slate-100 text-slate-700 border border-slate-200">
                    Perfil en sesión: <strong>{{ $esAdmin ? 'Administrador General' : 'Empleado Operativo' }}</strong>
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Paso 1 -->
                <a href="#seccion-clientes" class="group block p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-blue-600 transition">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">
                            Paso 1
                        </span>
                        <span class="text-xs text-slate-400 group-hover:text-blue-600 font-medium">Ir a Clientes →</span>
                    </div>
                    <h3 class="text-xs font-bold text-slate-900">Verificación de Cliente</h3>
                    <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                        Seleccione un cliente activo o regístrelo en el directorio con su NIT y ciudad principal de destino.
                    </p>
                </a>

                <!-- Paso 2 -->
                <a href="#formulario-nuevo-pedido" class="group block p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-blue-600 transition">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">
                            Paso 2
                        </span>
                        <span class="text-xs text-slate-400 group-hover:text-blue-600 font-medium">Crear Orden →</span>
                    </div>
                    <h3 class="text-xs font-bold text-slate-900">Registro de Orden de Pedido</h3>
                    <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                        Ingrese el valor comercial, peso en kilogramos y ciudad de entrega. La orden inicia en estado <strong>Pendiente</strong>.
                    </p>
                </a>

                <!-- Paso 3 -->
                <a href="#seccion-ordenes" class="group block p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-blue-600 transition">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">
                            Paso 3
                        </span>
                        <span class="text-xs text-slate-400 group-hover:text-blue-600 font-medium">Gestionar Despacho →</span>
                    </div>
                    <h3 class="text-xs font-bold text-slate-900">Despacho y Cotización de Flete</h3>
                    <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                        Al cambiar el estado a <strong>Despachado</strong>, el sistema consulta la API de la transportadora y liquida el costo de envío.
                    </p>
                </a>

                <!-- Paso 4 -->
                <a href="#seccion-reporte" class="group block p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-blue-600 transition">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">
                            Paso 4
                        </span>
                        <span class="text-xs text-slate-400 group-hover:text-blue-600 font-medium">Ver Consolidado →</span>
                    </div>
                    <h3 class="text-xs font-bold text-slate-900">Entrega, Auditoría y Reporte</h3>
                    <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                        Al marcar <strong>Entregado</strong> el pedido queda cerrado para empleados. El <strong>Administrador</strong> puede auditar o editar cualquier orden.
                    </p>
                </a>
            </div>
        </section>

        <!-- NOTIFICACIONES DEL SISTEMA -->
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
                    <strong class="font-bold uppercase tracking-wide text-red-800 mr-1">Restricción de Seguridad:</strong>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-lg bg-red-50 border border-red-300 text-red-900 text-xs space-y-1">
                <div class="font-bold uppercase tracking-wide">Verifique los datos ingresados:</div>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- INDICADORES EJECUTIVOS (KPIs) -->
        <section class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Órdenes Registradas</span>
                <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $conteosEstados['todos'] }}</p>
                <span class="text-[11px] text-slate-400">Total en base de datos</span>
            </div>
            <div class="bg-white rounded-lg p-4 border border-slate-200 border-l-4 border-l-amber-500 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-600 uppercase tracking-wider">En Alistamiento</span>
                <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $conteosEstados['pendiente'] }}</p>
                <span class="text-[11px] text-amber-700 font-medium">Estado: Pendiente</span>
            </div>
            <div class="bg-white rounded-lg p-4 border border-slate-200 border-l-4 border-l-blue-600 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-600 uppercase tracking-wider">En Tránsito Nacional</span>
                <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $conteosEstados['despachado'] }}</p>
                <span class="text-[11px] text-blue-700 font-medium">Estado: Despachado</span>
            </div>
            <div class="bg-white rounded-lg p-4 border border-slate-200 border-l-4 border-l-emerald-600 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-600 uppercase tracking-wider">Órdenes Cerradas</span>
                <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $conteosEstados['entregado'] }}</p>
                <span class="text-[11px] text-emerald-700 font-medium">Estado: Entregado</span>
            </div>
            <div class="col-span-2 sm:col-span-1 bg-slate-900 text-white rounded-lg p-4 border border-slate-800 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Facturación del Mes</span>
                <p class="text-xl font-bold text-white mt-1 tabular-nums">
                    ${{ number_format($reporteMensual->sum('total_vendido'), 0, ',', '.') }}
                </p>
                <span class="text-[11px] text-slate-400 capitalize">{{ $mesActualNombre }}</span>
            </div>
        </section>

        <!-- PANEL DE EDICIÓN Y AUDITORÍA CRUD DE ORDEN (ADMINISTRADOR / EDICIÓN AUTORIZADA) -->
        @if($pedidoEditar)
            <section id="panel-edicion-orden" class="bg-white border-2 border-blue-700 rounded-lg p-6 shadow-md">
                <div class="flex flex-wrap items-center justify-between gap-2 pb-4 mb-5 border-b border-slate-200">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded">
                            Módulo de Edición y Auditoría de Orden #{{ $pedidoEditar->id }}
                        </span>
                        <h2 class="text-base font-bold text-slate-900 mt-1.5">
                            Modificar Orden de Pedido — {{ $pedidoEditar->cliente?->nombre }}
                        </h2>
                        <p class="text-xs text-slate-500">
                            Actualice los datos comerciales, logísticos o el estado de la orden.
                        </p>
                    </div>
                    <a href="{{ route('pedidos.index', ['estado' => $estadoFiltro]) }}"
                       class="text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3.5 py-2 rounded-md border border-slate-300">
                        Cerrar Editor
                    </a>
                </div>

                @if($pedidoEditar->estaEntregado() && !$esAdmin)
                    <div class="p-4 bg-red-50 border border-red-200 text-red-900 rounded-md text-xs flex flex-wrap items-center justify-between gap-4">
                        <div>
                            Esta orden se encuentra cerrada en estado <strong>ENTREGADO</strong>. El perfil de <strong>Empleado Operativo</strong> no tiene permisos para modificar órdenes entregadas.
                            Seleccione <strong>Administrador General</strong> en el menú desplegable superior para habilitar la edición.
                        </div>
                    </div>
                @else
                    <form action="{{ route('pedidos.update', $pedidoEditar) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="filtro_actual" value="{{ $estadoFiltro }}">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cliente Titular</label>
                            <select name="cliente_id" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs">
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected($pedidoEditar->cliente_id === $cliente->id)>
                                        {{ $cliente->nombre }} ({{ $cliente->ciudad }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha de Orden</label>
                            <input type="date" name="fecha" required
                                   value="{{ $pedidoEditar->fecha?->format('Y-m-d') }}"
                                   class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Estado Operativo</label>
                            <select name="estado" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">
                                @foreach(\App\Models\Pedido::ESTADOS as $est)
                                    <option value="{{ $est }}" @selected($pedidoEditar->estado === $est)>
                                        {{ ucfirst($est) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Valor Comercial ($)</label>
                            <input type="number" step="0.01" min="0" name="total" required
                                   value="{{ $pedidoEditar->total }}"
                                   class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Peso Bruto (kg)</label>
                            <input type="number" step="0.1" min="0.1" name="peso_kg" required
                                   value="{{ $pedidoEditar->peso_kg }}"
                                   class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad de Destino</label>
                            <input type="text" name="ciudad_destino" required
                                   value="{{ $pedidoEditar->ciudad_destino }}"
                                   class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Costo de Envío ($)</label>
                            <input type="number" step="0.01" min="0" name="costo_envio"
                                   value="{{ $pedidoEditar->costo_envio }}"
                                   placeholder="Dejar vacío para cotizar automáticamente"
                                   class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Observaciones de Despacho</label>
                            <input type="text" name="notas"
                                   value="{{ $pedidoEditar->notas }}"
                                   class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs">
                        </div>

                        <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-slate-100">
                            <label class="inline-flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                <input type="checkbox" name="forzar_recotizacion" value="1" class="rounded border-slate-300">
                                <span>Recalcular tarifa automáticamente con la transportadora si el estado es <strong>Despachado</strong></span>
                            </label>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('pedidos.index', ['estado' => $estadoFiltro]) }}"
                                   class="px-4 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-blue-700 hover:bg-blue-600 text-white font-semibold px-5 py-2 rounded-md text-xs shadow-sm">
                                    Guardar Cambios en la Orden #{{ $pedidoEditar->id }}
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </section>
        @endif

        <!-- CUERPO CENTRAL: IZQUIERDA (GESTIÓN DE ÓRDENES Y CONSOLA API) | DERECHA (CREACIÓN Y REPORTE MENSUAL) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- COLUMNA PRINCIPAL (8 COLUMNAS) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- TABLA DE ÓRDENES DE PEDIDO -->
                <section id="seccion-ordenes" class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                                Registro y Control de Órdenes de Pedido
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Gestione el estado de cada despacho. Al seleccionar el estado <strong>Despachado</strong> se liquida automáticamente el flete con la transportadora.
                            </p>
                        </div>

                        <!-- FILTROS POR ESTADO -->
                        <div class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-md border border-slate-200">
                            @php
                                $filtros = [
                                    'todos' => 'Todas (' . $conteosEstados['todos'] . ')',
                                    'pendiente' => 'Pendientes (' . $conteosEstados['pendiente'] . ')',
                                    'despachado' => 'Despachadas (' . $conteosEstados['despachado'] . ')',
                                    'entregado' => 'Entregadas (' . $conteosEstados['entregado'] . ')',
                                ];
                            @endphp
                            @foreach($filtros as $clave => $etiqueta)
                                <a href="{{ route('pedidos.index', ['estado' => $clave]) }}"
                                   class="px-3 py-1.5 rounded text-xs font-semibold transition {{ $estadoFiltro === $clave ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white' }}">
                                    {{ $etiqueta }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">Orden / Fecha</th>
                                    <th class="px-4 py-3 text-left">Cliente / Ruta</th>
                                    <th class="px-4 py-3 text-right">Valor Venta</th>
                                    <th class="px-4 py-3 text-right">Flete Liquidado</th>
                                    <th class="px-4 py-3 text-center">Estado Actual</th>
                                    <th class="px-4 py-3 text-right">Operación / CRUD</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @forelse($pedidos as $pedido)
                                    @php
                                        $bloqueado = $pedido->estaEntregado() && !$esAdmin;
                                        $badgeClasses = match($pedido->estado) {
                                            'pendiente' => 'bg-amber-50 text-amber-800 border-amber-300',
                                            'despachado' => 'bg-blue-50 text-blue-800 border-blue-300',
                                            'entregado' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                                            default => 'bg-slate-100 text-slate-700 border-slate-300',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/90 transition">
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 tabular-nums">#{{ $pedido->id }}</div>
                                            <div class="text-[11px] text-slate-500 tabular-nums">{{ $pedido->fecha?->format('Y-m-d') }}</div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="font-semibold text-slate-900">{{ $pedido->cliente?->nombre }}</div>
                                            <div class="text-[11px] text-slate-500">
                                                Destino: <strong>{{ $pedido->ciudad_destino }}</strong> · Peso: {{ number_format((float)$pedido->peso_kg, 1) }} kg
                                            </div>
                                            @if($pedido->notas)
                                                <div class="text-[11px] text-slate-400 truncate max-w-xs" title="{{ $pedido->notas }}">
                                                    {{ $pedido->notas }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap font-bold text-slate-900 tabular-nums">
                                            ${{ number_format((float)$pedido->total, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap tabular-nums">
                                            @if($pedido->costo_envio !== null)
                                                <div class="font-semibold text-slate-800">
                                                    ${{ number_format((float)$pedido->costo_envio, 2, ',', '.') }}
                                                </div>
                                                @if($pedido->referencia_cotizacion)
                                                    <div class="text-[10px] font-mono text-slate-400">
                                                        Guía: {{ $pedido->referencia_cotizacion }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-[11px] text-slate-400">Pendiente despacho</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold border uppercase tracking-wide {{ $badgeClasses }}">
                                                {{ $pedido->estado }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            @if($bloqueado)
                                                <div class="inline-flex flex-col items-end">
                                                    <span class="text-[11px] text-slate-500 font-medium bg-slate-100 px-2.5 py-1 rounded border border-slate-200">
                                                        Orden Cerrada (Inmutable)
                                                    </span>
                                                    <span class="text-[10px] text-slate-400 mt-0.5">
                                                        Edición reservada al Administrador
                                                    </span>
                                                </div>
                                            @else
                                                <div class="inline-flex items-center justify-end gap-1.5">
                                                    <!-- Cambio rápido de estado -->
                                                    <form action="{{ route('pedidos.estado', $pedido) }}" method="POST" class="inline-flex items-center gap-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="estado"
                                                                class="rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 focus:border-blue-600 focus:outline-none">
                                                            @foreach(\App\Models\Pedido::ESTADOS as $est)
                                                                <option value="{{ $est }}" @selected($pedido->estado === $est)>
                                                                    {{ ucfirst($est) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit"
                                                                class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold px-2.5 py-1 rounded transition">
                                                            Aplicar
                                                        </button>
                                                    </form>

                                                    <!-- Botón Editar Orden (CRUD) -->
                                                    <a href="{{ route('pedidos.index', ['estado' => $estadoFiltro, 'editar' => $pedido->id]) }}#panel-edicion-orden"
                                                       class="text-xs font-semibold px-2.5 py-1 rounded border transition {{ $esAdmin ? 'bg-blue-50 border-blue-300 text-blue-800 hover:bg-blue-100' : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-100' }}">
                                                        Editar
                                                    </a>

                                                    <!-- Botón Eliminar Orden (Exclusivo Administrador) -->
                                                    @if($esAdmin)
                                                        <form action="{{ route('pedidos.destroy', $pedido) }}" method="POST"
                                                              onsubmit="return confirm('¿Confirma la eliminación permanente de la orden #{{ $pedido->id }}?');"
                                                              class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="text-xs font-semibold px-2 py-1 rounded border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 transition"
                                                                    title="Eliminar orden">
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            No existen órdenes registradas bajo el estado seleccionado (<strong>{{ $estadoFiltro }}</strong>).
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- MAESTRO DE CLIENTES Y CRUD CORPORATIVO -->
                <section id="seccion-clientes" class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                                Directorio Maestro de Clientes Corporativos
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Clientes habilitados para despacho nacional. {{ $esAdmin ? 'Como Administrador puede registrar, editar o eliminar clientes.' : 'Seleccione el perfil Administrador para editar o eliminar registros del maestro.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Formulario de Edición o Creación de Cliente -->
                    <div class="p-5 bg-slate-50 border-b border-slate-200">
                        @if($clienteEditar && $esAdmin)
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-blue-800 uppercase">
                                    Editando Cliente: {{ $clienteEditar->nombre }}
                                </span>
                                <a href="{{ route('pedidos.index', ['estado' => $estadoFiltro]) }}#seccion-clientes"
                                   class="text-xs text-slate-600 hover:text-slate-900 underline">
                                    Cancelar edición
                                </a>
                            </div>
                            <form action="{{ route('clientes.update', $clienteEditar) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @csrf
                                @method('PUT')
                                <input type="text" name="nombre" required value="{{ $clienteEditar->nombre }}" placeholder="Razón Social *"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <input type="text" name="documento" value="{{ $clienteEditar->documento }}" placeholder="NIT / Documento"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <input type="email" name="email" required value="{{ $clienteEditar->email }}" placeholder="Correo Electrónico *"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <input type="text" name="telefono" value="{{ $clienteEditar->telefono }}" placeholder="Teléfono"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <input type="text" name="ciudad" required value="{{ $clienteEditar->ciudad }}" placeholder="Ciudad Principal *"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <div class="flex gap-2">
                                    <input type="text" name="direccion" value="{{ $clienteEditar->direccion }}" placeholder="Dirección"
                                           class="flex-1 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                    <button type="submit" class="bg-blue-700 hover:bg-blue-600 text-white font-semibold px-4 py-1.5 rounded text-xs whitespace-nowrap">
                                        Actualizar
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="text-xs font-semibold text-slate-700 mb-2">
                                Registrar Nuevo Cliente en el Directorio
                            </div>
                            <form action="{{ route('clientes.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @csrf
                                <input type="text" name="nombre" required placeholder="Razón Social / Nombre *"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <input type="text" name="documento" placeholder="NIT / Documento"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <input type="email" name="email" required placeholder="Correo Corporativo *"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <input type="text" name="telefono" placeholder="Teléfono de Contacto"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <input type="text" name="ciudad" required placeholder="Ciudad Principal *"
                                       class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                <div class="flex gap-2">
                                    <input type="text" name="direccion" placeholder="Dirección Comercial"
                                           class="flex-1 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold px-4 py-1.5 rounded text-xs whitespace-nowrap">
                                        + Agregar Cliente
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">Razón Social / NIT</th>
                                    <th class="px-4 py-2.5 text-left">Contacto</th>
                                    <th class="px-4 py-2.5 text-left">Ciudad / Dirección</th>
                                    <th class="px-4 py-2.5 text-center">Órdenes</th>
                                    @if($esAdmin)
                                        <th class="px-4 py-2.5 text-right">Gestión Administrador</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($clientes as $cli)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-2.5">
                                            <div class="font-semibold text-slate-900">{{ $cli->nombre }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $cli->documento ?: 'Sin NIT' }}</div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="text-slate-800">{{ $cli->email }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $cli->telefono }}</div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="font-medium text-slate-800">{{ $cli->ciudad }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $cli->direccion }}</div>
                                        </td>
                                        <td class="px-4 py-2.5 text-center font-semibold tabular-nums">
                                            {{ $cli->pedidos_count }}
                                        </td>
                                        @if($esAdmin)
                                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <a href="{{ route('pedidos.index', ['estado' => $estadoFiltro, 'editar_cliente' => $cli->id]) }}#seccion-clientes"
                                                       class="px-2.5 py-1 rounded border border-blue-300 bg-blue-50 text-blue-800 hover:bg-blue-100 font-semibold">
                                                        Editar
                                                    </a>
                                                    <form action="{{ route('clientes.destroy', $cli) }}" method="POST"
                                                          onsubmit="return confirm('¿Confirma eliminar el cliente {{ $cli->nombre }} y sus órdenes asociadas?');"
                                                          class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="px-2.5 py-1 rounded border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 font-semibold">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- CONSOLA INTERACTIVA EN REACT 18 (SINCRONIZACIÓN API REST) -->
                <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
                    <div id="react-pedidos-root" data-admin="{{ $esAdmin ? '1' : '0' }}"></div>
                </section>

            </div>

            <!-- COLUMNA LATERAL (4 COLUMNAS): REGISTRO DE PEDIDO + CONSOLIDADO COMERCIAL DEL MES -->
            <div class="lg:col-span-4 space-y-6">

                <!-- FORMULARIO CORPORATIVO PARA CREAR ORDEN DE PEDIDO -->
                <section id="formulario-nuevo-pedido" class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
                    <div class="pb-3 mb-4 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                            Nueva Orden de Pedido
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Complete la información comercial y logística del despacho.
                        </p>
                    </div>

                    <form action="{{ route('pedidos.store', ['filtro_actual' => $estadoFiltro]) }}" method="POST" class="space-y-3.5">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cliente Corporativo *</label>
                            <select name="cliente_id" id="select-cliente-crear" required
                                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs focus:border-blue-600 focus:outline-none">
                                <option value="">Seleccione un cliente del directorio...</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}"
                                            data-ciudad="{{ $cliente->ciudad }}"
                                            @selected(old('cliente_id') == $cliente->id)>
                                        {{ $cliente->nombre }} — {{ $cliente->ciudad }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha de Orden *</label>
                                <input type="date" name="fecha" required
                                       value="{{ old('fecha', now()->format('Y-m-d')) }}"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Estado Inicial</label>
                                <select name="estado" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-xs">
                                    <option value="pendiente" @selected(old('estado') === 'pendiente')>Pendiente</option>
                                    <option value="despachado" @selected(old('estado') === 'despachado')>Despachado (Liquidar flete)</option>
                                    <option value="entregado" @selected(old('estado') === 'entregado')>Entregado</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Valor Venta ($) *</label>
                                <input type="number" step="0.01" min="1" name="total" required
                                       placeholder="Ej. 1500000"
                                       value="{{ old('total') }}"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Peso Total (kg) *</label>
                                <input type="number" step="0.1" min="0.1" name="peso_kg" required
                                       value="{{ old('peso_kg', '5.0') }}"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad de Destino</label>
                            <input type="text" name="ciudad_destino" id="input-ciudad-crear"
                                   placeholder="Se asigna automáticamente según el cliente"
                                   value="{{ old('ciudad_destino') }}"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Instrucciones / Notas Logísticas</label>
                            <textarea name="notas" rows="2" placeholder="Detalle de mercancía, bodega de recepción o contacto..."
                                      class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">{{ old('notas') }}</textarea>
                        </div>

                        <button type="submit"
                                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-4 rounded-md text-xs shadow-sm transition">
                            Registrar Orden de Pedido
                        </button>
                    </form>
                </section>

                <!-- REPORTE EJECUTIVO: VENTAS POR CLIENTE EN EL MES ACTUAL -->
                <section id="seccion-reporte" class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
                    <div class="pb-3 mb-4 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                            Ventas por Cliente — Mes Actual
                        </h2>
                        <p class="text-xs text-slate-500 capitalize mt-0.5">
                            Consolidado comercial de {{ $mesActualNombre }} (Ordenado de mayor a menor facturación)
                        </p>
                    </div>

                    <div class="space-y-3">
                        @forelse($reporteMensual as $item)
                            <div class="p-3 rounded-md bg-slate-50 border border-slate-200">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[11px] font-bold {{ $item->posicion === 1 ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $item->posicion }}
                                        </span>
                                        <span class="font-semibold text-xs text-slate-900 ml-1">
                                            {{ $item->cliente_nombre }}
                                        </span>
                                        <div class="text-[11px] text-slate-500 ml-6">
                                            {{ $item->cliente_ciudad }} · {{ $item->cantidad_pedidos }} {{ $item->cantidad_pedidos === 1 ? 'orden' : 'órdenes' }} en el mes
                                        </div>
                                    </div>
                                    <div class="text-right tabular-nums">
                                        <div class="font-bold text-xs text-slate-900">
                                            ${{ number_format($item->total_vendido, 2, ',', '.') }}
                                        </div>
                                        <div class="text-[10px] text-slate-500">
                                            {{ $item->participacion_porcentaje }}% del total
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-200 h-1.5 rounded-full mt-2 overflow-hidden">
                                    <div class="bg-blue-700 h-1.5 rounded-full" style="width: {{ min(100, $item->participacion_porcentaje) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 text-center py-4">
                                No existen órdenes facturadas en el mes en curso.
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs tabular-nums">
                        <span class="font-semibold text-slate-600">Total Facturado ({{ ucfirst($mesActualNombre) }}):</span>
                        <span class="font-bold text-sm text-slate-900">
                            ${{ number_format($reporteMensual->sum('total_vendido'), 2, ',', '.') }}
                        </span>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 mt-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between text-xs text-slate-500">
            <span>UVA3 Logística Nacional · Plataforma Corporativa de Despachos</span>
            <span>Integración Transportadora: <code class="font-mono text-slate-700">POST https://tarifas.transportadora.test/v1/cotizar</code></span>
        </div>
    </footer>

    <!-- SCRIPTS DE AUTOCOMPLETADO Y COMPONENTE REACT 18 -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectCliente = document.getElementById('select-cliente-crear');
            const inputCiudad = document.getElementById('input-ciudad-crear');
            if (selectCliente && inputCiudad) {
                selectCliente.addEventListener('change', function () {
                    const selectedOption = selectCliente.options[selectCliente.selectedIndex];
                    const ciudad = selectedOption ? selectedOption.getAttribute('data-ciudad') : '';
                    if (ciudad) {
                        inputCiudad.value = ciudad;
                    }
                });
            }
        });

        // Consola React 18 sincronizada con GET /api/pedidos?estado=...
        (function () {
            const rootEl = document.getElementById('react-pedidos-root');
            if (!rootEl || typeof React === 'undefined' || typeof ReactDOM === 'undefined') return;

            const e = React.createElement;
            const { useState, useEffect, useCallback } = React;

            function PedidosReactWidget({ initialAdmin }) {
                const [estadoFiltro, setEstadoFiltro] = useState('pendiente');
                const [pedidos, setPedidos] = useState([]);
                const [loading, setLoading] = useState(false);
                const [error, setError] = useState(null);
                const [mensaje, setMensaje] = useState(null);
                const [adminMode, setAdminMode] = useState(initialAdmin);
                const [verJson, setVerJson] = useState(false);
                const [rawResponse, setRawResponse] = useState(null);

                const endpoint = estadoFiltro === 'todos'
                    ? '/api/pedidos'
                    : '/api/pedidos?estado=' + encodeURIComponent(estadoFiltro);

                const fetchPedidos = useCallback(async () => {
                    setLoading(true);
                    setError(null);
                    try {
                        const res = await fetch(endpoint, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Admin-Mode': adminMode ? '1' : '0'
                            }
                        });
                        const data = await res.json();
                        setRawResponse(data);
                        if (!res.ok || !data.ok) {
                            throw new Error(data.message || 'Error al consultar el servicio de pedidos');
                        }
                        setPedidos(data.data || []);
                    } catch (err) {
                        setError(err.message);
                    } finally {
                        setLoading(false);
                    }
                }, [endpoint, adminMode]);

                useEffect(() => {
                    fetchPedidos();
                }, [fetchPedidos]);

                const actualizarEstadoViaApi = async (pedidoId, nuevoEstado) => {
                    setError(null);
                    setMensaje(null);
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/pedidos/' + pedidoId + '/estado', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Admin-Mode': adminMode ? '1' : '0'
                            },
                            body: JSON.stringify({
                                estado: nuevoEstado,
                                es_admin: adminMode
                            })
                        });
                        const data = await res.json();
                        if (!res.ok || !data.ok) {
                            throw new Error(data.message || 'No fue posible actualizar el estado');
                        }
                        setMensaje(data.message);
                        await fetchPedidos();
                    } catch (err) {
                        setError(err.message);
                    }
                };

                return e('div', { className: 'space-y-4' },
                    e('div', { className: 'flex flex-wrap items-center justify-between gap-3 bg-slate-900 text-white p-4 rounded-md' },
                        e('div', null,
                            e('span', { className: 'text-xs font-bold uppercase tracking-wider text-slate-300 block' },
                                'Consola de Monitoreo API en Tiempo Real (React 18)'
                            ),
                            e('div', { className: 'text-xs font-mono text-slate-400 mt-0.5' },
                                'Endpoint: ',
                                e('a', {
                                    href: endpoint,
                                    target: '_blank',
                                    className: 'text-sky-400 underline font-semibold'
                                }, 'GET ' + endpoint)
                            )
                        ),
                        e('div', { className: 'flex flex-wrap items-center gap-2' },
                            e('select', {
                                value: adminMode ? 'admin' : 'empleado',
                                onChange: (ev) => setAdminMode(ev.target.value === 'admin'),
                                className: 'text-xs bg-slate-800 text-white border border-slate-700 px-3 py-1.5 rounded font-medium'
                            },
                                e('option', { value: 'empleado' }, 'Perfil API: Empleado Operativo'),
                                e('option', { value: 'admin' }, 'Perfil API: Administrador General')
                            ),
                            e('button', {
                                type: 'button',
                                onClick: () => setVerJson(!verJson),
                                className: 'text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 px-3 py-1.5 rounded font-medium'
                            }, verJson ? 'Ocultar JSON' : 'Ver Respuesta JSON'),
                            e('button', {
                                type: 'button',
                                onClick: fetchPedidos,
                                className: 'text-xs bg-blue-700 hover:bg-blue-600 px-3 py-1.5 rounded font-semibold'
                            }, 'Sincronizar')
                        )
                    ),

                    e('div', { className: 'flex flex-wrap items-center gap-1.5' },
                        ['pendiente', 'despachado', 'entregado', 'todos'].map((est) =>
                            e('button', {
                                key: est,
                                type: 'button',
                                onClick: () => setEstadoFiltro(est),
                                className: 'px-3.5 py-1.5 rounded text-xs font-semibold capitalize transition ' +
                                    (estadoFiltro === est
                                        ? 'bg-slate-900 text-white'
                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200')
                            }, 'Filtro: ' + est)
                        )
                    ),

                    error && e('div', { className: 'p-3 rounded bg-red-50 border border-red-200 text-red-800 text-xs font-medium' }, error),
                    mensaje && e('div', { className: 'p-3 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium' }, mensaje),

                    verJson && rawResponse && e('pre', {
                        className: 'p-3.5 bg-slate-950 text-slate-200 rounded text-xs overflow-x-auto max-h-60 font-mono'
                    }, JSON.stringify(rawResponse, null, 2)),

                    loading
                        ? e('div', { className: 'py-6 text-center text-xs text-slate-500' }, 'Sincronizando datos desde ' + endpoint + ' ...')
                        : e('div', { className: 'overflow-x-auto border border-slate-200 rounded' },
                            e('table', { className: 'min-w-full divide-y divide-slate-200 text-xs' },
                                e('thead', { className: 'bg-slate-50 text-slate-600 uppercase' },
                                    e('tr', null,
                                        e('th', { className: 'px-3 py-2.5 text-left' }, 'Orden'),
                                        e('th', { className: 'px-3 py-2.5 text-left' }, 'Cliente'),
                                        e('th', { className: 'px-3 py-2.5 text-left' }, 'Fecha'),
                                        e('th', { className: 'px-3 py-2.5 text-left' }, 'Destino'),
                                        e('th', { className: 'px-3 py-2.5 text-right' }, 'Valor Venta'),
                                        e('th', { className: 'px-3 py-2.5 text-right' }, 'Flete'),
                                        e('th', { className: 'px-3 py-2.5 text-center' }, 'Estado'),
                                        e('th', { className: 'px-3 py-2.5 text-right' }, 'Acción Rápida')
                                    )
                                ),
                                e('tbody', { className: 'divide-y divide-slate-200' },
                                    pedidos.length === 0
                                        ? e('tr', null,
                                            e('td', { colSpan: 8, className: 'px-3 py-4 text-center text-slate-400' },
                                                'Sin registros para el estado ' + estadoFiltro
                                            )
                                        )
                                        : pedidos.map((p) => {
                                            const bloqueado = p.estado === 'entregado' && !adminMode;
                                            return e('tr', { key: p.id, className: 'hover:bg-slate-50' },
                                                e('td', { className: 'px-3 py-2.5 font-bold tabular-nums' }, '#' + p.id),
                                                e('td', { className: 'px-3 py-2.5 font-medium text-slate-900' }, p.cliente ? p.cliente.nombre : '—'),
                                                e('td', { className: 'px-3 py-2.5 text-slate-500 tabular-nums' }, (p.fecha || '').substring(0, 10)),
                                                e('td', { className: 'px-3 py-2.5 text-slate-600' }, p.ciudad_destino + ' (' + p.peso_kg + ' kg)'),
                                                e('td', { className: 'px-3 py-2.5 text-right font-semibold tabular-nums' }, '$' + Number(p.total).toLocaleString('es-CO')),
                                                e('td', { className: 'px-3 py-2.5 text-right text-slate-800 font-medium tabular-nums' },
                                                    p.costo_envio !== null ? '$' + Number(p.costo_envio).toLocaleString('es-CO') : '—'
                                                ),
                                                e('td', { className: 'px-3 py-2.5 text-center font-bold uppercase' }, p.estado),
                                                e('td', { className: 'px-3 py-2.5 text-right' },
                                                    e('select', {
                                                        disabled: bloqueado,
                                                        value: p.estado,
                                                        onChange: (ev) => actualizarEstadoViaApi(p.id, ev.target.value),
                                                        className: 'border border-slate-300 rounded px-2 py-1 text-xs disabled:opacity-50 bg-white'
                                                    },
                                                        e('option', { value: 'pendiente' }, 'Pendiente'),
                                                        e('option', { value: 'despachado' }, 'Despachado'),
                                                        e('option', { value: 'entregado' }, 'Entregado')
                                                    )
                                                )
                                            );
                                        })
                                )
                            )
                        )
                );
            }

            const initialAdmin = rootEl.getAttribute('data-admin') === '1';
            const root = ReactDOM.createRoot(rootEl);
            root.render(e(PedidosReactWidget, { initialAdmin }));
        })();
    </script>
</body>
</html>
