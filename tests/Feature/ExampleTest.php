<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que el panel corporativo principal carga con el flujo paso a paso y el reporte mensual.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Sistema de Gestión Logística y Despachos Nacionales')
            ->assertSee('Flujo de Operación del Sistema Logístico')
            ->assertSee('Ventas por Cliente — Mes Actual');
    }
}
