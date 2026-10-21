@extends('layouts.app')

@section('title', 'Consolidado Mensual de Ventas')

@section('content')
    <!-- ENCABEZADO DE SECCIÓN E INSTRUCCIONES -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1 max-w-3xl">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded">
                    Sección 4 · Inteligencia Comercial
                </span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">
                    Ventas por Cliente — Mes Actual ({{ ucfirst($mesActualNombre) }})
                </h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <strong>Instrucciones del reporte:</strong> Este informe consolida el valor total vendido a cada cliente durante el periodo comprendido entre el <strong>{{ $inicioMes }}</strong> y el <strong>{{ $finMes }}</strong>, ordenado de forma descendente (de mayor a menor facturación). Las órdenes correspondientes a meses anteriores quedan automáticamente excluidas del corte contable actual.
                </p>
            </div>
            <a href="{{ route('pedidos.crear') }}"
               class="px-4 py-2 rounded-md bg-blue-700 hover:bg-blue-600 text-white text-xs font-semibold shadow-sm transition">
                + Registrar Nueva Orden
            </a>
        </div>
    </section>

    @php
        $granTotalVentas = (float) $reporteMensual->sum('total_vendido');
        $granTotalFletes = (float) $reporteMensual->sum('total_costo_envio');
        $totalOrdenesMes = (int) $reporteMensual->sum('cantidad_pedidos');
    @endphp

    <!-- INDICADORES DEL CORTE MENSUAL -->
    <section class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-slate-900 text-white rounded-lg p-4 border border-slate-800 shadow-sm">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Facturación Total del Mes</span>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">
                ${{ number_format($granTotalVentas, 2, ',', '.') }}
            </p>
            <span class="text-[11px] text-slate-400 capitalize">{{ $mesActualNombre }}</span>
        </div>
        <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Clientes con Movimiento</span>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $reporteMensual->count() }}</p>
            <span class="text-[11px] text-slate-400">Empresas facturadas en el mes</span>
        </div>
        <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Órdenes del Periodo</span>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $totalOrdenesMes }}</p>
            <span class="text-[11px] text-slate-400">Emitidas entre {{ $inicioMes }} y {{ $finMes }}</span>
        </div>
        <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Fletes Liquidados del Mes</span>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">
                ${{ number_format($granTotalFletes, 2, ',', '.') }}
            </p>
            <span class="text-[11px] text-slate-400">Costo logístico acumulado</span>
        </div>
    </section>

    <!-- TABLA CONTABLE ORDENADA DE MAYOR A MENOR -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                    Ranking de Facturación Mensual por Cliente (Mayor a Menor)
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Cifras expresadas en moneda local ($) para el cierre comercial vigente.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Posición</th>
                        <th class="px-4 py-3 text-left">Cliente Corporativo / NIT</th>
                        <th class="px-4 py-3 text-left">Ciudad Sede</th>
                        <th class="px-4 py-3 text-center">Órdenes del Mes</th>
                        <th class="px-4 py-3 text-right">Fletes Asociados</th>
                        <th class="px-4 py-3 text-right">Total Vendido (Mes Actual)</th>
                        <th class="px-4 py-3 text-left w-48">Participación (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($reporteMensual as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded text-xs font-bold tabular-nums {{ $item->posicion === 1 ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 border border-slate-300' }}">
                                    #{{ $item->posicion }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-semibold text-slate-900">{{ $item->cliente_nombre }}</div>
                                <div class="text-[11px] text-slate-500">{{ $item->cliente_documento ?: 'Sin NIT' }} · {{ $item->cliente_email }}</div>
                            </td>
                            <td class="px-4 py-3.5 font-medium text-slate-700">
                                {{ $item->cliente_ciudad }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold text-slate-800 tabular-nums">
                                {{ $item->cantidad_pedidos }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-slate-600 tabular-nums">
                                ${{ number_format($item->total_costo_envio, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-900 tabular-nums text-sm">
                                ${{ number_format($item->total_vendido, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-slate-200 h-2 rounded-full overflow-hidden">
                                        <div class="bg-blue-700 h-2 rounded-full" style="width: {{ min(100, $item->participacion_porcentaje) }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-slate-700 tabular-nums w-12 text-right">
                                        {{ $item->participacion_porcentaje }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                No se registran ventas en el mes actual.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($reporteMensual->isNotEmpty())
                    <tfoot class="bg-slate-50 font-bold text-slate-900 border-t-2 border-slate-200">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right uppercase text-[11px] tracking-wider text-slate-600">
                                Consolidado General ({{ ucfirst($mesActualNombre) }}):
                            </td>
                            <td class="px-4 py-3 text-center tabular-nums">{{ $totalOrdenesMes }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">${{ number_format($granTotalFletes, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-sm">${{ number_format($granTotalVentas, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">100.0%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>
@endsection
