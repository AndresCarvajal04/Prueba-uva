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
     * Vista principal del módulo de pedidos:
     * - Listado con filtro por estado (Requisito 2)
     * - Formulario de creación y cambio de estado (Requisito 2)
     * - Reporte mensual de ventas por cliente de mayor a menor (Requisito 4)
     * - Gestión de rol Operador / Administrador (Requisito 6)
     * - Integración con vista React consumiendo GET /api/pedidos (Requisito 7)
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
        $clientes = Cliente::orderBy('nombre')->get();
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

        return view('pedidos.index', [
            'pedidos' => $pedidos,
            'clientes' => $clientes,
            'estadoFiltro' => $estadoFiltro,
            'conteosEstados' => $conteosEstados,
            'reporteMensual' => $reporteMensual,
            'mesActualNombre' => Carbon::now()->translatedFormat('F Y'),
            'esAdmin' => $esAdmin,
            'pedidoEditar' => $pedidoEditar,
        ]);
    }

    /**
     * Crea un nuevo pedido desde el formulario web (Requisito 2).
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

        $mensaje = "Pedido #{$pedido->id} creado para {$cliente->nombre}.";

        if ($estado === Pedido::ESTADO_DESPACHADO) {
            $cotizacion = $this->transportadoraService->cotizarEnvio($pedido);
            $pedido->update([
                'costo_envio' => $cotizacion['costo_envio'],
                'referencia_cotizacion' => $cotizacion['referencia_cotizacion'],
            ]);
            $mensaje .= ' Costo de envío calculado: $' . number_format($cotizacion['costo_envio'], 2) . " ({$cotizacion['referencia_cotizacion']}).";
        }

        return redirect()
            ->route('pedidos.index', ['estado' => $request->query('filtro_actual', 'todos')])
            ->with('success', $mensaje);
    }

    /**
     * Cambia el estado de un pedido desde el listado web (Requisitos 2, 5 y 6).
     */
    public function cambiarEstado(Request $request, Pedido $pedido): RedirectResponse
    {
        $esAdmin = $this->esAdministrador($request);

        if (! $pedido->puedeSerModificadoPor($esAdmin)) {
            return redirect()
                ->back()
                ->with('error', "Bloqueo de seguridad (Requisito 6): El pedido #{$pedido->id} ya fue ENTREGADO y no puede modificarse por un operador. Active el modo Administrador para corregir este pedido.");
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(Pedido::ESTADOS)],
        ]);

        $nuevoEstado = $validated['estado'];
        $datosActualizar = ['estado' => $nuevoEstado];
        $mensajeExtra = '';

        // Requisito 5: Al marcar como despachado, cotizar costo de envío en API de transportadora
        if ($nuevoEstado === Pedido::ESTADO_DESPACHADO) {
            $cotizacion = $this->transportadoraService->cotizarEnvio($pedido);
            $datosActualizar['costo_envio'] = $cotizacion['costo_envio'];
            $datosActualizar['referencia_cotizacion'] = $cotizacion['referencia_cotizacion'];
            $mensajeExtra = ' | Costo de envío cotizado: $' . number_format($cotizacion['costo_envio'], 2)
                . " (Ref: {$cotizacion['referencia_cotizacion']} — {$cotizacion['mensaje']})";
        }

        $pedido->update($datosActualizar);

        return redirect()
            ->back()
            ->with('success', "Estado del pedido #{$pedido->id} actualizado a '" . strtoupper($nuevoEstado) . "'{$mensajeExtra}");
    }

    /**
     * Corrección integral de un pedido (Requisito 6: El administrador puede corregir cualquier pedido).
     */
    public function update(Request $request, Pedido $pedido): RedirectResponse
    {
        $esAdmin = $this->esAdministrador($request);

        if (! $pedido->puedeSerModificadoPor($esAdmin)) {
            return redirect()
                ->route('pedidos.index')
                ->with('error', "No autorizado: El pedido #{$pedido->id} está entregado y solo el administrador puede corregirlo.");
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
        if ($validated['estado'] === Pedido::ESTADO_DESPACHADO && ($estadoAnterior !== Pedido::ESTADO_DESPACHADO || $pedido->costo_envio === null)) {
            if (! $request->filled('costo_envio')) {
                $cotizacion = $this->transportadoraService->cotizarEnvio($pedido);
                $pedido->costo_envio = $cotizacion['costo_envio'];
                $pedido->referencia_cotizacion = $cotizacion['referencia_cotizacion'];
                $mensajeExtra = ' Se recotizó el envío automáticamente: $' . number_format($cotizacion['costo_envio'], 2) . '.';
            }
        }

        $pedido->save();

        return redirect()
            ->route('pedidos.index')
            ->with('success', "Pedido #{$pedido->id} corregido exitosamente por " . ($esAdmin ? 'el Administrador' : 'el Operador') . ".{$mensajeExtra}");
    }

    /**
     * Alterna entre rol Operador y Administrador en la sesión para evaluar el Requisito 6.
     */
    public function cambiarRol(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rol' => ['required', Rule::in(['operador', 'admin'])],
        ]);

        $request->session()->put('rol_activo', $validated['rol']);

        $nombreRol = $validated['rol'] === 'admin'
            ? 'Administrador (puede corregir cualquier pedido, incluidos entregados)'
            : 'Operador Logístico (los pedidos entregados están bloqueados)';

        return redirect()
            ->back()
            ->with('success', "Rol activo cambiado a: {$nombreRol}.");
    }

    private function esAdministrador(Request $request): bool
    {
        if ($request->user() && $request->user()->is_admin) {
            return true;
        }

        if ($request->boolean('es_admin')) {
            return true;
        }

        return $request->session()->get('rol_activo', 'operador') === 'admin';
    }
}
