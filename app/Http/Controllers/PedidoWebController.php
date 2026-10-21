<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\ReporteVentasService;
use App\Services\TransportadoraService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PedidoWebController extends Controller
{
    public function __construct(
        private readonly TransportadoraService $transportadoraService,
        private readonly ReporteVentasService $reporteVentasService
    ) {}

    /**
     * Sección 1: Gestión y Control de Órdenes de Pedido.
     */
    public function index(Request $request): View
    {
        $estadoFiltro = $request->query('estado', 'todos');
        if (! in_array($estadoFiltro, ['todos', ...Pedido::ESTADOS], true)) {
            $estadoFiltro = 'todos';
        }

        $query = Pedido::with('cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($estadoFiltro !== 'todos') {
            $query->where('estado', $estadoFiltro);
        }

        $pedidos = $query->get();
        $reporteMensual = $this->reporteVentasService->obtenerVentasMesActual();

        return view('pedidos.index', [
            'pedidos' => $pedidos,
            'estadoFiltro' => $estadoFiltro,
            'conteosEstados' => $this->obtenerConteosEstados(),
            'totalFacturadoMes' => (float) $reporteMensual->sum('total_vendido'),
            'mesActualNombre' => Carbon::now()->translatedFormat('F Y'),
            'esAdmin' => $this->esAdministrador($request),
            'seccionActiva' => 'ordenes',
        ]);
    }

    /**
     * Sección 2: Formulario dedicado para Registrar una Nueva Orden de Pedido.
     */
    public function create(Request $request): View
    {
        $clientes = Cliente::orderBy('nombre')->get();

        return view('pedidos.crear', [
            'clientes' => $clientes,
            'conteosEstados' => $this->obtenerConteosEstados(),
            'esAdmin' => $this->esAdministrador($request),
            'seccionActiva' => 'crear',
        ]);
    }

    /**
     * Sección 3: Edición y Auditoría de una Orden de Pedido.
     */
    public function edit(Request $request, Pedido $pedido): View
    {
        $pedido->load('cliente');
        $clientes = Cliente::orderBy('nombre')->get();

        return view('pedidos.editar', [
            'pedidoEditar' => $pedido,
            'clientes' => $clientes,
            'conteosEstados' => $this->obtenerConteosEstados(),
            'esAdmin' => $this->esAdministrador($request),
            'seccionActiva' => 'ordenes',
        ]);
    }

    /**
     * Sección 4: Directorio Maestro de Clientes Corporativos (CRUD).
     */
    public function clientesIndex(Request $request): View
    {
        $clientes = Cliente::withCount('pedidos')->orderBy('nombre')->get();

        $clienteEditar = null;
        if ($request->filled('editar_cliente')) {
            $clienteEditar = Cliente::find($request->query('editar_cliente'));
        }

        return view('clientes.index', [
            'clientes' => $clientes,
            'clienteEditar' => $clienteEditar,
            'conteosEstados' => $this->obtenerConteosEstados(),
            'esAdmin' => $this->esAdministrador($request),
            'seccionActiva' => 'clientes',
        ]);
    }

    /**
     * Sección 5: Reporte Consolidado de Ventas por Cliente en el Mes Actual.
     */
    public function reporteVentas(Request $request): View
    {
        $reporteMensual = $this->reporteVentasService->obtenerVentasMesActual();

        return view('reportes.ventas', [
            'reporteMensual' => $reporteMensual,
            'mesActualNombre' => Carbon::now()->translatedFormat('F Y'),
            'inicioMes' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'finMes' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'conteosEstados' => $this->obtenerConteosEstados(),
            'esAdmin' => $this->esAdministrador($request),
            'seccionActiva' => 'reportes',
        ]);
    }

    /**
     * Sección 6: Tablero Dinámico de Operaciones en Tiempo Real (React 18).
     */
    public function monitorReact(Request $request): View
    {
        return view('pedidos.monitor-react', [
            'conteosEstados' => $this->obtenerConteosEstados(),
            'esAdmin' => $this->esAdministrador($request),
            'seccionActiva' => 'monitor',
        ]);
    }

    /**
     * Sección 7: Manual Operativo Paso a Paso y Matriz de Roles.
     */
    public function guiaOperativa(Request $request): View
    {
        return view('pedidos.guia', [
            'conteosEstados' => $this->obtenerConteosEstados(),
            'esAdmin' => $this->esAdministrador($request),
            'seccionActiva' => 'guia',
        ]);
    }

    /**
     * Registra una nueva orden de pedido con sanitización de campos.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'fecha' => ['required', 'date'],
            'total' => ['required', 'numeric', 'min:1', 'max:999999999.99'],
            'peso_kg' => ['required', 'numeric', 'min:0.1', 'max:50000'],
            'ciudad_destino' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(Pedido::ESTADOS)],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $estado = $validated['estado'] ?? Pedido::ESTADO_PENDIENTE;
        $ciudadDestino = ! empty($validated['ciudad_destino'])
            ? trim(strip_tags($validated['ciudad_destino']))
            : $cliente->ciudad;

        $pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'fecha' => $validated['fecha'],
            'estado' => $estado,
            'total' => $validated['total'],
            'peso_kg' => $validated['peso_kg'],
            'ciudad_destino' => $ciudadDestino,
            'notas' => isset($validated['notas']) ? trim(strip_tags($validated['notas'])) : null,
        ]);

        $mensaje = "Orden de pedido #{$pedido->id} registrada correctamente para {$cliente->nombre}.";

        if ($estado === Pedido::ESTADO_DESPACHADO) {
            $cotizacion = $this->transportadoraService->cotizarEnvio($pedido);
            $pedido->update([
                'costo_envio' => $cotizacion['costo_envio'],
                'referencia_cotizacion' => $cotizacion['referencia_cotizacion'],
            ]);
            $mensaje .= ' Costo de envío asignado: $' . number_format($cotizacion['costo_envio'], 2) . " (Guía: {$cotizacion['referencia_cotizacion']}).";
        }

        return redirect()
            ->route('pedidos.index')
            ->with('success', $mensaje);
    }

    /**
     * Actualiza el estado operativo de un pedido.
     */
    public function cambiarEstado(Request $request, Pedido $pedido): RedirectResponse
    {
        $esAdmin = $this->esAdministrador($request);

        if (! $pedido->puedeSerModificadoPor($esAdmin)) {
            return redirect()
                ->back()
                ->with('error', "Operación denegada: La orden #{$pedido->id} se encuentra cerrada en estado ENTREGADO y no admite modificaciones por personal operativo. Solicite intervención de un Administrador.");
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(Pedido::ESTADOS)],
        ]);

        $nuevoEstado = $validated['estado'];
        $datosActualizar = ['estado' => $nuevoEstado];
        $mensajeExtra = '';

        if ($nuevoEstado === Pedido::ESTADO_DESPACHADO) {
            $cotizacion = $this->transportadoraService->cotizarEnvio($pedido);
            $datosActualizar['costo_envio'] = $cotizacion['costo_envio'];
            $datosActualizar['referencia_cotizacion'] = $cotizacion['referencia_cotizacion'];
            $mensajeExtra = ' — Tarifa de transporte liquidada: $' . number_format($cotizacion['costo_envio'], 2)
                . " (Guía: {$cotizacion['referencia_cotizacion']})";
        }

        $pedido->update($datosActualizar);

        return redirect()
            ->back()
            ->with('success', "Estado de la orden #{$pedido->id} actualizado a " . strtoupper($nuevoEstado) . "{$mensajeExtra}.");
    }

    /**
     * Edición y auditoría integral de un pedido.
     */
    public function update(Request $request, Pedido $pedido): RedirectResponse
    {
        $esAdmin = $this->esAdministrador($request);

        if (! $pedido->puedeSerModificadoPor($esAdmin)) {
            return redirect()
                ->route('pedidos.index')
                ->with('error', "Acceso restringido: La orden #{$pedido->id} ya fue entregada y únicamente el perfil de Administrador puede editarla.");
        }

        $validated = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'fecha' => ['required', 'date'],
            'estado' => ['required', Rule::in(Pedido::ESTADOS)],
            'total' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'peso_kg' => ['required', 'numeric', 'min:0.1', 'max:50000'],
            'ciudad_destino' => ['required', 'string', 'max:100'],
            'costo_envio' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['ciudad_destino'] = trim(strip_tags($validated['ciudad_destino']));
        if (isset($validated['notas'])) {
            $validated['notas'] = trim(strip_tags($validated['notas']));
        }

        $estadoAnterior = $pedido->estado;
        $pedido->fill($validated);

        $mensajeExtra = '';
        if ($validated['estado'] === Pedido::ESTADO_DESPACHADO && ($estadoAnterior !== Pedido::ESTADO_DESPACHADO || $pedido->costo_envio === null || $request->boolean('forzar_recotizacion'))) {
            if (! $request->filled('costo_envio') || $request->boolean('forzar_recotizacion')) {
                $cotizacion = $this->transportadoraService->cotizarEnvio($pedido);
                $pedido->costo_envio = $cotizacion['costo_envio'];
                $pedido->referencia_cotizacion = $cotizacion['referencia_cotizacion'];
                $mensajeExtra = ' Se ejecutó recotización automática de transporte: $' . number_format($cotizacion['costo_envio'], 2) . '.';
            }
        }

        $pedido->save();

        return redirect()
            ->route('pedidos.index')
            ->with('success', "Orden de pedido #{$pedido->id} actualizada correctamente.{$mensajeExtra}");
    }

    /**
     * Elimina un pedido (exclusivo para el perfil Administrador).
     */
    public function destroy(Request $request, Pedido $pedido): RedirectResponse
    {
        if (! $this->esAdministrador($request)) {
            return redirect()
                ->back()
                ->with('error', 'Permiso insuficiente: Solo el Administrador está autorizado para eliminar órdenes de pedido.');
        }

        $id = $pedido->id;
        $pedido->delete();

        return redirect()
            ->route('pedidos.index')
            ->with('success', "Orden de pedido #{$id} eliminada del sistema.");
    }

    /**
     * Registra un nuevo cliente corporativo con sanitización.
     */
    public function storeCliente(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'documento' => ['nullable', 'string', 'max:50', 'unique:clientes,documento'],
            'email' => ['required', 'email', 'max:150', 'unique:clientes,email'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'ciudad' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $datosSanitizados = array_map(
            fn ($val) => is_string($val) ? trim(strip_tags($val)) : $val,
            $validated
        );

        $cliente = Cliente::create($datosSanitizados);

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente corporativo '{$cliente->nombre}' registrado correctamente.");
    }

    /**
     * Actualiza los datos de un cliente (Administrador).
     */
    public function updateCliente(Request $request, Cliente $cliente): RedirectResponse
    {
        if (! $this->esAdministrador($request)) {
            return redirect()
                ->back()
                ->with('error', 'Solo el perfil de Administrador puede modificar el maestro de clientes.');
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'documento' => ['nullable', 'string', 'max:50', Rule::unique('clientes', 'documento')->ignore($cliente->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('clientes', 'email')->ignore($cliente->id)],
            'telefono' => ['nullable', 'string', 'max:50'],
            'ciudad' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $datosSanitizados = array_map(
            fn ($val) => is_string($val) ? trim(strip_tags($val)) : $val,
            $validated
        );

        $cliente->update($datosSanitizados);

        return redirect()
            ->route('clientes.index')
            ->with('success', "Información del cliente '{$cliente->nombre}' actualizada correctamente.");
    }

    /**
     * Elimina un cliente del directorio (Administrador).
     */
    public function destroyCliente(Request $request, Cliente $cliente): RedirectResponse
    {
        if (! $this->esAdministrador($request)) {
            return redirect()
                ->back()
                ->with('error', 'Solo el perfil de Administrador puede eliminar clientes.');
        }

        $nombre = $cliente->nombre;
        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente '{$nombre}' eliminado correctamente.");
    }

    /**
     * Cambia el perfil operativo activo mediante el menú desplegable (Empleado / Administrador).
     */
    public function cambiarRol(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rol' => ['required', Rule::in(['empleado', 'operador', 'admin'])],
        ]);

        $rolNormalizado = $validated['rol'] === 'admin' ? 'admin' : 'empleado';
        $request->session()->put('rol_activo', $rolNormalizado);

        $etiqueta = $rolNormalizado === 'admin'
            ? 'Administrador General (Permisos de edición, recotización y auditoría habilitados)'
            : 'Empleado Operativo (Restricción de seguridad activa sobre órdenes entregadas)';

        return redirect()
            ->back()
            ->with('success', "Sesión operativa cambiada a: {$etiqueta}.");
    }

    /**
     * @return array<string, int>
     */
    private function obtenerConteosEstados(): array
    {
        return [
            'todos' => Pedido::count(),
            Pedido::ESTADO_PENDIENTE => Pedido::where('estado', Pedido::ESTADO_PENDIENTE)->count(),
            Pedido::ESTADO_DESPACHADO => Pedido::where('estado', Pedido::ESTADO_DESPACHADO)->count(),
            Pedido::ESTADO_ENTREGADO => Pedido::where('estado', Pedido::ESTADO_ENTREGADO)->count(),
        ];
    }

    private function esAdministrador(Request $request): bool
    {
        if ($request->user() && $request->user()->is_admin) {
            return true;
        }

        return $request->session()->get('rol_activo', 'empleado') === 'admin';
    }
}
