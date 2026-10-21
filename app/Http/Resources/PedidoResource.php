<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoResource extends JsonResource
{
    /**
     * Transforma la entidad Pedido en una estructura JSON estandarizada y segura.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'cliente_id' => (int) $this->cliente_id,
            'fecha' => $this->fecha ? $this->fecha->format('Y-m-d') : null,
            'estado' => (string) $this->estado,
            'total' => number_format((float) $this->total, 2, '.', ''),
            'peso_kg' => number_format((float) $this->peso_kg, 2, '.', ''),
            'ciudad_destino' => (string) $this->ciudad_destino,
            'costo_envio' => $this->costo_envio !== null
                ? number_format((float) $this->costo_envio, 2, '.', '')
                : null,
            'referencia_cotizacion' => $this->referencia_cotizacion,
            'notas' => $this->notas,
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
        ];
    }
}
