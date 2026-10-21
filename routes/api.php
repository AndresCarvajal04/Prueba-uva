<?php

use App\Http\Controllers\Api\PedidoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — UVA3 Sistema de Gestión Logística
|--------------------------------------------------------------------------
*/

Route::get('/pedidos', [PedidoController::class, 'index'])->name('api.pedidos.index');
Route::post('/pedidos', [PedidoController::class, 'store'])->name('api.pedidos.store');
Route::patch('/pedidos/{pedido}/estado', [PedidoController::class, 'cambiarEstado'])->name('api.pedidos.estado');
Route::put('/pedidos/{pedido}', [PedidoController::class, 'update'])->name('api.pedidos.update');
Route::delete('/pedidos/{pedido}', [PedidoController::class, 'destroy'])->name('api.pedidos.destroy');

Route::get('/reportes/ventas-mes', [PedidoController::class, 'reporteVentasMes'])->name('api.reportes.ventas-mes');
Route::get('/clientes', [PedidoController::class, 'clientes'])->name('api.clientes.index');
