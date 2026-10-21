<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuarios de prueba (Operador y Administrador para Requisito 6)
        User::updateOrCreate(
            ['email' => 'admin@uva3.co'],
            [
                'name' => 'Administrador UVA3',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'operador@uva3.co'],
            [
                'name' => 'Operador Logístico',
                'password' => Hash::make('operador123'),
                'is_admin' => false,
            ]
        );

        // Clientes de prueba (Pregunta 1.4)
        $clientesData = [
            [
                'nombre' => 'Distribuidora Andina S.A.S.',
                'documento' => '900.412.881-1',
                'email' => 'compras@andinadist.co',
                'telefono' => '604 312 4500',
                'ciudad' => 'Medellín',
                'direccion' => 'Cra. 43A # 14-50, El Poblado',
            ],
            [
                'nombre' => 'Comercializadora del Pacífico Ltda.',
                'documento' => '800.198.332-4',
                'email' => 'logistica@pacifico.com.co',
                'telefono' => '602 489 1120',
                'ciudad' => 'Cali',
                'direccion' => 'Av. 6N # 28N-102, Santa Mónica',
            ],
            [
                'nombre' => 'Grupo Empresarial Caribe S.A.',
                'documento' => '901.005.719-8',
                'email' => 'pedidos@grupocaribe.co',
                'telefono' => '605 385 9000',
                'ciudad' => 'Barranquilla',
                'direccion' => 'Vía 40 # 73-290, Zona Industrial',
            ],
            [
                'nombre' => 'Almacenes Santander Express',
                'documento' => '890.204.511-3',
                'email' => 'abastecimiento@santanderexpress.co',
                'telefono' => '607 643 2210',
                'ciudad' => 'Bucaramanga',
                'direccion' => 'Calle 36 # 19-45, Centro',
            ],
            [
                'nombre' => 'Inversiones Cafeteras del Quindío',
                'documento' => '900.774.109-6',
                'email' => 'contacto@invcafeteras.co',
                'telefono' => '606 745 8830',
                'ciudad' => 'Pereira',
                'direccion' => 'Av. Circunvalar # 8-60',
            ],
        ];

        $clientes = [];
        foreach ($clientesData as $data) {
            $clientes[] = Cliente::updateOrCreate(['email' => $data['email']], $data);
        }

        // Evitar duplicar pedidos si ya se ejecutó el seeder
        if (Pedido::count() > 0) {
            return;
        }

        $inicioMesActual = Carbon::now()->startOfMonth();
        $mesAnterior = Carbon::now()->subMonth()->startOfMonth();

        $pedidosData = [
            // --- MES ACTUAL ---
            // Cliente 0: Distribuidora Andina (Total mes actual: $4,250,000)
            [
                'cliente_id' => $clientes[0]->id,
                'fecha' => $inicioMesActual->copy()->addDays(1)->toDateString(),
                'estado' => Pedido::ESTADO_ENTREGADO,
                'total' => 2450000.00,
                'peso_kg' => 18.50,
                'ciudad_destino' => 'Medellín',
                'costo_envio' => 64250.00,
                'referencia_cotizacion' => 'COT-UVA3-1001',
                'notas' => 'Lote de insumos industriales - Entregado en bodega principal',
            ],
            [
                'cliente_id' => $clientes[0]->id,
                'fecha' => $inicioMesActual->copy()->addDays(4)->toDateString(),
                'estado' => Pedido::ESTADO_DESPACHADO,
                'total' => 1800000.00,
                'peso_kg' => 12.00,
                'ciudad_destino' => 'Medellín',
                'costo_envio' => 45000.00,
                'referencia_cotizacion' => 'COT-UVA3-1002',
                'notas' => 'Reposición urgente de catálogo Q4',
            ],

            // Cliente 2: Grupo Empresarial Caribe (Total mes actual: $5,600,000 - #1 en ranking)
            [
                'cliente_id' => $clientes[2]->id,
                'fecha' => $inicioMesActual->copy()->addDays(2)->toDateString(),
                'estado' => Pedido::ESTADO_ENTREGADO,
                'total' => 3500000.00,
                'peso_kg' => 32.00,
                'ciudad_destino' => 'Barranquilla',
                'costo_envio' => 118000.00,
                'referencia_cotizacion' => 'COT-UVA3-1003',
                'notas' => 'Carga pesada costa atlántica',
            ],
            [
                'cliente_id' => $clientes[2]->id,
                'fecha' => $inicioMesActual->copy()->addDays(6)->toDateString(),
                'estado' => Pedido::ESTADO_PENDIENTE,
                'total' => 2100000.00,
                'peso_kg' => 15.00,
                'ciudad_destino' => 'Barranquilla',
                'costo_envio' => null,
                'referencia_cotizacion' => null,
                'notas' => 'Pendiente de despacho y cotización en transportadora',
            ],

            // Cliente 1: Comercializadora del Pacífico (Total mes actual: $3,150,000)
            [
                'cliente_id' => $clientes[1]->id,
                'fecha' => $inicioMesActual->copy()->addDays(3)->toDateString(),
                'estado' => Pedido::ESTADO_DESPACHADO,
                'total' => 1950000.00,
                'peso_kg' => 14.20,
                'ciudad_destino' => 'Cali',
                'costo_envio' => 52800.00,
                'referencia_cotizacion' => 'COT-UVA3-1004',
                'notas' => 'Envío refrigerado ruta occidente',
            ],
            [
                'cliente_id' => $clientes[1]->id,
                'fecha' => $inicioMesActual->copy()->addDays(7)->toDateString(),
                'estado' => Pedido::ESTADO_PENDIENTE,
                'total' => 1200000.00,
                'peso_kg' => 8.50,
                'ciudad_destino' => 'Cali',
                'costo_envio' => null,
                'referencia_cotizacion' => null,
                'notas' => 'Alistamiento en centro de distribución',
            ],

            // Cliente 3: Almacenes Santander Express (Total mes actual: $1,480,000)
            [
                'cliente_id' => $clientes[3]->id,
                'fecha' => $inicioMesActual->copy()->addDays(5)->toDateString(),
                'estado' => Pedido::ESTADO_PENDIENTE,
                'total' => 1480000.00,
                'peso_kg' => 9.00,
                'ciudad_destino' => 'Bucaramanga',
                'costo_envio' => null,
                'referencia_cotizacion' => null,
                'notas' => 'Mercancía frágil - requiere embalaje reforzado',
            ],

            // Cliente 4: Inversiones Cafeteras (Total mes actual: $890,000)
            [
                'cliente_id' => $clientes[4]->id,
                'fecha' => $inicioMesActual->copy()->addDays(8)->toDateString(),
                'estado' => Pedido::ESTADO_ENTREGADO,
                'total' => 890000.00,
                'peso_kg' => 5.40,
                'ciudad_destino' => 'Pereira',
                'costo_envio' => 28500.00,
                'referencia_cotizacion' => 'COT-UVA3-1005',
                'notas' => 'Entregado a satisfacción',
            ],

            // --- MES ANTERIOR (Para comprobar que el reporte del mes actual filtra correctamente) ---
            [
                'cliente_id' => $clientes[3]->id,
                'fecha' => $mesAnterior->copy()->addDays(10)->toDateString(),
                'estado' => Pedido::ESTADO_ENTREGADO,
                'total' => 4900000.00,
                'peso_kg' => 40.00,
                'ciudad_destino' => 'Bucaramanga',
                'costo_envio' => 142000.00,
                'referencia_cotizacion' => 'COT-UVA3-0901',
                'notas' => 'Pedido histórico del mes pasado (no debe sumar en el mes actual)',
            ],
            [
                'cliente_id' => $clientes[4]->id,
                'fecha' => $mesAnterior->copy()->addDays(18)->toDateString(),
                'estado' => Pedido::ESTADO_ENTREGADO,
                'total' => 2750000.00,
                'peso_kg' => 21.00,
                'ciudad_destino' => 'Pereira',
                'costo_envio' => 74000.00,
                'referencia_cotizacion' => 'COT-UVA3-0902',
                'notas' => 'Pedido histórico del mes pasado',
            ],
        ];

        foreach ($pedidosData as $pedido) {
            Pedido::create($pedido);
        }
    }
}
