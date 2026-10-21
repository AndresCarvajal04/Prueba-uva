<?php

use App\Http\Controllers\PedidoWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PedidoWebController::class, 'index'])->name('pedidos.index');

// CRUD de Pedidos
Route::post('/pedidos', [PedidoWebController::class, 'store'])->name('pedidos.store');
Route::patch('/pedidos/{pedido}/estado', [PedidoWebController::class, 'cambiarEstado'])->name('pedidos.estado');
Route::put('/pedidos/{pedido}', [PedidoWebController::class, 'update'])->name('pedidos.update');
Route::delete('/pedidos/{pedido}', [PedidoWebController::class, 'destroy'])->name('pedidos.destroy');

// CRUD de Clientes (Administración)
Route::post('/clientes', [PedidoWebController::class, 'storeCliente'])->name('clientes.store');
Route::put('/clientes/{cliente}', [PedidoWebController::class, 'updateCliente'])->name('clientes.update');
Route::delete('/clientes/{cliente}', [PedidoWebController::class, 'destroyCliente'])->name('clientes.destroy');

// Selector de Perfil (Administrador / Empleado)
Route::post('/cambiar-rol', [PedidoWebController::class, 'cambiarRol'])->name('pedidos.cambiar-rol');
