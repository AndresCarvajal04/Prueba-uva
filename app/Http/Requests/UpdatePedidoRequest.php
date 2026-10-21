<?php

namespace App\Http\Requests;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Solicitud validada y sanitizada para la edición y auditoría de una orden de pedido.
 */
class UpdatePedidoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitiza las entradas de texto antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $sanitized = [];

        if ($this->has('ciudad_destino') && is_string($this->input('ciudad_destino'))) {
            $sanitized['ciudad_destino'] = trim(strip_tags($this->input('ciudad_destino')));
        }

        if ($this->has('notas') && is_string($this->input('notas'))) {
            $sanitized['notas'] = trim(strip_tags($this->input('notas')));
        }

        if (! empty($sanitized)) {
            $this->merge($sanitized);
        }
    }

    /**
     * Reglas de validación aplicables a la actualización de un pedido.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'fecha' => ['required', 'date'],
            'estado' => ['required', Rule::in(Pedido::ESTADOS)],
            'total' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'peso_kg' => ['required', 'numeric', 'min:0.1', 'max:50000'],
            'ciudad_destino' => ['required', 'string', 'max:100'],
            'costo_envio' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
