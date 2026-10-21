<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\ReporteVentasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador Web de Reportes Comerciales.
 *
 * Responsabilidad única (SRP): presentar el informe consolidado de ventas
 * por cliente en el mes actual ordenado de mayor a menor.
 */
class ReporteWebController extends Controller
{
    public function __construct(
        private readonly ReporteVentasService $reporteVentasService
    ) {}

    /**
     * Muestra la sección del Reporte Consolidado de Ventas del Mes Actual.
     */
    public function ventasMes(Request $request): View
    {
        $reporteMensual = $this->reporteVentasService->obtenerVentasMesActual();

        return view('reportes.ventas', [
            'reporteMensual' => $reporteMensual,
            'mesActualNombre' => Carbon::now()->translatedFormat('F Y'),
            'inicioMes' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'finMes' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'conteosEstados' => [
                'todos' => Pedido::count(),
                Pedido::ESTADO_PENDIENTE => Pedido::where('estado', Pedido::ESTADO_PENDIENTE)->count(),
                Pedido::ESTADO_DESPACHADO => Pedido::where('estado', Pedido::ESTADO_DESPACHADO)->count(),
                Pedido::ESTADO_ENTREGADO => Pedido::where('estado', Pedido::ESTADO_ENTREGADO)->count(),
            ],
            'esAdmin' => ($request->user() && $request->user()->is_admin)
                || $request->session()->get('rol_activo', 'empleado') === 'admin',
            'seccionActiva' => 'reportes',
        ]);
    }
}
