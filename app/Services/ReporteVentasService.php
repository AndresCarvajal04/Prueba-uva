<?php

namespace App\Services;

use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteVentasService
{
    /**
     * Requisito 4:
     * Obtiene el reporte con el total vendido por cliente en el mes actual,
     * ordenado de mayor a menor.
     *
     * @return Collection<int, object>
     */
    public function obtenerVentasMesActual(?Carbon $fechaReferencia = null): Collection
    {
        $referencia = $fechaReferencia ? $fechaReferencia->copy() : Carbon::now();
        $inicioMes = $referencia->copy()->startOfMonth()->toDateString();
        $finMes = $referencia->copy()->endOfMonth()->toDateString();

        $resultados = Cliente::query()
            ->select([
                'clientes.id as cliente_id',
                'clientes.nombre as cliente_nombre',
                'clientes.documento as cliente_documento',
                'clientes.ciudad as cliente_ciudad',
                'clientes.email as cliente_email',
                DB::raw('COUNT(pedidos.id) as cantidad_pedidos'),
                DB::raw('COALESCE(SUM(pedidos.total), 0) as total_vendido'),
                DB::raw('COALESCE(SUM(pedidos.costo_envio), 0) as total_costo_envio'),
            ])
            ->join('pedidos', 'pedidos.cliente_id', '=', 'clientes.id')
            ->whereBetween('pedidos.fecha', [$inicioMes, $finMes])
            ->groupBy(
                'clientes.id',
                'clientes.nombre',
                'clientes.documento',
                'clientes.ciudad',
                'clientes.email'
            )
            ->orderByDesc('total_vendido')
            ->get();

        $granTotalMes = (float) $resultados->sum('total_vendido');

        return $resultados->map(function ($fila, int $index) use ($granTotalMes) {
            $totalVendido = round((float) $fila->total_vendido, 2);
            $totalEnvio = round((float) $fila->total_costo_envio, 2);
            $participacion = $granTotalMes > 0
                ? round(($totalVendido / $granTotalMes) * 100, 1)
                : 0.0;

            return (object) [
                'posicion' => $index + 1,
                'cliente_id' => (int) $fila->cliente_id,
                'cliente_nombre' => $fila->cliente_nombre,
                'cliente_documento' => $fila->cliente_documento,
                'cliente_ciudad' => $fila->cliente_ciudad,
                'cliente_email' => $fila->cliente_email,
                'cantidad_pedidos' => (int) $fila->cantidad_pedidos,
                'total_vendido' => $totalVendido,
                'total_costo_envio' => $totalEnvio,
                'participacion_porcentaje' => $participacion,
            ];
        });
    }
}
