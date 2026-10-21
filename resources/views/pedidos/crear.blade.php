@extends('layouts.app')

@section('title', 'Registrar Nueva Orden')

@section('content')
    <!-- ENCABEZADO DE SECCIÓN E INSTRUCCIONES -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1 max-w-3xl">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded">
                    Sección 2 · Registro de Órdenes
                </span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">
                    Apertura de Nueva Orden de Pedido
                </h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <strong>Instrucciones:</strong> Seleccione un cliente activo del directorio corporativo; el sistema asignará de forma predeterminada su ciudad principal de entrega (puede modificarla si el despacho se dirige a una sucursal distinta). Si selecciona como estado inicial <strong>Despachado</strong>, la tarifa de transporte nacional se calculará inmediatamente al guardar la orden.
                </p>
            </div>
            <a href="{{ route('pedidos.index') }}"
               class="px-3.5 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                ← Volver al Listado de Órdenes
            </a>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- FORMULARIO PRINCIPAL (8 COLUMNAS) -->
        <section class="lg:col-span-8 bg-white rounded-lg border border-slate-200 shadow-sm p-6">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide pb-3 mb-5 border-b border-slate-200">
                Datos Comerciales y Logísticos de la Orden
            </h3>

            <form action="{{ route('pedidos.store') }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cliente Corporativo *</label>
                        <select name="cliente_id" id="select-cliente-crear" required
                                class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2.5 text-xs focus:border-blue-600 focus:outline-none">
                            <option value="">Seleccione un cliente del directorio...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}"
                                        data-ciudad="{{ $cliente->ciudad }}"
                                        data-direccion="{{ $cliente->direccion }}"
                                        @selected(old('cliente_id') == $cliente->id)>
                                    {{ $cliente->nombre }} ({{ $cliente->documento ?: 'Sin NIT' }}) — {{ $cliente->ciudad }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha de Emisión *</label>
                        <input type="date" name="fecha" required
                               value="{{ old('fecha', now()->format('Y-m-d')) }}"
                               class="w-full rounded-md border border-slate-300 px-3.5 py-2 text-xs focus:border-blue-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Estado Operativo Inicial *</label>
                        <select name="estado" class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs focus:border-blue-600 focus:outline-none">
                            <option value="pendiente" @selected(old('estado') === 'pendiente')>Pendiente (En alistamiento de bodega)</option>
                            <option value="despachado" @selected(old('estado') === 'despachado')>Despachado (Liquidar tarifa de envío inmediatamente)</option>
                            <option value="entregado" @selected(old('estado') === 'entregado')>Entregado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Valor Comercial de la Venta ($) *</label>
                        <input type="number" step="0.01" min="1" name="total" required
                               placeholder="Ej. 2450000"
                               value="{{ old('total') }}"
                               class="w-full rounded-md border border-slate-300 px-3.5 py-2 text-xs focus:border-blue-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peso Bruto de la Carga (kg) *</label>
                        <input type="number" step="0.1" min="0.1" name="peso_kg" required
                               value="{{ old('peso_kg', '5.0') }}"
                               class="w-full rounded-md border border-slate-300 px-3.5 py-2 text-xs focus:border-blue-600 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad de Destino</label>
                        <input type="text" name="ciudad_destino" id="input-ciudad-crear"
                               placeholder="Se completa automáticamente al elegir el cliente"
                               value="{{ old('ciudad_destino') }}"
                               class="w-full rounded-md border border-slate-300 px-3.5 py-2 text-xs focus:border-blue-600 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Instrucciones de Entrega / Observaciones</label>
                        <textarea name="notas" rows="3" placeholder="Indique condiciones de embalaje, horario de recepción o referencias de bodega..."
                                  class="w-full rounded-md border border-slate-300 px-3.5 py-2 text-xs focus:border-blue-600 focus:outline-none">{{ old('notas') }}</textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                    <a href="{{ route('pedidos.index') }}"
                       class="px-4 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-md bg-blue-700 hover:bg-blue-600 text-white text-xs font-semibold shadow-sm transition">
                        Guardar y Registrar Orden
                    </button>
                </div>
            </form>
        </section>

        <!-- PANEL LATERAL DE GUÍA RÁPIDA (4 COLUMNAS) -->
        <aside class="lg:col-span-4 space-y-4">
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-5 space-y-3">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                    Guía de Diligenciamiento
                </h3>
                <ol class="space-y-2.5 text-xs text-slate-600 list-decimal list-inside leading-relaxed">
                    <li><strong>Verificación del Cliente:</strong> Si el cliente no aparece en la lista desplegable, regístrelo primero en la sección <a href="{{ route('clientes.index') }}" class="text-blue-700 font-semibold underline">Maestro de Clientes</a>.</li>
                    <li><strong>Cálculo de Transporte:</strong> El peso en kilogramos y la ciudad de destino son utilizados por el servicio de cotización al momento del despacho.</li>
                    <li><strong>Impacto Contable:</strong> Toda orden registrada con fecha del mes en curso se incorpora automáticamente al consolidado de <a href="{{ route('reportes.ventas') }}" class="text-blue-700 font-semibold underline">Ventas del Mes</a>.</li>
                </ol>
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
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
</script>
@endpush
