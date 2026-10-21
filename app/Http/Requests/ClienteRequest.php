<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Solicitud validada y sanitizada para el registro y actualización de clientes corporativos.
 */
class ClienteRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitiza los campos alfanuméricos del cliente antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $campos = ['nombre', 'documento', 'email', 'telefono', 'ciudad', 'direccion'];
        $sanitized = [];

        foreach ($campos as $campo) {
            if ($this->has($campo) && is_string($this->input($campo))) {
                $sanitized[$campo] = trim(strip_tags($this->input($campo)));
            }
        }

        if (! empty($sanitized)) {
            $this->merge($sanitized);
        }
    }

    /**
     * Reglas de validación para creación o edición de un cliente.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $clienteActual = $this->route('cliente');
        $clienteId = is_object($clienteActual) ? $clienteActual->id : $clienteActual;

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'documento' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clientes', 'documento')->ignore($clienteId),
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('clientes', 'email')->ignore($clienteId),
            ],
            'telefono' => ['nullable', 'string', 'max:50'],
            'ciudad' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
