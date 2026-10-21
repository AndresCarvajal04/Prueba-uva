<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePedidoRequest;
use App\Http\Requests\UpdatePedidoRequest;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\ReporteVentasService;
use App\Services\TransportadoraService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador Web de Órdenes de Pedido.
 *
 * Responsabilidad única (SRP): gestionar el ciclo de vida de las órdenes de pedido
 * (listado, creación, transición de estado, auditoría/edición y eliminación),
 * además de la navegación de perfil y tablero operativo.
 */
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
     * Vista dedicada para Edición y Auditoría de una Orden de Pedido.
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
     * Sección 5: Tablero Dinámico de Operaciones en Tiempo Real (React 18).
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
     * Sección 6: Manual Operativo Paso a Paso y Matriz de Roles.
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
     * Registra una nueva orden de pedido utilizando StorePedidoRequest.
     */
    public function store(StorePedidoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $estado = $validated['estado'] ?? Pedido::ESTADO_PENDIENTE;
        $ciudadDestino = ! empty($validated['ciudad_destino'])
            ? $validated['ciudad_destino']
            : $cliente->ciudad;

        $pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'fecha' => $validated['fecha'] ?? Carbon::now()->toDateString(),
            'estado' => $estado,
            'total' => $validated['total'],
            'peso_kg' => $validated['peso_kg'] ?? 5.00,
            'ciudad_destino' => $ciudadDestino,
            'notas' => $validated['notas'] ?? null,
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
     * Actualiza el estado operativo de un pedido y cotiza envío si pasa a despachado.
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
     * Edición y auditoría integral de un pedido utilizando UpdatePedidoRequest.
     */
    public function update(UpdatePedidoRequest $request, Pedido $pedido): RedirectResponse
    {
        $esAdmin = $this->esAdministrador($request);

        if (! $pedido->puedeSerModificadoPor($esAdmin)) {
            return redirect()
                ->route('pedidos.index')
                ->with('error', "Acceso restringido: La orden #{$pedido->id} ya fue entregada y únicamente el perfil de Administrador puede editarla.");
        }

        $validated = $request->validated();
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
