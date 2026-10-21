<?php

use App\Http\Controllers\ClienteWebController;
use App\Http\Controllers\PedidoWebController;
use App\Http\Controllers\ReporteWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — UVA3 Sistema Corporativo de Gestión Logística
|--------------------------------------------------------------------------
| Rutas organizadas bajo el principio de responsabilidad única (SRP):
| - PedidoWebController: Gestión de órdenes, flujo operativo y tablero dinámico.
| - ClienteWebController: Directorio maestro de clientes corporativos (CRUD).
| - ReporteWebController: Consolidado contable de ventas del mes actual.
*/

// Secciones de Órdenes, Tablero Dinámico y Guía Operativa
Route::get('/', [PedidoWebController::class, 'index'])->name('pedidos.index');
Route::get('/pedidos/crear', [PedidoWebController::class, 'create'])->name('pedidos.crear');
Route::get('/pedidos/{pedido}/editar', [PedidoWebController::class, 'edit'])->name('pedidos.editar');
Route::get('/monitor-tiempo-real', [PedidoWebController::class, 'monitorReact'])->name('pedidos.monitor');
Route::get('/guia-operativa', [PedidoWebController::class, 'guiaOperativa'])->name('pedidos.guia');

// Acciones CRUD de Pedidos
Route::post('/pedidos', [PedidoWebController::class, 'store'])->name('pedidos.store');
Route::patch('/pedidos/{pedido}/estado', [PedidoWebController::class, 'cambiarEstado'])->name('pedidos.estado');
Route::put('/pedidos/{pedido}', [PedidoWebController::class, 'update'])->name('pedidos.update');
Route::delete('/pedidos/{pedido}', [PedidoWebController::class, 'destroy'])->name('pedidos.destroy');

// Sección y Acciones CRUD del Maestro de Clientes
Route::get('/clientes', [ClienteWebController::class, 'index'])->name('clientes.index');
Route::post('/clientes', [ClienteWebController::class, 'store'])->name('clientes.store');
Route::put('/clientes/{cliente}', [ClienteWebController::class, 'update'])->name('clientes.update');
Route::delete('/clientes/{cliente}', [ClienteWebController::class, 'destroy'])->name('clientes.destroy');

// Sección de Reporte Mensual de Ventas
Route::get('/reportes/ventas-mes', [ReporteWebController::class, 'ventasMes'])->name('reportes.ventas');

// Selector de Perfil (Empleado Operativo / Administrador General)
Route::post('/cambiar-rol', [PedidoWebController::class, 'cambiarRol'])->name('pedidos.cambiar-rol');
