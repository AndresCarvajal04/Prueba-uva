<?php

use App\Http\Controllers\PedidoWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PedidoWebController::class, 'index'])->name('pedidos.index');
Route::post('/pedidos', [PedidoWebController::class, 'store'])->name('pedidos.store');
Route::patch('/pedidos/{pedido}/estado', [PedidoWebController::class, 'cambiarEstado'])->name('pedidos.estado');
Route::put('/pedidos/{pedido}', [PedidoWebController::class, 'update'])->name('pedidos.update');
Route::post('/cambiar-rol', [PedidoWebController::class, 'cambiarRol'])->name('pedidos.cambiar-rol');
