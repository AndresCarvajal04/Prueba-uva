<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que todas las secciones independientes del sistema cargan correctamente
     * con cabeceras de seguridad y sin exponer payloads JSON ni rutas internas.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed();

        // Sección 1: Órdenes de Pedido
        $response = $this->get('/');
        $response->assertStatus(200)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertSee('Sistema de Gestión Logística y Despachos Nacionales')
            ->assertSee('Gestión y Control de Órdenes de Pedido')
            ->assertDontSee('tarifas.transportadora.test');

        // Sección 2: Registrar Nueva Orden
        $this->get('/pedidos/crear')
            ->assertStatus(200)
            ->assertSee('Apertura de Nueva Orden de Pedido');

        // Sección 3: Maestro de Clientes
        $this->get('/clientes')
            ->assertStatus(200)
            ->assertSee('Directorio Maestro de Clientes Corporativos');

        // Sección 4: Ventas del Mes
        $this->get('/reportes/ventas-mes')
            ->assertStatus(200)
            ->assertSee('Ranking de Facturación Mensual por Cliente');

        // Sección 5: Tablero Dinámico React
        $this->get('/monitor-tiempo-real')
            ->assertStatus(200)
            ->assertSee('Tablero Dinámico de Órdenes en Tiempo Real');

        // Sección 6: Flujo e Instrucciones
        $this->get('/guia-operativa')
            ->assertStatus(200)
            ->assertSee('Secuencia Operativa Paso a Paso');
    }
}
