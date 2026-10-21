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
     * Panel corporativo principal de gestión de pedidos, clientes y reportes.
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
        $clientes = Cliente::withCount('pedidos')->orderBy('nombre')->get();
        $reporteMensual = $this->reporteVentasService->obtenerVentasMesActual();

        $conteosEstados = [
            'todos' => Pedido::count(),
            Pedido::ESTADO_PENDIENTE => Pedido::where('estado', Pedido::ESTADO_PENDIENTE)->count(),
            Pedido::ESTADO_DESPACHADO => Pedido::where('estado', Pedido::ESTADO_DESPACHADO)->count(),
            Pedido::ESTADO_ENTREGADO => Pedido::where('estado', Pedido::ESTADO_ENTREGADO)->count(),
        ];

        $esAdmin = $this->esAdministrador($request);

        $pedidoEditar = null;
        if ($request->filled('editar')) {
            $pedidoEditar = Pedido::with('cliente')->find($request->query('editar'));
        }

        $clienteEditar = null;
        if ($request->filled('editar_cliente')) {
            $clienteEditar = Cliente::find($request->query('editar_cliente'));
        }

        return view('pedidos.index', [
            'pedidos' => $pedidos,
            'clientes' => $clientes,
            'estadoFiltro' => $estadoFiltro,
            'conteosEstados' => $conteosEstados,
            'reporteMensual' => $reporteMensual,
            'mesActualNombre' => Carbon::now()->translatedFormat('F Y'),
            'esAdmin' => $esAdmin,
            'pedidoEditar' => $pedidoEditar,
            'clienteEditar' => $clienteEditar,
        ]);
    }

    /**
     * Registra una nueva orden de pedido.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'fecha' => ['required', 'date'],
            'total' => ['required', 'numeric', 'min:1'],
            'peso_kg' => ['required', 'numeric', 'min:0.1'],
            'ciudad_destino' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(Pedido::ESTADOS)],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $estado = $validated['estado'] ?? Pedido::ESTADO_PENDIENTE;
        $ciudadDestino = ! empty($validated['ciudad_destino'])
            ? $validated['ciudad_destino']
            : $cliente->ciudad;

        $pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'fecha' => $validated['fecha'],
            'estado' => $estado,
            'total' => $validated['total'],
            'peso_kg' => $validated['peso_kg'],
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
            ->route('pedidos.index', ['estado' => $request->query('filtro_actual', 'todos')])
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
                . " (Ref: {$cotizacion['referencia_cotizacion']})";
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
            'cliente_id' => ['required', 'exists:clientes,id'],
            'fecha' => ['required', 'date'],
            'estado' => ['required', Rule::in(Pedido::ESTADOS)],
            'total' => ['required', 'numeric', 'min:0'],
            'peso_kg' => ['required', 'numeric', 'min:0.1'],
            'ciudad_destino' => ['required', 'string', 'max:100'],
            'costo_envio' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

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
            ->route('pedidos.index', ['estado' => $request->input('filtro_actual', 'todos')])
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
     * Registra un nuevo cliente corporativo.
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

        $cliente = Cliente::create($validated);

        return redirect()
            ->route('pedidos.index')
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

        $cliente->update($validated);

        return redirect()
            ->route('pedidos.index')
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
            ->route('pedidos.index')
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

    private function esAdministrador(Request $request): bool
    {
        if ($request->user() && $request->user()->is_admin) {
            return true;
        }

        if ($request->boolean('es_admin')) {
            return true;
        }

        return $request->session()->get('rol_activo', 'empleado') === 'admin';
    }
}
