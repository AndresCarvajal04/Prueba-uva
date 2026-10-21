<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>UVA3 Logística — Módulo de Pedidos y Despachos Nacionales</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- React 18 para el Requisito 7 (Listado en React consumiendo GET /api/pedidos?estado=...) -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

    <!-- Barra Superior: Identidad + Selector de Rol (Requisito 6) -->
    <header class="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-30 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-lg shadow-inner">
                    U3
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-bold text-lg tracking-tight">UVA3 Logística Nacional</h1>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 font-medium">
                            Módulo de Pedidos
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Control de despachos, cotización de transportadora y ventas mensuales por cliente
                    </p>
                </div>
            </div>

            <!-- Selector de Rol para validar Requisito 6 -->
            <div class="flex flex-wrap items-center gap-3">
                <a href="/api/pedidos?estado=pendiente" target="_blank"
                   class="text-xs font-mono bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-slate-700 px-3 py-2 rounded-lg transition flex items-center gap-1.5">
                    <span>GET /api/pedidos?estado=pendiente</span>
                    <span>↗</span>
                </a>

                <form action="{{ route('pedidos.cambiar-rol') }}" method="POST" class="flex items-center bg-slate-800 p-1 rounded-xl border border-slate-700">
                    @csrf
                    <input type="hidden" name="rol" value="{{ $esAdmin ? 'operador' : 'admin' }}">
                    <div class="px-3 py-1 text-xs">
                        <span class="text-slate-400 block text-[10px] uppercase tracking-wider">Rol Activo (Req. 6)</span>
                        @if($esAdmin)
                            <span class="font-bold text-amber-400">★ Administrador (Corrige todo)</span>
                        @else
                            <span class="font-semibold text-sky-300">Operador (Entregados bloqueados)</span>
                        @endif
                    </div>
                    <button type="submit"
                            class="text-xs font-semibold px-3 py-1.5 rounded-lg transition {{ $esAdmin ? 'bg-slate-700 hover:bg-slate-600 text-slate-200' : 'bg-amber-500 hover:bg-amber-400 text-slate-950' }}">
                        Cambiar a {{ $esAdmin ? 'Operador' : 'Administrador' }}
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Alertas Flash y Errores de Validación -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start justify-between gap-4 shadow-sm">
                <div class="text-sm font-medium">
                    <span class="font-bold text-emerald-700">✓ Operación exitosa:</span> {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 flex items-start justify-between gap-4 shadow-sm">
                <div class="text-sm font-medium">
                    <span class="font-bold text-red-700">⚠ Acción restringida:</span> {{ session('error') }}
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm space-y-1">
                <div class="font-bold">Por favor corrige los siguientes errores en el formulario:</div>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Tarjetas de Resumen (KPIs) -->
        <section class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
                <span class="text-xs font-medium text-slate-500 uppercase">Total Pedidos</span>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $conteosEstados['todos'] }}</p>
                <span class="text-[11px] text-slate-400">Histórico registrado</span>
            </div>
            <div class="bg-amber-50/70 rounded-xl p-4 border border-amber-200 shadow-sm">
                <span class="text-xs font-semibold text-amber-800 uppercase">Pendientes</span>
                <p class="text-2xl font-bold text-amber-700 mt-1">{{ $conteosEstados['pendiente'] }}</p>
                <span class="text-[11px] text-amber-700/80">Por despachar y cotizar</span>
            </div>
            <div class="bg-sky-50/70 rounded-xl p-4 border border-sky-200 shadow-sm">
                <span class="text-xs font-semibold text-sky-800 uppercase">Despachados</span>
                <p class="text-2xl font-bold text-sky-700 mt-1">{{ $conteosEstados['despachado'] }}</p>
                <span class="text-[11px] text-sky-700/80">Con flete cotizado en API</span>
            </div>
            <div class="bg-emerald-50/70 rounded-xl p-4 border border-emerald-200 shadow-sm">
                <span class="text-xs font-semibold text-emerald-800 uppercase">Entregados</span>
                <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $conteosEstados['entregado'] }}</p>
                <span class="text-[11px] text-emerald-700/80">Inmutables para operador</span>
            </div>
            <div class="col-span-2 sm:col-span-1 bg-indigo-950 text-white rounded-xl p-4 shadow-sm">
                <span class="text-xs font-semibold text-indigo-300 uppercase">Ventas Mes Actual</span>
                <p class="text-xl font-bold text-white mt-1">
                    ${{ number_format($reporteMensual->sum('total_vendido'), 0, ',', '.') }}
                </p>
                <span class="text-[11px] text-indigo-300 capitalize">{{ $mesActualNombre }}</span>
            </div>
        </section>

        <!-- Modal / Panel de Corrección de Pedido (Requisito 6) -->
        @if($pedidoEditar)
            <section class="bg-amber-50 border-2 border-amber-400 rounded-2xl p-6 shadow-md">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800 bg-amber-200 px-2.5 py-0.5 rounded-full">
                            Requisito 6 — Corrección de Pedido #{{ $pedidoEditar->id }}
                        </span>
                        <h2 class="text-lg font-bold text-slate-900 mt-1">
                            Corrigiendo Pedido de {{ $pedidoEditar->cliente?->nombre }}
                        </h2>
                    </div>
                    <a href="{{ route('pedidos.index', ['estado' => $estadoFiltro]) }}"
                       class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white px-3 py-1.5 rounded-lg border border-slate-300">
                        ✕ Cancelar edición
                    </a>
                </div>

                @if($pedidoEditar->estaEntregado() && !$esAdmin)
                    <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-xl text-sm">
                        Este pedido se encuentra en estado <strong>ENTREGADO</strong> y no puede ser modificado con el rol de Operador.
                        Usa el botón superior para cambiar a <strong>Administrador</strong> y habilitar su corrección.
                    </div>
                @else
                    <form action="{{ route('pedidos.update', $pedidoEditar) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cliente</label>
                            <select name="cliente_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected($pedidoEditar->cliente_id === $cliente->id)>
                                        {{ $cliente->nombre }} ({{ $cliente->ciudad }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha del Pedido</label>
                            <input type="date" name="fecha" required
                                   value="{{ $pedidoEditar->fecha?->format('Y-m-d') }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Estado</label>
                            <select name="estado" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                @foreach(\App\Models\Pedido::ESTADOS as $est)
                                    <option value="{{ $est }}" @selected($pedidoEditar->estado === $est)>
                                        {{ ucfirst($est) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Valor Total Venta ($)</label>
                            <input type="number" step="0.01" min="0" name="total" required
                                   value="{{ $pedidoEditar->total }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Peso Total (kg)</label>
                            <input type="number" step="0.1" min="0.1" name="peso_kg" required
                                   value="{{ $pedidoEditar->peso_kg }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad Destino</label>
                            <input type="text" name="ciudad_destino" required
                                   value="{{ $pedidoEditar->ciudad_destino }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Costo Envío ($) (Vacío = Recotizar)</label>
                            <input type="number" step="0.01" min="0" name="costo_envio"
                                   value="{{ $pedidoEditar->costo_envio }}"
                                   placeholder="Auto-cotizar si es despachado"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Notas / Observaciones</label>
                            <input type="text" name="notas"
                                   value="{{ $pedidoEditar->notas }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>

                        <div class="sm:col-span-2 lg:col-span-4 flex justify-end gap-3 pt-2">
                            <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-semibold px-5 py-2 rounded-lg text-sm shadow-sm">
                                Guardar Corrección del Pedido #{{ $pedidoEditar->id }}
                            </button>
                        </div>
                    </form>
                @endif
            </section>
        @endif

        <!-- Layout Principal: Izquierda (Listado y Filtro + React) | Derecha (Crear Pedido + Reporte Mes Actual) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Columna Principal (8 cols): Listado de Pedidos (Req 2, 5, 6) + Pestaña React (Req 7) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Tarjeta: Listado de Pedidos con Filtro por Estado -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                Listado de Pedidos Nacionales
                            </h2>
                            <p class="text-xs text-slate-500">
                                Filtra por estado o cambia el estado de un pedido. Al pasar a <span class="font-semibold text-sky-700">despachado</span> se cotiza automáticamente el envío en <code class="bg-slate-100 px-1 rounded">POST /v1/cotizar</code>.
                            </p>
                        </div>

                        <!-- Filtro por Estado (Requisito 2) -->
                        <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 p-1 rounded-xl">
                            @php
                                $filtros = [
                                    'todos' => 'Todos (' . $conteosEstados['todos'] . ')',
                                    'pendiente' => 'Pendientes (' . $conteosEstados['pendiente'] . ')',
                                    'despachado' => 'Despachados (' . $conteosEstados['despachado'] . ')',
                                    'entregado' => 'Entregados (' . $conteosEstados['entregado'] . ')',
                                ];
                            @endphp
                            @foreach($filtros as $clave => $etiqueta)
                                <a href="{{ route('pedidos.index', ['estado' => $clave]) }}"
                                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $estadoFiltro === $clave ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }}">
                                    {{ $etiqueta }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold">
                                <tr>
                                    <th class="px-4 py-3 text-left">Pedido / Fecha</th>
                                    <th class="px-4 py-3 text-left">Cliente / Destino</th>
                                    <th class="px-4 py-3 text-right">Venta Total</th>
                                    <th class="px-4 py-3 text-right">Costo Envío (Req. 5)</th>
                                    <th class="px-4 py-3 text-center">Estado</th>
                                    <th class="px-4 py-3 text-right">Cambiar Estado / Corregir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($pedidos as $pedido)
                                    @php
                                        $bloqueado = $pedido->estaEntregado() && !$esAdmin;
                                        $badgeClasses = match($pedido->estado) {
                                            'pendiente' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'despachado' => 'bg-sky-100 text-sky-800 border-sky-200',
                                            'entregado' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="font-bold text-slate-900">#{{ $pedido->id }}</div>
                                            <div class="text-xs text-slate-500">{{ $pedido->fecha?->format('Y-m-d') }}</div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="font-semibold text-slate-900">{{ $pedido->cliente?->nombre }}</div>
                                            <div class="text-xs text-slate-500">
                                                📍 {{ $pedido->ciudad_destino }} · ⚖️ {{ number_format((float)$pedido->peso_kg, 1) }} kg
                                            </div>
                                            @if($pedido->notas)
                                                <div class="text-[11px] text-slate-400 truncate max-w-xs" title="{{ $pedido->notas }}">
                                                    {{ $pedido->notas }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap font-bold text-slate-900">
                                            ${{ number_format((float)$pedido->total, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            @if($pedido->costo_envio !== null)
                                                <div class="font-semibold text-sky-700">
                                                    ${{ number_format((float)$pedido->costo_envio, 2, ',', '.') }}
                                                </div>
                                                @if($pedido->referencia_cotizacion)
                                                    <div class="text-[10px] font-mono text-slate-400">
                                                        {{ $pedido->referencia_cotizacion }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-xs text-slate-400 italic">Sin cotizar</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border uppercase {{ $badgeClasses }}">
                                                {{ $pedido->estado }}
                                            </span>
                                            @if($bloqueado)
                                                <div class="text-[10px] text-slate-400 mt-1">🔒 Inmutable</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            @if($bloqueado)
                                                <div class="inline-flex flex-col items-end">
                                                    <span class="text-xs text-slate-400 font-medium bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                                        Entregado (Bloqueado)
                                                    </span>
                                                    <span class="text-[10px] text-amber-600 mt-0.5">
                                                        Requiere rol Administrador
                                                    </span>
                                                </div>
                                            @else
                                                <div class="inline-flex items-center gap-1.5">
                                                    <form action="{{ route('pedidos.estado', $pedido) }}" method="POST" class="inline-flex items-center gap-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="estado"
                                                                class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none">
                                                            @foreach(\App\Models\Pedido::ESTADOS as $est)
                                                                <option value="{{ $est }}" @selected($pedido->estado === $est)>
                                                                    {{ ucfirst($est) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit"
                                                                class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-2.5 py-1 rounded-lg transition">
                                                            Guardar
                                                        </button>
                                                    </form>

                                                    <a href="{{ route('pedidos.index', ['estado' => $estadoFiltro, 'editar' => $pedido->id]) }}"
                                                       title="Corregir pedido completo"
                                                       class="text-xs font-semibold px-2.5 py-1 rounded-lg border {{ $esAdmin ? 'bg-amber-50 border-amber-300 text-amber-800 hover:bg-amber-100' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                                                        ✎
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            No hay pedidos registrados con el estado seleccionado (<strong>{{ $estadoFiltro }}</strong>).
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Requisito 7 (Opcional - Suma puntos): Módulo en React consumiendo GET /api/pedidos?estado=... -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <div id="react-pedidos-root" data-admin="{{ $esAdmin ? '1' : '0' }}"></div>
                </div>

            </div>

            <!-- Columna Lateral (4 cols): Crear Pedido (Req 2) + Reporte Ventas del Mes (Req 4) -->
            <div class="lg:col-span-4 space-y-6">

                <!-- Tarjeta: Formulario para Crear un Pedido (Requisito 2) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <div class="mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full">
                            Requisito 2
                        </span>
                        <h2 class="text-base font-bold text-slate-900 mt-1">Registrar Nuevo Pedido</h2>
                        <p class="text-xs text-slate-500">
                            Crea un pedido para cualquier cliente de UVA3.
                        </p>
                    </div>

                    <form action="{{ route('pedidos.store', ['filtro_actual' => $estadoFiltro]) }}" method="POST" class="space-y-3.5">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cliente *</label>
                            <select name="cliente_id" id="select-cliente-crear" required
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="">Seleccione un cliente...</option>
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
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha *</label>
                                <input type="date" name="fecha" required
                                       value="{{ old('fecha', now()->format('Y-m-d')) }}"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Estado Inicial</label>
                                <select name="estado" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                    <option value="pendiente" @selected(old('estado') === 'pendiente')>Pendiente</option>
                                    <option value="despachado" @selected(old('estado') === 'despachado')>Despachado (Cotizar ya)</option>
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
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Peso Carga (kg) *</label>
                                <input type="number" step="0.1" min="0.1" name="peso_kg" required
                                       value="{{ old('peso_kg', '5.0') }}"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad de Destino</label>
                            <input type="text" name="ciudad_destino" id="input-ciudad-crear"
                                   placeholder="Se toma del cliente si se deja vacío"
                                   value="{{ old('ciudad_destino') }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Notas de Despacho</label>
                            <textarea name="notas" rows="2" placeholder="Instrucciones de entrega o contenido de carga..."
                                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notas') }}</textarea>
                        </div>

                        <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-4 rounded-xl text-sm shadow-sm transition">
                            + Crear Pedido
                        </button>
                    </form>
                </div>

                <!-- Tarjeta: Reporte Total Vendido por Cliente en el Mes Actual (Requisito 4) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">
                                Requisito 4
                            </span>
                            <h2 class="text-base font-bold text-slate-900 mt-1">
                                Ventas por Cliente — Mes Actual
                            </h2>
                            <p class="text-xs text-slate-500 capitalize">
                                {{ $mesActualNombre }} · Ordenado de mayor a menor
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3 mt-4">
                        @forelse($reporteMensual as $item)
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold {{ $item->posicion === 1 ? 'bg-amber-400 text-slate-950' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $item->posicion }}
                                        </span>
                                        <span class="font-semibold text-sm text-slate-900 ml-1">
                                            {{ $item->cliente_nombre }}
                                        </span>
                                        <div class="text-[11px] text-slate-500 ml-6">
                                            {{ $item->cliente_ciudad }} · {{ $item->cantidad_pedidos }} {{ $item->cantidad_pedidos === 1 ? 'pedido' : 'pedidos' }} este mes
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-sm text-emerald-700">
                                            ${{ number_format($item->total_vendido, 2, ',', '.') }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ $item->participacion_porcentaje }}% del mes
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-200 h-1.5 rounded-full mt-2 overflow-hidden">
                                    <div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ min(100, $item->participacion_porcentaje) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 text-center py-4">
                                No hay ventas registradas en el mes actual.
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-600">Total Vendido ({{ ucfirst($mesActualNombre) }}):</span>
                        <span class="font-bold text-sm text-slate-900">
                            ${{ number_format($reporteMensual->sum('total_vendido'), 2, ',', '.') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Autocompletado de Ciudad Destino en Formulario y Montaje del Componente React (Requisito 7) -->
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

        // Componente React 18 (Requisito 7: Listado en React consumiendo el endpoint del punto 3: GET /api/pedidos?estado=...)
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
                            throw new Error(data.message || 'Error al consultar el endpoint');
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
                    e('div', { className: 'flex flex-wrap items-center justify-between gap-3 bg-slate-900 text-white p-4 rounded-xl' },
                        e('div', null,
                            e('span', { className: 'text-[11px] font-bold uppercase tracking-wider text-sky-400' },
                                '⚛ Requisito 7 (Opcional) — Componente React 18 en Vivo'
                            ),
                            e('div', { className: 'text-xs font-mono text-slate-300 mt-0.5' },
                                'Consumiendo endpoint Requisito 3: ',
                                e('a', {
                                    href: endpoint,
                                    target: '_blank',
                                    className: 'text-emerald-400 underline font-semibold'
                                }, 'GET ' + endpoint)
                            )
                        ),
                        e('div', { className: 'flex flex-wrap items-center gap-2' },
                            e('label', { className: 'flex items-center gap-1.5 text-xs bg-slate-800 px-3 py-1.5 rounded-lg cursor-pointer border border-slate-700' },
                                e('input', {
                                    type: 'checkbox',
                                    checked: adminMode,
                                    onChange: (ev) => setAdminMode(ev.target.checked)
                                }),
                                e('span', null, 'Modo Admin en API')
                            ),
                            e('button', {
                                type: 'button',
                                onClick: () => setVerJson(!verJson),
                                className: 'text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 px-3 py-1.5 rounded-lg font-medium'
                            }, verJson ? 'Ocultar JSON' : 'Ver JSON Respuesta'),
                            e('button', {
                                type: 'button',
                                onClick: fetchPedidos,
                                className: 'text-xs bg-indigo-600 hover:bg-indigo-500 px-3 py-1.5 rounded-lg font-semibold'
                            }, '↻ Refrescar API')
                        )
                    ),

                    e('div', { className: 'flex flex-wrap items-center gap-2' },
                        ['pendiente', 'despachado', 'entregado', 'todos'].map((est) =>
                            e('button', {
                                key: est,
                                type: 'button',
                                onClick: () => setEstadoFiltro(est),
                                className: 'px-3.5 py-1.5 rounded-lg text-xs font-semibold capitalize transition ' +
                                    (estadoFiltro === est
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200')
                            }, 'Estado: ' + est)
                        )
                    ),

                    error && e('div', { className: 'p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs font-medium' }, '⚠ ' + error),
                    mensaje && e('div', { className: 'p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium' }, '✓ ' + mensaje),

                    verJson && rawResponse && e('pre', {
                        className: 'p-3.5 bg-slate-950 text-emerald-300 rounded-xl text-xs overflow-x-auto max-h-60 font-mono'
                    }, JSON.stringify(rawResponse, null, 2)),

                    loading
                        ? e('div', { className: 'py-6 text-center text-xs text-slate-500' }, 'Consultando ' + endpoint + ' ...')
                        : e('div', { className: 'overflow-x-auto border border-slate-200 rounded-xl' },
                            e('table', { className: 'min-w-full divide-y divide-slate-200 text-xs' },
                                e('thead', { className: 'bg-slate-50 text-slate-500 uppercase' },
                                    e('tr', null,
                                        e('th', { className: 'px-3 py-2.5 text-left' }, 'ID'),
                                        e('th', { className: 'px-3 py-2.5 text-left' }, 'Cliente'),
                                        e('th', { className: 'px-3 py-2.5 text-left' }, 'Fecha'),
                                        e('th', { className: 'px-3 py-2.5 text-left' }, 'Destino'),
                                        e('th', { className: 'px-3 py-2.5 text-right' }, 'Total'),
                                        e('th', { className: 'px-3 py-2.5 text-right' }, 'Costo Envío'),
                                        e('th', { className: 'px-3 py-2.5 text-center' }, 'Estado'),
                                        e('th', { className: 'px-3 py-2.5 text-right' }, 'Cambiar vía API')
                                    )
                                ),
                                e('tbody', { className: 'divide-y divide-slate-100' },
                                    pedidos.length === 0
                                        ? e('tr', null,
                                            e('td', { colSpan: 8, className: 'px-3 py-4 text-center text-slate-400' },
                                                'Sin pedidos para el filtro ' + estadoFiltro
                                            )
                                        )
                                        : pedidos.map((p) => {
                                            const bloqueado = p.estado === 'entregado' && !adminMode;
                                            return e('tr', { key: p.id, className: 'hover:bg-slate-50' },
                                                e('td', { className: 'px-3 py-2.5 font-bold' }, '#' + p.id),
                                                e('td', { className: 'px-3 py-2.5 font-medium text-slate-900' }, p.cliente ? p.cliente.nombre : '—'),
                                                e('td', { className: 'px-3 py-2.5 text-slate-500' }, (p.fecha || '').substring(0, 10)),
                                                e('td', { className: 'px-3 py-2.5 text-slate-600' }, p.ciudad_destino + ' (' + p.peso_kg + ' kg)'),
                                                e('td', { className: 'px-3 py-2.5 text-right font-semibold' }, '$' + Number(p.total).toLocaleString('es-CO')),
                                                e('td', { className: 'px-3 py-2.5 text-right text-sky-700 font-medium' },
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
