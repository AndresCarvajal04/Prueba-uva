/**
 * ============================================================================
 * Tablero Dinámico de Órdenes en Tiempo Real — React 18 (tablero-react.js)
 * ============================================================================
 * Componente desacoplado de interfaz que consume el servicio REST /api/pedidos
 * para filtrar órdenes por estado y gestionar transiciones asíncronas.
 */
(function () {
    'use strict';

    const rootEl = document.getElementById('react-pedidos-root');
    if (!rootEl || typeof React === 'undefined' || typeof ReactDOM === 'undefined') {
        return;
    }

    const e = React.createElement;
    const { useState, useEffect, useCallback } = React;

    /**
     * Componente principal del Tablero Dinámico de Pedidos.
     *
     * @param {Object} props
     * @param {boolean} props.esAdminSesion Indica si la sesión actual tiene privilegios de Administrador.
     */
    function TableroDinamicoPedidos({ esAdminSesion }) {
        const [estadoFiltro, setEstadoFiltro] = useState('pendiente');
        const [pedidos, setPedidos] = useState([]);
        const [loading, setLoading] = useState(false);
        const [error, setError] = useState(null);
        const [mensaje, setMensaje] = useState(null);

        /**
         * Consulta las órdenes de pedido desde el endpoint REST filtrando por estado.
         */
        const cargarOrdenes = useCallback(async () => {
            setLoading(true);
            setError(null);

            const url = estadoFiltro === 'todos'
                ? '/api/pedidos'
                : '/api/pedidos?estado=' + encodeURIComponent(estadoFiltro);

            try {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Admin-Mode': esAdminSesion ? '1' : '0'
                    }
                });
                const payload = await res.json();

                if (!res.ok || !payload.ok) {
                    throw new Error(payload.message || 'No fue posible recuperar el listado de órdenes.');
                }

                setPedidos(payload.data || []);
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        }, [estadoFiltro, esAdminSesion]);

        useEffect(() => {
            cargarOrdenes();
        }, [cargarOrdenes]);

        /**
         * Envía la solicitud PATCH para actualizar el estado de una orden.
         *
         * @param {number} pedidoId Identificador único de la orden.
         * @param {string} nuevoEstado Nuevo estado operativo ('pendiente' | 'despachado' | 'entregado').
         */
        const actualizarEstado = async (pedidoId, nuevoEstado) => {
            setError(null);
            setMensaje(null);

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('/api/pedidos/' + pedidoId + '/estado', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Admin-Mode': esAdminSesion ? '1' : '0'
                    },
                    body: JSON.stringify({ estado: nuevoEstado })
                });
                const payload = await res.json();

                if (!res.ok || !payload.ok) {
                    throw new Error(payload.message || 'No se pudo actualizar el estado de la orden.');
                }

                setMensaje(payload.message);
                await cargarOrdenes();
            } catch (err) {
                setError(err.message);
            }
        };

        const filtros = [
            { id: 'pendiente', label: 'Pendientes' },
            { id: 'despachado', label: 'Despachadas' },
            { id: 'entregado', label: 'Entregadas' },
            { id: 'todos', label: 'Todas las Órdenes' }
        ];

        return e('div', { className: 'space-y-4' },
            e('div', { className: 'flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200' },
                e('div', { className: 'flex flex-wrap items-center gap-1.5 bg-slate-100 p-1 rounded-md border border-slate-200' },
                    filtros.map((f) =>
                        e('button', {
                            key: f.id,
                            type: 'button',
                            onClick: () => setEstadoFiltro(f.id),
                            className: 'px-3.5 py-1.5 rounded text-xs font-semibold transition ' +
                                (estadoFiltro === f.id
                                    ? 'bg-slate-900 text-white shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-white')
                        }, f.label)
                    )
                ),
                e('button', {
                    type: 'button',
                    onClick: cargarOrdenes,
                    className: 'px-3.5 py-1.5 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700'
                }, 'Actualizar Tablero')
            ),

            error && e('div', { className: 'p-3.5 rounded-md bg-red-50 border border-red-200 text-red-800 text-xs font-medium' }, error),
            mensaje && e('div', { className: 'p-3.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium' }, mensaje),

            loading
                ? e('div', { className: 'py-8 text-center text-xs text-slate-500' }, 'Cargando información operativa...')
                : e('div', { className: 'overflow-x-auto border border-slate-200 rounded-md' },
                    e('table', { className: 'min-w-full divide-y divide-slate-200 text-xs' },
                        e('thead', { className: 'bg-slate-50 text-slate-600 uppercase font-semibold' },
                            e('tr', null,
                                e('th', { className: 'px-4 py-3 text-left' }, 'Orden'),
                                e('th', { className: 'px-4 py-3 text-left' }, 'Cliente Corporativo'),
                                e('th', { className: 'px-4 py-3 text-left' }, 'Fecha'),
                                e('th', { className: 'px-4 py-3 text-left' }, 'Ciudad Destino / Peso'),
                                e('th', { className: 'px-4 py-3 text-right' }, 'Valor Venta'),
                                e('th', { className: 'px-4 py-3 text-right' }, 'Costo Envío'),
                                e('th', { className: 'px-4 py-3 text-center' }, 'Estado'),
                                e('th', { className: 'px-4 py-3 text-right' }, 'Cambiar Estado')
                            )
                        ),
                        e('tbody', { className: 'divide-y divide-slate-200' },
                            pedidos.length === 0
                                ? e('tr', null,
                                    e('td', { colSpan: 8, className: 'px-4 py-8 text-center text-slate-500' },
                                        'No se encontraron órdenes para el filtro seleccionado.'
                                    )
                                )
                                : pedidos.map((p) => {
                                    const bloqueado = p.estado === 'entregado' && !esAdminSesion;
                                    return e('tr', { key: p.id, className: 'hover:bg-slate-50' },
                                        e('td', { className: 'px-4 py-3 font-bold tabular-nums text-slate-900' }, '#' + p.id),
                                        e('td', { className: 'px-4 py-3 font-semibold text-slate-900' }, p.cliente ? p.cliente.nombre : '—'),
                                        e('td', { className: 'px-4 py-3 text-slate-600 tabular-nums' }, p.fecha || '—'),
                                        e('td', { className: 'px-4 py-3 text-slate-600' }, p.ciudad_destino + ' · ' + p.peso_kg + ' kg'),
                                        e('td', { className: 'px-4 py-3 text-right font-bold tabular-nums text-slate-900' },
                                            '$' + Number(p.total).toLocaleString('es-CO', { minimumFractionDigits: 2 })
                                        ),
                                        e('td', { className: 'px-4 py-3 text-right font-medium tabular-nums text-slate-800' },
                                            p.costo_envio !== null
                                                ? '$' + Number(p.costo_envio).toLocaleString('es-CO', { minimumFractionDigits: 2 })
                                                : 'Pendiente'
                                        ),
                                        e('td', { className: 'px-4 py-3 text-center font-bold uppercase' }, p.estado),
                                        e('td', { className: 'px-4 py-3 text-right' },
                                            bloqueado
                                                ? e('span', { className: 'text-[11px] text-slate-400 bg-slate-100 px-2.5 py-1 rounded border border-slate-200' }, 'Cerrada')
                                                : e('select', {
                                                    value: p.estado,
                                                    onChange: (ev) => actualizarEstado(p.id, ev.target.value),
                                                    className: 'border border-slate-300 rounded px-2.5 py-1 text-xs bg-white font-medium'
                                                },
                                                    e('option', { value: 'pendiente' }, 'Pendiente'),
                                                    e('option', { value: 'despachado' }, 'Despachado'),
                                                    e('option', { value: 'entregado' }, 'Entregado')
                                                )
                                        )
                                    );
                                })
                        )
                    )
                )
        );
    }

    const esAdminSesion = rootEl.getAttribute('data-admin') === '1';
    const root = ReactDOM.createRoot(rootEl);
    root.render(e(TableroDinamicoPedidos, { esAdminSesion }));
})();
