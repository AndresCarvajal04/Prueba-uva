@extends('layouts.app')

@section('title', 'Maestro de Clientes')

@section('content')
    <!-- ENCABEZADO DE SECCIÓN E INSTRUCCIONES -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1 max-w-3xl">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded">
                    Sección 3 · Directorio Comercial
                </span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">
                    Directorio Maestro de Clientes Corporativos
                </h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <strong>Instrucciones:</strong> Administre las empresas y clientes habilitados para recibir despachos nacionales. La ciudad principal registrada en cada cliente se utilizará por defecto al momento de abrir una nueva orden de pedido.
                    @if($esAdmin)
                        Con el perfil <strong>Administrador General</strong> activo puede crear, editar o eliminar cualquier registro del maestro de clientes.
                    @else
                        Con el perfil <strong>Empleado Operativo</strong> puede registrar nuevos clientes; para editar o eliminar registros existentes, cambie al perfil <strong>Administrador General</strong>.
                    @endif
                </p>
            </div>
            <a href="{{ route('pedidos.crear') }}"
               class="px-4 py-2 rounded-md bg-blue-700 hover:bg-blue-600 text-white text-xs font-semibold shadow-sm transition">
                + Crear Orden para un Cliente
            </a>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- FORMULARIO DE ALTA O EDICIÓN DE CLIENTE (4 COLUMNAS) -->
        <section class="lg:col-span-4 bg-white rounded-lg border border-slate-200 shadow-sm p-5 h-fit">
            @if($clienteEditar && $esAdmin)
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700">Modo Edición</span>
                        <h3 class="text-sm font-bold text-slate-900">Actualizar Cliente #{{ $clienteEditar->id }}</h3>
                    </div>
                    <a href="{{ route('clientes.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                        Cancelar
                    </a>
                </div>
                <form action="{{ route('clientes.update', $clienteEditar) }}" method="POST" class="space-y-3.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Razón Social / Nombre *</label>
                        <input type="text" name="nombre" required value="{{ $clienteEditar->nombre }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NIT / Documento</label>
                        <input type="text" name="documento" value="{{ $clienteEditar->documento }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Correo Corporativo *</label>
                        <input type="email" name="email" required value="{{ $clienteEditar->email }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Teléfono</label>
                            <input type="text" name="telefono" value="{{ $clienteEditar->telefono }}"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad Principal *</label>
                            <input type="text" name="ciudad" required value="{{ $clienteEditar->ciudad }}"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Dirección Comercial</label>
                        <input type="text" name="direccion" value="{{ $clienteEditar->direccion }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <button type="submit" class="w-full bg-blue-700 hover:bg-blue-600 text-white font-semibold py-2.5 px-4 rounded-md text-xs shadow-sm transition">
                        Guardar Cambios del Cliente
                    </button>
                </form>
            @else
                <div class="pb-3 mb-4 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                        Registrar Nuevo Cliente
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Incorpore una nueva empresa al maestro comercial.
                    </p>
                </div>
                <form action="{{ route('clientes.store') }}" method="POST" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Razón Social / Nombre *</label>
                        <input type="text" name="nombre" required placeholder="Ej. Logística del Oriente S.A.S."
                               value="{{ old('nombre') }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NIT / Documento</label>
                        <input type="text" name="documento" placeholder="Ej. 900.555.123-4"
                               value="{{ old('documento') }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Correo Corporativo *</label>
                        <input type="email" name="email" required placeholder="compras@empresa.co"
                               value="{{ old('email') }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Teléfono</label>
                            <input type="text" name="telefono" placeholder="601 300 0000"
                                   value="{{ old('telefono') }}"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad Principal *</label>
                            <input type="text" name="ciudad" required placeholder="Ej. Medellín"
                                   value="{{ old('ciudad') }}"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Dirección Comercial</label>
                        <input type="text" name="direccion" placeholder="Zona Industrial Bodega 4"
                               value="{{ old('direccion') }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-4 rounded-md text-xs shadow-sm transition">
                        + Registrar Cliente en Maestro
                    </button>
                </form>
            @endif
        </section>

        <!-- TABLA DEL DIRECTORIO DE CLIENTES (8 COLUMNAS) -->
        <section class="lg:col-span-8 bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                        Cartera Activa de Clientes ({{ $clientes->count() }})
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Listado de empresas registradas y volumen histórico de órdenes asociadas.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3 text-left">Razón Social / NIT</th>
                            <th class="px-4 py-3 text-left">Contacto</th>
                            <th class="px-4 py-3 text-left">Ciudad / Dirección</th>
                            <th class="px-4 py-3 text-center">Órdenes</th>
                            <th class="px-4 py-3 text-right">Administración</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($clientes as $cli)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $cli->nombre }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $cli->documento ?: 'Sin NIT' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-slate-800">{{ $cli->email }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $cli->telefono }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $cli->ciudad }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $cli->direccion }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-slate-800 tabular-nums">
                                    {{ $cli->pedidos_count }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if($esAdmin)
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ route('clientes.index', ['editar_cliente' => $cli->id]) }}"
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
                                    @else
                                        <span class="text-[11px] text-slate-400">Solo Administrador</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
