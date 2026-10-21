<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    /**
     * Transforma la entidad Cliente en una estructura JSON controlada y sin exponer metadatos internos.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'nombre' => (string) $this->nombre,
            'documento' => $this->documento,
            'email' => (string) $this->email,
            'telefono' => $this->telefono,
            'ciudad' => (string) $this->ciudad,
            'direccion' => $this->direccion,
        ];
    }
}
