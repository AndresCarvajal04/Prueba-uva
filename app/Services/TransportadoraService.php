<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TransportadoraService
{
    /**
     * Consulta la API de tarifas de la transportadora:
     * POST https://tarifas.transportadora.test/v1/cotizar
     *
     * @return array{costo_envio: float, referencia_cotizacion: string, origen_tarifa: string, mensaje: string}
     */
    public function cotizarEnvio(Pedido $pedido): array
    {
        $endpoint = (string) config('services.transportadora.url', 'https://tarifas.transportadora.test/v1/cotizar');
        $apiKey = (string) config('services.transportadora.key', '');
        $ciudadOrigen = (string) config('services.transportadora.origen', 'Bogotá D.C.');
        $fallbackEnabled = (bool) config('services.transportadora.fallback_enabled', true);

        $pedido->loadMissing('cliente');
        $ciudadDestino = $pedido->ciudad_destino ?: ($pedido->cliente?->ciudad ?? 'Bogotá D.C.');
        $pesoKg = max(0.5, (float) ($pedido->peso_kg ?: 1.0));
        $valorDeclarado = max(0.0, (float) $pedido->total);

        $payload = [
            'pedido_id' => $pedido->id,
            'ciudad_origen' => $ciudadOrigen,
            'ciudad_destino' => $ciudadDestino,
            'peso_kg' => $pesoKg,
            'valor_declarado' => $valorDeclarado,
        ];

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-API-Key' => $apiKey,
                ])
                ->timeout(5)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();

                $costo = $data['costo_envio']
                    ?? $data['tarifa']
                    ?? $data['costo']
                    ?? data_get($data, 'data.costo_envio');

                if (is_numeric($costo)) {
                    $referencia = (string) (
                        $data['referencia']
                        ?? $data['cotizacion_id']
                        ?? data_get($data, 'data.referencia')
                        ?? ('API-' . $pedido->id . '-' . now()->format('His'))
                    );

                    return [
                        'costo_envio' => round((float) $costo, 2),
                        'referencia_cotizacion' => $referencia,
                        'origen_tarifa' => 'api_remota',
                        'mensaje' => "Costo de envío cotizado exitosamente en {$endpoint}.",
                    ];
                }
            }

            Log::warning('TransportadoraService: respuesta no exitosa o sin tarifa válida.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'pedido_id' => $pedido->id,
            ]);

            if (! $fallbackEnabled) {
                throw new RuntimeException("Error al cotizar envío en {$endpoint} (HTTP {$response->status()}).");
            }
        } catch (ConnectionException $e) {
            Log::info('TransportadoraService: host de prueba no alcanzable por red, aplicando cálculo de contingencia.', [
                'endpoint' => $endpoint,
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            if (! $fallbackEnabled) {
                throw new RuntimeException("No fue posible conectar con {$endpoint}: {$e->getMessage()}", 0, $e);
            }
        } catch (Throwable $e) {
            if ($e instanceof RuntimeException && ! $fallbackEnabled) {
                throw $e;
            }

            Log::warning('TransportadoraService: excepción al consultar API, aplicando contingencia.', [
                'endpoint' => $endpoint,
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            if (! $fallbackEnabled) {
                throw new RuntimeException("Error al consultar la API de tarifas: {$e->getMessage()}", 0, $e);
            }
        }

        // Cálculo de respaldo cuando el dominio .test no resuelve localmente
        $costoContingencia = $this->calcularTarifaContingencia($ciudadDestino, $pesoKg, $valorDeclarado);
        $referenciaContingencia = 'COT-LOCAL-' . str_pad((string) $pedido->id, 4, '0', STR_PAD_LEFT);

        return [
            'costo_envio' => $costoContingencia,
            'referencia_cotizacion' => $referenciaContingencia,
            'origen_tarifa' => 'contingencia_local',
            'mensaje' => "Intento de conexión a {$endpoint} registrado; tarifa calculada mediante tabulador logístico de respaldo.",
        ];
    }

    /**
     * Tabulador logístico nacional por ciudad destino, peso (kg) y seguro (1% del valor declarado).
     */
    public function calcularTarifaContingencia(string $ciudadDestino, float $pesoKg, float $valorDeclarado): float
    {
        $ciudadNormalizada = mb_strtolower(trim($ciudadDestino));

        $tarifaBasePorCiudad = [
            'bogotá' => 14000.0,
            'bogota' => 14000.0,
            'bogotá d.c.' => 14000.0,
            'medellín' => 22000.0,
            'medellin' => 22000.0,
            'cali' => 24000.0,
            'bucaramanga' => 23000.0,
            'pereira' => 20000.0,
            'manizales' => 21000.0,
            'barranquilla' => 32000.0,
            'cartagena' => 34000.0,
            'santa marta' => 33000.0,
        ];

        $base = $tarifaBasePorCiudad[$ciudadNormalizada] ?? 26000.0;
        $costoPorKilo = 1850.0 * $pesoKg;
        $seguroMercancia = 0.008 * $valorDeclarado; // 0.8% seguro de carga

        return round($base + $costoPorKilo + $seguroMercancia, 2);
    }
}
