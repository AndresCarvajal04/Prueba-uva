<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePedidoRequest;
use App\Http\Requests\UpdatePedidoRequest;
use App\Http\Resources\ClienteResource;
use App\Http\Resources\PedidoResource;
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
     * Responde en JSON con recursos serializados de forma controlada.
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
            'data' => PedidoResource::collection($pedidos)->resolve($request),
        ]);
    }

    /**
     * Crea un nuevo pedido vía API utilizando StorePedidoRequest.
     */
    public function store(StorePedidoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $estadoInicial = $validated['estado'] ?? Pedido::ESTADO_PENDIENTE;

        $pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'fecha' => $validated['fecha'] ?? Carbon::now()->toDateString(),
            'estado' => $estadoInicial,
            'total' => $validated['total'],
            'peso_kg' => $validated['peso_kg'] ?? 1.00,
            'ciudad_destino' => ! empty($validated['ciudad_destino'])
                ? $validated['ciudad_destino']
                : $cliente->ciudad,
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

        $pedidoActualizado = $pedido->fresh('cliente');

        return response()->json([
            'ok' => true,
            'message' => 'Orden de pedido creada exitosamente.',
            'cotizacion' => $cotizacionInfo,
            'data' => (new PedidoResource($pedidoActualizado))->resolve($request),
        ], 201);
    }

    /**
     * Cambia el estado de un pedido validando permisos de rol y cotizando envío al despachar.
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
        $pedidoActualizado = $pedido->fresh('cliente');

        return response()->json([
            'ok' => true,
            'message' => $cotizacionInfo
                ? "Orden #{$pedido->id} despachada. Costo de envío liquidado: $" . number_format($cotizacionInfo['costo_envio'], 2)
                : "Estado de la orden #{$pedido->id} actualizado a '{$nuevoEstado}'.",
            'cotizacion' => $cotizacionInfo,
            'data' => (new PedidoResource($pedidoActualizado))->resolve($request),
        ]);
    }

    /**
     * Edición completa de un pedido utilizando UpdatePedidoRequest.
     */
    public function update(UpdatePedidoRequest $request, Pedido $pedido): JsonResponse
    {
        $esAdmin = $this->esAdministrador($request);

        if (! $pedido->puedeSerModificadoPor($esAdmin)) {
            return response()->json([
                'ok' => false,
                'message' => 'Los pedidos entregados no se pueden modificar. Solo el administrador puede corregir cualquier pedido.',
            ], 403);
        }

        $validated = $request->validated();

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
        $pedidoActualizado = $pedido->fresh('cliente');

        return response()->json([
            'ok' => true,
            'message' => "Orden #{$pedido->id} actualizada exitosamente.",
            'cotizacion' => $cotizacionInfo,
            'data' => (new PedidoResource($pedidoActualizado))->resolve($request),
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
    public function clientes(Request $request): JsonResponse
    {
        $clientes = Cliente::orderBy('nombre')->get();

        return response()->json([
            'ok' => true,
            'data' => ClienteResource::collection($clientes)->resolve($request),
        ]);
    }

    private function esAdministrador(Request $request): bool
    {
        if ($request->user() && $request->user()->is_admin) {
            return true;
        }

        if ($request->hasSession() && $request->session()->get('rol_activo') === 'admin') {
            return true;
        }

        if (app()->environment(['local', 'testing'])) {
            $headerAdmin = $request->header('X-Admin-Mode');
            if ($headerAdmin !== null) {
                return in_array(strtolower($headerAdmin), ['1', 'true', 'admin', 'yes'], true);
            }
        }

        return false;
    }
}
