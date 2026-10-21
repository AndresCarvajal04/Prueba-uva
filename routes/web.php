<?php

use App\Http\Controllers\PedidoWebController;
use Illuminate\Support\Facades\Route;

// Secciones principales de navegación del sistema
Route::get('/', [PedidoWebController::class, 'index'])->name('pedidos.index');
Route::get('/pedidos/crear', [PedidoWebController::class, 'create'])->name('pedidos.crear');
Route::get('/pedidos/{pedido}/editar', [PedidoWebController::class, 'edit'])->name('pedidos.editar');
Route::get('/clientes', [PedidoWebController::class, 'clientesIndex'])->name('clientes.index');
Route::get('/reportes/ventas-mes', [PedidoWebController::class, 'reporteVentas'])->name('reportes.ventas');
Route::get('/monitor-tiempo-real', [PedidoWebController::class, 'monitorReact'])->name('pedidos.monitor');
Route::get('/guia-operativa', [PedidoWebController::class, 'guiaOperativa'])->name('pedidos.guia');

// Acciones CRUD de Pedidos
Route::post('/pedidos', [PedidoWebController::class, 'store'])->name('pedidos.store');
Route::patch('/pedidos/{pedido}/estado', [PedidoWebController::class, 'cambiarEstado'])->name('pedidos.estado');
Route::put('/pedidos/{pedido}', [PedidoWebController::class, 'update'])->name('pedidos.update');
Route::delete('/pedidos/{pedido}', [PedidoWebController::class, 'destroy'])->name('pedidos.destroy');

// Acciones CRUD de Clientes
Route::post('/clientes', [PedidoWebController::class, 'storeCliente'])->name('clientes.store');
Route::put('/clientes/{cliente}', [PedidoWebController::class, 'updateCliente'])->name('clientes.update');
Route::delete('/clientes/{cliente}', [PedidoWebController::class, 'destroyCliente'])->name('clientes.destroy');

// Selector de Perfil (Empleado Operativo / Administrador General)
Route::post('/cambiar-rol', [PedidoWebController::class, 'cambiarRol'])->name('pedidos.cambiar-rol');
