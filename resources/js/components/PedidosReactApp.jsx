import React, { useState, useEffect, useCallback } from 'react';

/**
 * Consola Interactiva de Órdenes (React 18)
 * Consume el endpoint JSON GET /api/pedidos?estado=...
 */
export default function PedidosReactApp({ initialAdminMode = false }) {
    const [estadoFiltro, setEstadoFiltro] = useState('pendiente');
    const [pedidos, setPedidos] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [mensaje, setMensaje] = useState(null);
    const [adminMode, setAdminMode] = useState(initialAdminMode);
    const [mostrarJsonCrudo, setMostrarJsonCrudo] = useState(false);
    const [respuestaApiCruda, setRespuestaApiCruda] = useState(null);

    const endpointUrl = estadoFiltro === 'todos'
        ? '/api/pedidos'
        : `/api/pedidos?estado=${encodeURIComponent(estadoFiltro)}`;

    const cargarPedidos = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(endpointUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Admin-Mode': adminMode ? '1' : '0',
                },
            });
            const json = await response.json();
            setRespuestaApiCruda(json);
            if (!response.ok || !json.ok) {
                throw new Error(json.message || 'Error al consultar /api/pedidos');
            }
            setPedidos(json.data || []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, [endpointUrl, adminMode]);

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
                    'X-Admin-Mode': adminMode ? '1' : '0',
                },
                body: JSON.stringify({
                    estado: nuevoEstado,
                    es_admin: adminMode,
                }),
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
            <div className="flex flex-wrap items-center justify-between gap-4 bg-slate-900 text-white p-4 rounded-lg">
                <div>
                    <span className="text-xs uppercase tracking-wider text-slate-400 font-semibold">
                        Consola de Sincronización en Tiempo Real (React 18 · API JSON)
                    </span>
                    <div className="font-mono text-xs mt-1 text-slate-300">
                        Endpoint activo: <a href={endpointUrl} target="_blank" rel="noreferrer" className="underline text-sky-400">{endpointUrl}</a>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <select
                        value={adminMode ? 'admin' : 'empleado'}
                        onChange={(e) => setAdminMode(e.target.value === 'admin')}
                        className="text-xs bg-slate-800 text-white border border-slate-700 px-3 py-1.5 rounded-md"
                    >
                        <option value="empleado">Perfil API: Empleado</option>
                        <option value="admin">Perfil API: Administrador</option>
                    </select>
                    <button
                        type="button"
                        onClick={() => setMostrarJsonCrudo(!mostrarJsonCrudo)}
                        className="text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 px-3 py-1.5 rounded-md"
                    >
                        {mostrarJsonCrudo ? 'Ocultar Payload JSON' : 'Inspeccionar Payload JSON'}
                    </button>
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                {['pendiente', 'despachado', 'entregado', 'todos'].map((est) => (
                    <button
                        key={est}
                        type="button"
                        onClick={() => setEstadoFiltro(est)}
                        className={`px-3.5 py-1.5 rounded-md text-xs font-semibold uppercase tracking-wide ${
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
                <div className="p-3 rounded-md bg-red-50 border border-red-200 text-red-700 text-xs">
                    {error}
                </div>
            )}
            {mensaje && (
                <div className="p-3 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                    {mensaje}
                </div>
            )}

            {mostrarJsonCrudo && respuestaApiCruda && (
                <pre className="p-4 bg-slate-950 text-slate-200 rounded-lg text-xs overflow-x-auto max-h-64">
                    {JSON.stringify(respuestaApiCruda, null, 2)}
                </pre>
            )}

            {loading ? (
                <p className="text-xs text-slate-500 py-6 text-center">Consultando {endpointUrl}...</p>
            ) : (
                <div className="overflow-x-auto bg-white rounded-lg border border-slate-200">
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
                                const bloqueado = p.estado === 'entregado' && !adminMode;
                                return (
                                    <tr key={p.id}>
                                        <td className="px-4 py-2.5 font-semibold">#{p.id}</td>
                                        <td className="px-4 py-2.5">{p.cliente?.nombre}</td>
                                        <td className="px-4 py-2.5">{p.fecha}</td>
                                        <td className="px-4 py-2.5">{p.ciudad_destino} ({p.peso_kg} kg)</td>
                                        <td className="px-4 py-2.5 text-right font-medium">${Number(p.total).toLocaleString('es-CO')}</td>
                                        <td className="px-4 py-2.5 text-right">
                                            {p.costo_envio !== null ? `$${Number(p.costo_envio).toLocaleString('es-CO')}` : '—'}
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
