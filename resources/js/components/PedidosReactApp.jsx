import React, { useState, useEffect, useCallback } from 'react';

/**
 * Requisito 7 (Opcional - Suma puntos):
 * Componente en React que consume el endpoint del Requisito 3:
 * GET /api/pedidos?estado=pendiente (y demás estados) en formato JSON.
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
                throw new Error(json.message || 'No se pudo actualizar el estado del pedido.');
            }
            setMensaje(json.message);
            await cargarPedidos();
        } catch (err) {
            setError(err.message);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-4 bg-slate-900 text-white p-4 rounded-xl">
                <div>
                    <span className="text-xs uppercase tracking-wider text-sky-400 font-semibold">
                        Requisito 7 — Cliente React consumiendo Requisito 3
                    </span>
                    <div className="font-mono text-sm mt-1">
                        GET <a href={endpointUrl} target="_blank" rel="noreferrer" className="underline text-emerald-400">{endpointUrl}</a>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <label className="flex items-center gap-2 text-xs bg-slate-800 px-3 py-1.5 rounded-lg cursor-pointer">
                        <input
                            type="checkbox"
                            checked={adminMode}
                            onChange={(e) => setAdminMode(e.target.checked)}
                        />
                        <span>Modo Administrador (X-Admin-Mode)</span>
                    </label>
                    <button
                        type="button"
                        onClick={() => setMostrarJsonCrudo(!mostrarJsonCrudo)}
                        className="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded-lg"
                    >
                        {mostrarJsonCrudo ? 'Ocultar JSON' : 'Ver JSON Crudo'}
                    </button>
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                {['pendiente', 'despachado', 'entregado', 'todos'].map((est) => (
                    <button
                        key={est}
                        type="button"
                        onClick={() => setEstadoFiltro(est)}
                        className={`px-4 py-2 rounded-lg text-sm font-medium capitalize ${
                            estadoFiltro === est
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-slate-700 border border-slate-200'
                        }`}
                    >
                        {est}
                    </button>
                ))}
            </div>

            {error && (
                <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                    {error}
                </div>
            )}
            {mensaje && (
                <div className="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                    {mensaje}
                </div>
            )}

            {mostrarJsonCrudo && respuestaApiCruda && (
                <pre className="p-4 bg-slate-950 text-emerald-300 rounded-xl text-xs overflow-x-auto max-h-64">
                    {JSON.stringify(respuestaApiCruda, null, 2)}
                </pre>
            )}

            {loading ? (
                <p className="text-sm text-slate-500 py-6 text-center">Cargando desde {endpointUrl}...</p>
            ) : (
                <div className="overflow-x-auto bg-white rounded-xl border border-slate-200">
                    <table className="min-w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="px-4 py-3 text-left">ID</th>
                                <th className="px-4 py-3 text-left">Cliente</th>
                                <th className="px-4 py-3 text-left">Fecha</th>
                                <th className="px-4 py-3 text-left">Destino / Peso</th>
                                <th className="px-4 py-3 text-right">Total Venta</th>
                                <th className="px-4 py-3 text-right">Costo Envío</th>
                                <th className="px-4 py-3 text-center">Estado</th>
                                <th className="px-4 py-3 text-right">Acción API</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {pedidos.map((p) => {
                                const bloqueado = p.estado === 'entregado' && !adminMode;
                                return (
                                    <tr key={p.id}>
                                        <td className="px-4 py-3 font-semibold">#{p.id}</td>
                                        <td className="px-4 py-3">{p.cliente?.nombre}</td>
                                        <td className="px-4 py-3">{p.fecha}</td>
                                        <td className="px-4 py-3">{p.ciudad_destino} ({p.peso_kg} kg)</td>
                                        <td className="px-4 py-3 text-right font-medium">${Number(p.total).toLocaleString('es-CO')}</td>
                                        <td className="px-4 py-3 text-right">
                                            {p.costo_envio !== null ? `$${Number(p.costo_envio).toLocaleString('es-CO')}` : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-center uppercase text-xs font-bold">{p.estado}</td>
                                        <td className="px-4 py-3 text-right">
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
