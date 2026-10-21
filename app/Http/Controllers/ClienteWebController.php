<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador Web de Clientes Corporativos.
 *
 * Responsabilidad única (SRP): gestionar el directorio maestro de clientes
 * (listado, registro, actualización y eliminación).
 */
class ClienteWebController extends Controller
{
    /**
     * Muestra la sección del Directorio Maestro de Clientes Corporativos.
     */
    public function index(Request $request): View
    {
        $clientes = Cliente::withCount('pedidos')->orderBy('nombre')->get();

        $clienteEditar = null;
        if ($request->filled('editar_cliente')) {
            $clienteEditar = Cliente::find($request->query('editar_cliente'));
        }

        return view('clientes.index', [
            'clientes' => $clientes,
            'clienteEditar' => $clienteEditar,
            'conteosEstados' => $this->obtenerConteosEstados(),
            'esAdmin' => $this->esAdministrador($request),
            'seccionActiva' => 'clientes',
        ]);
    }

    /**
     * Registra un nuevo cliente en el directorio corporativo.
     */
    public function store(ClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente corporativo '{$cliente->nombre}' registrado correctamente.");
    }

    /**
     * Actualiza la información de un cliente existente (exclusivo para Administrador).
     */
    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        if (! $this->esAdministrador($request)) {
            return redirect()
                ->back()
                ->with('error', 'Solo el perfil de Administrador puede modificar el maestro de clientes.');
        }

        $cliente->update($request->validated());

        return redirect()
            ->route('clientes.index')
            ->with('success', "Información del cliente '{$cliente->nombre}' actualizada correctamente.");
    }

    /**
     * Elimina un cliente del directorio (exclusivo para Administrador).
     */
    public function destroy(Request $request, Cliente $cliente): RedirectResponse
    {
        if (! $this->esAdministrador($request)) {
            return redirect()
                ->back()
                ->with('error', 'Solo el perfil de Administrador puede eliminar clientes.');
        }

        $nombre = $cliente->nombre;
        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente '{$nombre}' eliminado correctamente.");
    }

    /**
     * Obtiene los conteos globales por estado para la barra de navegación.
     *
     * @return array<string, int>
     */
    private function obtenerConteosEstados(): array
    {
        return [
            'todos' => Pedido::count(),
            Pedido::ESTADO_PENDIENTE => Pedido::where('estado', Pedido::ESTADO_PENDIENTE)->count(),
            Pedido::ESTADO_DESPACHADO => Pedido::where('estado', Pedido::ESTADO_DESPACHADO)->count(),
            Pedido::ESTADO_ENTREGADO => Pedido::where('estado', Pedido::ESTADO_ENTREGADO)->count(),
        ];
    }

    /**
     * Determina si la sesión actual opera con privilegios de Administrador.
     */
    private function esAdministrador(Request $request): bool
    {
        if ($request->user() && $request->user()->is_admin) {
            return true;
        }

        return $request->session()->get('rol_activo', 'empleado') === 'admin';
    }
}
