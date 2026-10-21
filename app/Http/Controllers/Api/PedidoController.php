<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\ReporteVentasService;
use App\Services\TransportadoraService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public function __construct(
        private readonly TransportadoraService $transportadoraService,
        private readonly ReporteVentasService $reporteVentasService
    ) {}

    /**
     * GET /api/pedidos?estado=pendiente
     * Responde en JSON con el listado de pedidos (opcionalmente filtrado por estado).
     */
    public function index(Request $request): JsonResponse
    {
        $estado = $request->query('estado');

        if ($estado !== null && $estado !== '' && $estado !== 'todos') {
            if (! in_array($estado, Pedido::ESTADOS, true)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El estado solicitado no es válido. Valores permitidos: pendiente, despachado, entregado.',
                    'estados_permitidos' => Pedido::ESTADOS,
                ], 422);
            }
        }

        $query = Pedido::with('cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($estado !== null && $estado !== '' && $estado !== 'todos') {
            $query->where('estado', $estado);
        }

        $pedidos = $query->get();

        return response()->json([
            'ok' => true,
            'filtro_estado' => ($estado !== null && $estado !== '') ? $estado : 'todos',
            'total_registros' => $pedidos->count(),
            'data' => $pedidos,
        ]);
    }

    /**
     * Crea un nuevo pedido vía API.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'fecha' => ['nullable', 'date'],
            'total' => ['required', 'numeric', 'min:1'],
            'peso_kg' => ['nullable', 'numeric', 'min:0.1'],
            'ciudad_destino' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(Pedido::ESTADOS)],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $estadoInicial = $validated['estado'] ?? Pedido::ESTADO_PENDIENTE;

        $pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'fecha' => $validated['fecha'] ?? Carbon::now()->toDateString(),
            'estado' => $estadoInicial,
            'total' => $validated['total'],
            'peso_kg' => $validated['peso_kg'] ?? 1.00,
            'ciudad_destino' => $validated['ciudad_destino'] ?? $cliente->ciudad,
            'notas' => $validated['notas'] ?? null,
        ]);

        $cotizacionInfo = null;
        if ($estadoInicial === Pedido::ESTADO_DESPACHADO) {
            $cotizacionInfo = $this->transportadoraService->cotizarEnvio($pedido);
            $pedido->update([
                'costo_envio' => $cotizacionInfo['costo_envio'],
                'referencia_cotizacion' => $cotizacionInfo['referencia_cotizacion'],
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Orden de pedido creada exitosamente.',
            'cotizacion' => $cotizacionInfo,
            'data' => $pedido->fresh('cliente'),
        ], 201);
    }

    /**
     * Cambia el estado de un pedido.
     * - Si el pedido está 'entregado', bloquea modificaciones salvo que sea Administrador.
     * - Si el nuevo estado es 'despachado', cotiza el envío en la API de la transportadora.
     */
    public function cambiarEstado(Request $request, Pedido $pedido): JsonResponse
    {
        $esAdmin = $this->esAdministrador($request);

        if (! $pedido->puedeSerModificadoPor($esAdmin)) {
            return response()->json([
                'ok' => false,
                'message' => 'Los pedidos entregados no se pueden modificar. Solo el administrador puede corregir un pedido entregado.',
            ], 403);
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(Pedido::ESTADOS)],
        ]);

        $nuevoEstado = $validated['estado'];
        $cotizacionInfo = null;

        $datosActualizar = [
            'estado' => $nuevoEstado,
        ];

        if ($nuevoEstado === Pedido::ESTADO_DESPACHADO) {
            $cotizacionInfo = $this->transportadoraService->cotizarEnvio($pedido);
            $datosActualizar['costo_envio'] = $cotizacionInfo['costo_envio'];
            $datosActualizar['referencia_cotizacion'] = $cotizacionInfo['referencia_cotizacion'];
        }

        $pedido->update($datosActualizar);

        return response()->json([
            'ok' => true,
            'message' => $cotizacionInfo
                ? "Orden #{$pedido->id} despachada. Costo de envío liquidado: $" . number_format($cotizacionInfo['costo_envio'], 2)
                : "Estado de la orden #{$pedido->id} actualizado a '{$nuevoEstado}'.",
            'cotizacion' => $cotizacionInfo,
            'data' => $pedido->fresh('cliente'),
        ]);
    }

    /**
     * Edición y corrección completa de un pedido.
     */
    public function update(Request $request, Pedido $pedido): JsonResponse
    {
        $esAdmin = $this->esAdministrador($request);

        if (! $pedido->puedeSerModificadoPor($esAdmin)) {
            return response()->json([
                'ok' => false,
                'message' => 'Los pedidos entregados no se pueden modificar. Solo el administrador puede corregir cualquier pedido.',
            ], 403);
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

        $cotizacionInfo = null;
        if ($validated['estado'] === Pedido::ESTADO_DESPACHADO && ($estadoAnterior !== Pedido::ESTADO_DESPACHADO || $pedido->costo_envio === null)) {
            if (! $request->filled('costo_envio')) {
                $cotizacionInfo = $this->transportadoraService->cotizarEnvio($pedido);
                $pedido->costo_envio = $cotizacionInfo['costo_envio'];
                $pedido->referencia_cotizacion = $cotizacionInfo['referencia_cotizacion'];
            }
        }

        $pedido->save();

        return response()->json([
            'ok' => true,
            'message' => "Orden #{$pedido->id} actualizada exitosamente.",
            'cotizacion' => $cotizacionInfo,
            'data' => $pedido->fresh('cliente'),
        ]);
    }

    /**
     * Elimina un pedido vía API (solo Administrador).
     */
    public function destroy(Request $request, Pedido $pedido): JsonResponse
    {
        if (! $this->esAdministrador($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo el administrador está autorizado para eliminar pedidos.',
            ], 403);
        }

        $id = $pedido->id;
        $pedido->delete();

        return response()->json([
            'ok' => true,
            'message' => "Orden #{$id} eliminada del sistema.",
        ]);
    }

    /**
     * Reporte con el total vendido por cliente en el mes actual, de mayor a menor.
     */
    public function reporteVentasMes(): JsonResponse
    {
        $ventas = $this->reporteVentasService->obtenerVentasMesActual();

        return response()->json([
            'ok' => true,
            'mes' => Carbon::now()->translatedFormat('F Y'),
            'rango' => [
                'inicio' => Carbon::now()->startOfMonth()->toDateString(),
                'fin' => Carbon::now()->endOfMonth()->toDateString(),
            ],
            'gran_total_vendido' => round((float) $ventas->sum('total_vendido'), 2),
            'data' => $ventas,
        ]);
    }

    /**
     * Listado de clientes corporativos.
     */
    public function clientes(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => Cliente::orderBy('nombre')->get(),
        ]);
    }

    private function esAdministrador(Request $request): bool
    {
        if ($request->user() && $request->user()->is_admin) {
            return true;
        }

        $headerAdmin = $request->header('X-Admin-Mode');
        if ($headerAdmin !== null) {
            return in_array(strtolower($headerAdmin), ['1', 'true', 'admin', 'yes'], true);
        }

        if ($request->has('es_admin')) {
            return $request->boolean('es_admin');
        }

        if ($request->hasSession() && $request->session()->get('rol_activo') === 'admin') {
            return true;
        }

        return false;
    }
}
