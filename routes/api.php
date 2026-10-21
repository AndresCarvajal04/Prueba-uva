<?php

use App\Http\Controllers\Api\PedidoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — UVA3 Módulo de Pedidos
|--------------------------------------------------------------------------
| Requisito 3: GET /api/pedidos?estado=pendiente que responda en JSON.
| Endpoints adicionales para crear, cambiar estado, corregir pedidos y
| consultar el reporte mensual desde el cliente en React (Requisito 7).
*/

Route::get('/pedidos', [PedidoController::class, 'index'])->name('api.pedidos.index');
Route::post('/pedidos', [PedidoController::class, 'store'])->name('api.pedidos.store');
Route::patch('/pedidos/{pedido}/estado', [PedidoController::class, 'cambiarEstado'])->name('api.pedidos.estado');
Route::put('/pedidos/{pedido}', [PedidoController::class, 'update'])->name('api.pedidos.update');

Route::get('/reportes/ventas-mes', [PedidoController::class, 'reporteVentasMes'])->name('api.reportes.ventas-mes');
Route::get('/clientes', [PedidoController::class, 'clientes'])->name('api.clientes.index');
