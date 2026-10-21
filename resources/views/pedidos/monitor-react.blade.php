@extends('layouts.app')

@section('title', 'Tablero Dinámico de Despachos')

@push('head')
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
@endpush

@section('content')
    <!-- ENCABEZADO DE SECCIÓN E INSTRUCCIONES -->
    <section class="corp-panel p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1 max-w-3xl">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded">
                    Sección 5 · Operación Asíncrona (React 18)
                </span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">
                    Tablero Dinámico de Órdenes en Tiempo Real
                </h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <strong>Instrucciones:</strong> Este módulo interactivo permite filtrar órdenes y ejecutar transiciones de estado sin recargar la página. Respeta de forma estricta los permisos de la sesión activa (<strong>{{ $esAdmin ? 'Administrador General' : 'Empleado Operativo' }}</strong>): las órdenes entregadas permanecen protegidas para empleados y solo admiten cambios bajo autorización de Administrador.
                </p>
            </div>
            <a href="{{ route('pedidos.index') }}"
               class="px-3.5 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                Ir a Vista Clásica de Órdenes
            </a>
        </div>
    </section>

    <!-- CONTENEDOR DEL COMPONENTE REACT 18 -->
    <section class="corp-panel p-5">
        <div id="react-pedidos-root" data-admin="{{ $esAdmin ? '1' : '0' }}"></div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/tablero-react.js') }}" defer></script>
@endpush
