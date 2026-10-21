import React, { useState, useEffect, useCallback } from 'react';

/**
 * Tablero Dinámico de Órdenes (React 18)
 * Consume de forma controlada el servicio interno de pedidos sin exponer estructuras JSON crudas.
 */
export default function PedidosReactApp({ esAdminSesion = false }) {
    const [estadoFiltro, setEstadoFiltro] = useState('pendiente');
    const [pedidos, setPedidos] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [mensaje, setMensaje] = useState(null);

    const cargarPedidos = useCallback(async () => {
        setLoading(true);
        setError(null);
        const url = estadoFiltro === 'todos'
            ? '/api/pedidos'
            : `/api/pedidos?estado=${encodeURIComponent(estadoFiltro)}`;

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Admin-Mode': esAdminSesion ? '1' : '0',
                },
            });
            const json = await response.json();
            if (!response.ok || !json.ok) {
                throw new Error(json.message || 'No fue posible recuperar el listado de órdenes.');
            }
            setPedidos(json.data || []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, [estadoFiltro, esAdminSesion]);

    useEffect(() => {
        cargarPedidos();
    }, [cargarPedidos]);

    const cambiarEstadoPedido = async (pedidoId, nuevoEstado) => {
        setError(null);
        setMensaje(null);
        try {
            const response = await fetch(`/api/pedidos/${pedidoId}/estado`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Admin-Mode': esAdminSesion ? '1' : '0',
                },
                body: JSON.stringify({ estado: nuevoEstado }),
            });
            const json = await response.json();
            if (!response.ok || !json.ok) {
                throw new Error(json.message || 'No se pudo actualizar el estado de la orden.');
            }
            setMensaje(json.message);
            await cargarPedidos();
        } catch (err) {
            setError(err.message);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap gap-2">
                {['pendiente', 'despachado', 'entregado', 'todos'].map((est) => (
                    <button
                        key={est}
                        type="button"
                        onClick={() => setEstadoFiltro(est)}
                        className={`px-3.5 py-1.5 rounded text-xs font-semibold uppercase tracking-wide ${
                            estadoFiltro === est
                                ? 'bg-slate-900 text-white'
                                : 'bg-white text-slate-700 border border-slate-300'
                        }`}
                    >
                        {est}
                    </button>
                ))}
            </div>

            {error && (
                <div className="p-3 rounded bg-red-50 border border-red-200 text-red-700 text-xs">
                    {error}
                </div>
            )}
            {mensaje && (
                <div className="p-3 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                    {mensaje}
                </div>
            )}

            {loading ? (
                <p className="text-xs text-slate-500 py-6 text-center">Cargando información operativa...</p>
            ) : (
                <div className="overflow-x-auto bg-white rounded border border-slate-200">
                    <table className="min-w-full text-xs">
                        <thead className="bg-slate-50 text-slate-600 uppercase">
                            <tr>
                                <th className="px-4 py-2.5 text-left">Orden</th>
                                <th className="px-4 py-2.5 text-left">Cliente</th>
                                <th className="px-4 py-2.5 text-left">Fecha</th>
                                <th className="px-4 py-2.5 text-left">Destino / Peso</th>
                                <th className="px-4 py-2.5 text-right">Valor Venta</th>
                                <th className="px-4 py-2.5 text-right">Flete</th>
                                <th className="px-4 py-2.5 text-center">Estado</th>
                                <th className="px-4 py-2.5 text-right">Transición</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {pedidos.map((p) => {
                                const bloqueado = p.estado === 'entregado' && !esAdminSesion;
                                return (
                                    <tr key={p.id}>
                                        <td className="px-4 py-2.5 font-semibold">#{p.id}</td>
                                        <td className="px-4 py-2.5">{p.cliente?.nombre}</td>
                                        <td className="px-4 py-2.5">{p.fecha}</td>
                                        <td className="px-4 py-2.5">{p.ciudad_destino} ({p.peso_kg} kg)</td>
                                        <td className="px-4 py-2.5 text-right font-medium">${Number(p.total).toLocaleString('es-CO')}</td>
                                        <td className="px-4 py-2.5 text-right">
                                            {p.costo_envio !== null ? `$${Number(p.costo_envio).toLocaleString('es-CO')}` : 'Pendiente'}
                                        </td>
                                        <td className="px-4 py-2.5 text-center uppercase font-bold">{p.estado}</td>
                                        <td className="px-4 py-2.5 text-right">
                                            <select
                                                disabled={bloqueado}
                                                value={p.estado}
                                                onChange={(e) => cambiarEstadoPedido(p.id, e.target.value)}
                                                className="border border-slate-300 rounded px-2 py-1 text-xs disabled:opacity-50"
                                            >
                                                <option value="pendiente">Pendiente</option>
                                                <option value="despachado">Despachado</option>
                                                <option value="entregado">Entregado</option>
                                            </select>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
