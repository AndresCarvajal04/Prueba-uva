@extends('layouts.app')

@section('title', 'Edición y Auditoría de Orden #' . $pedidoEditar->id)

@section('content')
    <!-- ENCABEZADO DE SECCIÓN E INSTRUCCIONES -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1 max-w-3xl">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded">
                    Auditoría y Edición de Registro
                </span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">
                    Modificar Orden de Pedido #{{ $pedidoEditar->id }} — {{ $pedidoEditar->cliente?->nombre }}
                </h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <strong>Instrucciones de edición:</strong> Modifique los datos comerciales o logísticos que requieran corrección. Si marca la casilla de recotización y el estado es <strong>Despachado</strong>, el sistema volverá a consultar la tarifa de envío con los nuevos parámetros de ciudad y peso.
                </p>
            </div>
            <a href="{{ route('pedidos.index') }}"
               class="px-3.5 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                ← Volver al Listado de Órdenes
            </a>
        </div>
    </section>

    @if($pedidoEditar->estaEntregado() && !$esAdmin)
        <section class="bg-red-50 border border-red-300 rounded-lg p-6 text-red-900 space-y-3">
            <h3 class="text-sm font-bold uppercase tracking-wide text-red-800">
                Orden Cerrada en Estado Entregado — Edición Bloqueada para Empleado Operativo
            </h3>
            <p class="text-xs leading-relaxed">
                Por políticas de integridad operativa, las órdenes que ya han sido marcadas como <strong>Entregadas</strong> no pueden ser alteradas por el perfil de Empleado Operativo. Para realizar una corrección administrativa sobre este registro, cambie el selector superior a <strong>Administrador General</strong>.
            </p>
        </section>
    @else
        <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-6">
            <form action="{{ route('pedidos.update', $pedidoEditar) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cliente Titular *</label>
                        <select name="cliente_id" required class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs">
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected($pedidoEditar->cliente_id === $cliente->id)>
                                    {{ $cliente->nombre }} ({{ $cliente->ciudad }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha de Orden *</label>
                        <input type="date" name="fecha" required
                               value="{{ $pedidoEditar->fecha?->format('Y-m-d') }}"
                               class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Estado Operativo *</label>
                        <select name="estado" required class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold">
                            @foreach(\App\Models\Pedido::ESTADOS as $est)
                                <option value="{{ $est }}" @selected($pedidoEditar->estado === $est)>
                                    {{ ucfirst($est) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Valor Comercial ($) *</label>
                        <input type="number" step="0.01" min="0" name="total" required
                               value="{{ $pedidoEditar->total }}"
                               class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peso Bruto (kg) *</label>
                        <input type="number" step="0.1" min="0.1" name="peso_kg" required
                               value="{{ $pedidoEditar->peso_kg }}"
                               class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad de Destino *</label>
                        <input type="text" name="ciudad_destino" required
                               value="{{ $pedidoEditar->ciudad_destino }}"
                               class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Costo de Envío ($)</label>
                        <input type="number" step="0.01" min="0" name="costo_envio"
                               value="{{ $pedidoEditar->costo_envio }}"
                               placeholder="Vacío = Cotizar automáticamente"
                               class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs">
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Observaciones de Despacho</label>
                        <textarea name="notas" rows="2"
                                  class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs">{{ $pedidoEditar->notas }}</textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                        <input type="checkbox" name="forzar_recotizacion" value="1" class="rounded border-slate-300">
                        <span>Recalcular tarifa automáticamente con la transportadora si el estado es <strong>Despachado</strong></span>
                    </label>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('pedidos.index') }}"
                           class="px-4 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-blue-700 hover:bg-blue-600 text-white font-semibold px-5 py-2 rounded-md text-xs shadow-sm">
                            Guardar Cambios en la Orden #{{ $pedidoEditar->id }}
                        </button>
                    </div>
                </div>
            </form>
        </section>
    @endif
@endsection
