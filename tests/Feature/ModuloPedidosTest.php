<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModuloPedidosTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $clienteA;
    private Cliente $clienteB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clienteA = Cliente::create([
            'nombre' => 'Cliente Alfa S.A.S.',
            'documento' => '900111222-1',
            'email' => 'alfa@test.co',
            'telefono' => '3001112233',
            'ciudad' => 'Medellín',
            'direccion' => 'Calle 10 # 20-30',
        ]);

        $this->clienteB = Cliente::create([
            'nombre' => 'Cliente Beta Ltda.',
            'documento' => '800333444-2',
            'email' => 'beta@test.co',
            'telefono' => '3004445566',
            'ciudad' => 'Barranquilla',
            'direccion' => 'Carrera 50 # 80-10',
        ]);
    }

    /**
     * Requisito 3:
     * Un endpoint GET /api/pedidos?estado=pendiente que responda en JSON.
     */
    public function test_endpoint_api_pedidos_filtra_por_estado_pendiente_en_json(): void
    {
        Pedido::create([
            'cliente_id' => $this->clienteA->id,
            'fecha' => Carbon::now()->toDateString(),
            'estado' => Pedido::ESTADO_PENDIENTE,
            'total' => 500000,
            'peso_kg' => 5,
            'ciudad_destino' => 'Medellín',
        ]);

        Pedido::create([
            'cliente_id' => $this->clienteB->id,
            'fecha' => Carbon::now()->toDateString(),
            'estado' => Pedido::ESTADO_ENTREGADO,
            'total' => 900000,
            'peso_kg' => 10,
            'ciudad_destino' => 'Barranquilla',
        ]);

        $response = $this->getJson('/api/pedidos?estado=pendiente');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'filtro_estado' => 'pendiente',
                'total_registros' => 1,
            ])
            ->assertJsonPath('data.0.estado', 'pendiente')
            ->assertJsonPath('data.0.cliente.nombre', 'Cliente Alfa S.A.S.');
    }

    /**
     * Requisito 4:
     * Un reporte con el total vendido por cliente en el mes actual, de mayor a menor.
     */
    public function test_reporte_total_vendido_por_cliente_en_mes_actual_ordenado_de_mayor_a_menor(): void
    {
        $hoy = Carbon::now();
        $mesPasado = Carbon::now()->subMonth();

        // Cliente A en mes actual: $1,200,000
        Pedido::create([
            'cliente_id' => $this->clienteA->id,
            'fecha' => $hoy->toDateString(),
            'estado' => Pedido::ESTADO_PENDIENTE,
            'total' => 1200000,
            'peso_kg' => 4,
            'ciudad_destino' => 'Medellín',
        ]);

        // Cliente A en mes pasado: $9,000,000 (NO debe sumarse al mes actual)
        Pedido::create([
            'cliente_id' => $this->clienteA->id,
            'fecha' => $mesPasado->toDateString(),
            'estado' => Pedido::ESTADO_ENTREGADO,
            'total' => 9000000,
            'peso_kg' => 25,
            'ciudad_destino' => 'Medellín',
        ]);

        // Cliente B en mes actual: $2,500,000 + $800,000 = $3,300,000 (#1)
        Pedido::create([
            'cliente_id' => $this->clienteB->id,
            'fecha' => $hoy->toDateString(),
            'estado' => Pedido::ESTADO_DESPACHADO,
            'total' => 2500000,
            'peso_kg' => 12,
            'ciudad_destino' => 'Barranquilla',
        ]);
        Pedido::create([
            'cliente_id' => $this->clienteB->id,
            'fecha' => $hoy->toDateString(),
            'estado' => Pedido::ESTADO_ENTREGADO,
            'total' => 800000,
            'peso_kg' => 6,
            'ciudad_destino' => 'Barranquilla',
        ]);

        $response = $this->getJson('/api/reportes/ventas-mes');

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('gran_total_vendido', 4500000)
            ->assertJsonPath('data.0.cliente_nombre', 'Cliente Beta Ltda.')
            ->assertJsonPath('data.0.total_vendido', 3300000)
            ->assertJsonPath('data.1.cliente_nombre', 'Cliente Alfa S.A.S.')
            ->assertJsonPath('data.1.total_vendido', 1200000);
    }

    /**
     * Requisito 5:
     * Al marcar un pedido como despachado, el sistema debe calcular el costo de envío
     * consultando POST https://tarifas.transportadora.test/v1/cotizar.
     */
    public function test_al_marcar_pedido_como_despachado_consulta_api_transportadora_y_guarda_costo_envio(): void
    {
        Http::fake([
            'https://tarifas.transportadora.test/v1/cotizar' => Http::response([
                'costo_envio' => 58900.50,
                'referencia' => 'COT-TRANS-7788',
            ], 200),
        ]);

        $pedido = Pedido::create([
            'cliente_id' => $this->clienteA->id,
            'fecha' => Carbon::now()->toDateString(),
            'estado' => Pedido::ESTADO_PENDIENTE,
            'total' => 1500000,
            'peso_kg' => 10.5,
            'ciudad_destino' => 'Medellín',
        ]);

        $response = $this->patchJson("/api/pedidos/{$pedido->id}/estado", [
            'estado' => Pedido::ESTADO_DESPACHADO,
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.estado', 'despachado')
            ->assertJsonPath('data.costo_envio', '58900.50')
            ->assertJsonPath('data.referencia_cotizacion', 'COT-TRANS-7788');

        Http::assertSent(function (HttpClientRequest $request) use ($pedido) {
            return $request->url() === 'https://tarifas.transportadora.test/v1/cotizar'
                && $request->method() === 'POST'
                && $request['pedido_id'] === $pedido->id
                && $request['ciudad_destino'] === 'Medellín';
        });
    }

    /**
     * Requisito 6:
     * Los pedidos entregados no se pueden modificar, pero el administrador debe poder corregir cualquier pedido.
     */
    public function test_pedido_entregado_no_puede_ser_modificado_por_operador_pero_si_por_administrador(): void
    {
        $pedidoEntregado = Pedido::create([
            'cliente_id' => $this->clienteA->id,
            'fecha' => Carbon::now()->toDateString(),
            'estado' => Pedido::ESTADO_ENTREGADO,
            'total' => 750000,
            'peso_kg' => 3.0,
            'ciudad_destino' => 'Medellín',
            'costo_envio' => 25000,
        ]);

        // 1. Intento de cambio de estado como Operador (debe ser rechazado con 403)
        $respuestaOperador = $this->patchJson("/api/pedidos/{$pedidoEntregado->id}/estado", [
            'estado' => Pedido::ESTADO_PENDIENTE,
        ]);

        $respuestaOperador->assertStatus(403)
            ->assertJsonPath('ok', false);

        $this->assertEquals(Pedido::ESTADO_ENTREGADO, $pedidoEntregado->fresh()->estado);

        // 2. Corrección por parte del Administrador (autenticado con is_admin = true o cabecera X-Admin-Mode)
        $admin = User::factory()->create(['is_admin' => true]);

        $respuestaAdmin = $this->actingAs($admin)->putJson("/api/pedidos/{$pedidoEntregado->id}", [
            'cliente_id' => $this->clienteB->id,
            'fecha' => Carbon::now()->toDateString(),
            'estado' => Pedido::ESTADO_PENDIENTE,
            'total' => 980000,
            'peso_kg' => 4.5,
            'ciudad_destino' => 'Barranquilla',
            'costo_envio' => 0,
            'notas' => 'Corregido por el administrador',
        ]);

        $respuestaAdmin->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.total', '980000.00');
    }

    /**
     * Verifica el cambio de perfil mediante el menú desplegable (Empleado / Administrador)
     * y las operaciones CRUD de administración (edición de cliente y eliminación de orden).
     */
    public function test_administrador_puede_cambiar_perfil_en_menu_desplegable_y_ejecutar_crud_completo(): void
    {
        $pedido = Pedido::create([
            'cliente_id' => $this->clienteA->id,
            'fecha' => Carbon::now()->toDateString(),
            'estado' => Pedido::ESTADO_ENTREGADO,
            'total' => 620000,
            'peso_kg' => 4.0,
            'ciudad_destino' => 'Medellín',
        ]);

        // Cambiar a perfil Administrador General desde el menú desplegable
        $this->post('/cambiar-rol', ['rol' => 'admin'])
            ->assertRedirect();

        $this->assertEquals('admin', session('rol_activo'));

        // Editar cliente como Administrador
        $this->put("/clientes/{$this->clienteA->id}", [
            'nombre' => 'Cliente Alfa Corporativo S.A.S.',
            'documento' => '900111222-1',
            'email' => 'alfa@test.co',
            'telefono' => '3009998877',
            'ciudad' => 'Bogotá D.C.',
            'direccion' => 'Av. El Dorado # 68-90',
        ])->assertRedirect();

        $this->assertEquals('Cliente Alfa Corporativo S.A.S.', $this->clienteA->fresh()->nombre);

        // Eliminar pedido entregado como Administrador
        $this->delete("/pedidos/{$pedido->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('pedidos', ['id' => $pedido->id]);
    }
}
