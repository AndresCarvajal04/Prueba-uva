@extends('layouts.app')

@section('title', 'Órdenes de Pedido')

@section('content')
    <!-- ENCABEZADO DE SECCIÓN E INSTRUCCIONES OPERATIVAS -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1 max-w-3xl">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded">
                        Sección 1 · Centro de Operaciones
                    </span>
                    <span class="text-xs text-slate-400">Flujo de Despacho y Auditoría</span>
                </div>
                <h2 class="text-lg font-bold text-slate-900">
                    Gestión y Control de Órdenes de Pedido
                </h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <strong>Instrucciones de uso:</strong> Utilice los botones de filtro para consultar las órdenes según su etapa operativa (<em>Pendiente</em>, <em>Despachado</em> o <em>Entregado</em>). Para avanzar el estado de un pedido, seleccione el nuevo estado en la columna <strong>Operación</strong> y presione <strong>Aplicar</strong>. Al cambiar a <strong>Despachado</strong>, el sistema calcula y asigna automáticamente la tarifa de transporte nacional.
                    @if($esAdmin)
                        Como <strong>Administrador General</strong>, dispone de los botones <strong>Editar</strong> y <strong>Eliminar</strong> sobre todas las órdenes, incluidas las entregadas.
                    @else
                        Las órdenes en estado <strong>Entregado</strong> se encuentran cerradas; si requiere corregir una orden entregada, seleccione el perfil <strong>Administrador General</strong> en la barra superior.
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('pedidos.guia') }}"
                   class="px-3.5 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                    Ver Guía Paso a Paso
                </a>
                <a href="{{ route('pedidos.crear') }}"
                   class="px-4 py-2 rounded-md bg-blue-700 hover:bg-blue-600 text-white text-xs font-semibold shadow-sm transition">
                    + Registrar Nueva Orden
                </a>
            </div>
        </div>
    </section>

    <!-- INDICADORES EJECUTIVOS (KPIs) -->
    <section class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Órdenes Registradas</span>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $conteosEstados['todos'] }}</p>
            <span class="text-[11px] text-slate-400">Total histórico</span>
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
        <a href="{{ route('reportes.ventas') }}" class="col-span-2 sm:col-span-1 bg-slate-900 hover:bg-slate-800 transition text-white rounded-lg p-4 border border-slate-800 shadow-sm block">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Ventas por Cliente — Mes Actual</span>
            <p class="text-xl font-bold text-white mt-1 tabular-nums">
                ${{ number_format($totalFacturadoMes, 0, ',', '.') }}
            </p>
            <span class="text-[11px] text-sky-400 capitalize">{{ $mesActualNombre }} · Ver reporte →</span>
        </a>
    </section>

    <!-- TABLA PRINCIPAL DE ÓRDENES CON FILTRO POR ESTADO -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                    Listado Maestro de Órdenes
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Mostrando filtro activo: <strong class="uppercase text-slate-800">{{ $estadoFiltro }}</strong>
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
                       class="px-3.5 py-1.5 rounded text-xs font-semibold transition {{ $estadoFiltro === $clave ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white' }}">
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
                        <th class="px-4 py-3 text-left">Cliente Corporativo / Ruta</th>
                        <th class="px-4 py-3 text-right">Valor Venta</th>
                        <th class="px-4 py-3 text-right">Flete Liquidado</th>
                        <th class="px-4 py-3 text-center">Estado Actual</th>
                        <th class="px-4 py-3 text-right">Operación / Administración</th>
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
                                    <div class="text-[11px] text-slate-400 truncate max-w-md" title="{{ $pedido->notas }}">
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

                                        <a href="{{ route('pedidos.editar', $pedido) }}"
                                           class="text-xs font-semibold px-2.5 py-1 rounded border transition {{ $esAdmin ? 'bg-blue-50 border-blue-300 text-blue-800 hover:bg-blue-100' : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-100' }}">
                                            Editar
                                        </a>

                                        @if($esAdmin)
                                            <form action="{{ route('pedidos.destroy', $pedido) }}" method="POST"
                                                  onsubmit="return confirm('¿Confirma la eliminación permanente de la orden #{{ $pedido->id }}?');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-xs font-semibold px-2 py-1 rounded border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 transition">
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
@endsection
