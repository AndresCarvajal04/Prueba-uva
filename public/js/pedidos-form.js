/**
 * ============================================================================
 * Módulo de Formulario de Órdenes (pedidos-form.js)
 * ============================================================================
 * Gestiona la sincronización automática de la ciudad principal del cliente
 * al seleccionarlo en el formulario de apertura de orden de pedido.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const selectCliente = document.getElementById('select-cliente-crear');
    const inputCiudad = document.getElementById('input-ciudad-crear');

    if (!selectCliente || !inputCiudad) {
        return;
    }

    selectCliente.addEventListener('change', function () {
        const selectedOption = selectCliente.options[selectCliente.selectedIndex];
        const ciudadCliente = selectedOption ? selectedOption.getAttribute('data-ciudad') : '';

        if (ciudadCliente) {
            inputCiudad.value = ciudadCliente;
        }
    });
});
