@extends('layouts.app')

@section('title', 'Flujo Operativo e Instrucciones')

@section('content')
    <!-- ENCABEZADO DE SECCIÓN -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1 max-w-3xl">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded">
                    Sección 6 · Manual Operativo Corporativo
                </span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">
                    Flujo de Operación del Sistema Logístico e Instrucciones por Perfil
                </h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Esta guía describe el procedimiento estándar de operación de la plataforma, las responsabilidades de cada módulo y los alcances de los perfiles <strong>Empleado Operativo</strong> y <strong>Administrador General</strong>.
                </p>
            </div>
            <a href="{{ route('pedidos.index') }}"
               class="px-4 py-2 rounded-md bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition">
                Ir al Centro de Órdenes
            </a>
        </div>
    </section>

    <!-- FLUJO PASO A PASO EN 4 ETAPAS -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 space-y-5">
        <div class="border-b border-slate-200 pb-3">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                Secuencia Operativa Paso a Paso
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Recorrido completo de una orden desde la vinculación comercial del cliente hasta el cierre contable mensual.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Etapa 1 -->
            <div class="p-5 rounded-lg border border-slate-200 bg-slate-50/70 flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-100 px-2.5 py-0.5 rounded">
                        Paso 1 · Maestro de Clientes
                    </span>
                    <h4 class="text-sm font-bold text-slate-900">Alta y Verificación del Cliente Corporativo</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Antes de emitir un despacho, verifique que la empresa compradora se encuentre registrada en el directorio con su NIT, correo corporativo y ciudad principal de entrega.
                    </p>
                </div>
                <div>
                    <a href="{{ route('clientes.index') }}" class="inline-flex items-center text-xs font-semibold text-blue-700 hover:text-blue-900 underline">
                        Abrir Sección 3: Maestro de Clientes →
                    </a>
                </div>
            </div>

            <!-- Etapa 2 -->
            <div class="p-5 rounded-lg border border-slate-200 bg-slate-50/70 flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-100 px-2.5 py-0.5 rounded">
                        Paso 2 · Apertura de Orden
                    </span>
                    <h4 class="text-sm font-bold text-slate-900">Registro de Nueva Orden de Pedido</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Diligencie el formulario indicando cliente, fecha de emisión, valor comercial de la venta y peso bruto en kilogramos. La orden ingresa por defecto en estado <strong>Pendiente</strong> para alistamiento en bodega.
                    </p>
                </div>
                <div>
                    <a href="{{ route('pedidos.crear') }}" class="inline-flex items-center text-xs font-semibold text-blue-700 hover:text-blue-900 underline">
                        Abrir Sección 2: Registrar Orden →
                    </a>
                </div>
            </div>

            <!-- Etapa 3 -->
            <div class="p-5 rounded-lg border border-slate-200 bg-slate-50/70 flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-100 px-2.5 py-0.5 rounded">
                        Paso 3 · Despacho y Liquidación
                    </span>
                    <h4 class="text-sm font-bold text-slate-900">Transición a Despachado y Cálculo de Flete</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Cuando el pedido sale a ruta nacional, cambie su estado a <strong>Despachado</strong> desde el listado de órdenes o desde el Tablero Dinámico. El sistema consulta de forma automática el servicio de tarifas de la transportadora y registra el costo de envío y número de guía.
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('pedidos.index', ['estado' => 'pendiente']) }}" class="inline-flex items-center text-xs font-semibold text-blue-700 hover:text-blue-900 underline">
                        Ver Órdenes Pendientes →
                    </a>
                    <a href="{{ route('pedidos.monitor') }}" class="inline-flex items-center text-xs font-semibold text-slate-700 hover:text-slate-900 underline">
                        Abrir Tablero Dinámico →
                    </a>
                </div>
            </div>

            <!-- Etapa 4 -->
            <div class="p-5 rounded-lg border border-slate-200 bg-slate-50/70 flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-100 px-2.5 py-0.5 rounded">
                        Paso 4 · Entrega y Consolidado Contable
                    </span>
                    <h4 class="text-sm font-bold text-slate-900">Confirmación de Entrega y Reporte Mensual</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Al confirmar la recepción por parte del cliente, actualice el estado a <strong>Entregado</strong>. El registro queda bloqueado contra cambios accidentales de operadores y sus cifras alimentan el ranking mensual de ventas por cliente.
                    </p>
                </div>
                <div>
                    <a href="{{ route('reportes.ventas') }}" class="inline-flex items-center text-xs font-semibold text-blue-700 hover:text-blue-900 underline">
                        Abrir Sección 4: Ventas del Mes →
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- MATRIZ DE PERMISOS: EMPLEADO OPERATIVO VS ADMINISTRADOR GENERAL -->
    <section class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                Matriz de Autorización por Perfil (Menú Desplegable Superior)
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Compare las operaciones permitidas según el perfil seleccionado en la barra superior del sistema.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Operación del Sistema</th>
                        <th class="px-4 py-3 text-center">Empleado Operativo</th>
                        <th class="px-4 py-3 text-center">Administrador General</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">Registrar nuevas órdenes de pedido y nuevos clientes</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">Avanzar estados operativos (Pendiente → Despachado → Entregado)</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">Modificar o corregir órdenes cerradas en estado <strong>Entregado</strong></td>
                        <td class="px-4 py-3 text-center text-red-700 font-semibold">Bloqueado (Inmutable)</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado (Edición y Recotización)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">Eliminar órdenes de pedido del sistema</td>
                        <td class="px-4 py-3 text-center text-red-700 font-semibold">Restringido</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">Editar y eliminar registros del Maestro de Clientes</td>
                        <td class="px-4 py-3 text-center text-red-700 font-semibold">Restringido</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">Consultar Reporte Mensual de Ventas por Cliente</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">Habilitado</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection
