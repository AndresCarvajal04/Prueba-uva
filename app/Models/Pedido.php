<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_DESPACHADO = 'despachado';
    public const ESTADO_ENTREGADO = 'entregado';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_DESPACHADO,
        self::ESTADO_ENTREGADO,
    ];

    protected $fillable = [
        'cliente_id',
        'fecha',
        'estado',
        'total',
        'peso_kg',
        'ciudad_destino',
        'costo_envio',
        'referencia_cotizacion',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'total' => 'decimal:2',
            'peso_kg' => 'decimal:2',
            'costo_envio' => 'decimal:2',
        ];
    }

    /**
     * Cliente al que pertenece el pedido.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Indica si el pedido ya fue entregado.
     */
    public function estaEntregado(): bool
    {
        return $this->estado === self::ESTADO_ENTREGADO;
    }

    /**
     * Regla de negocio #6:
     * Los pedidos entregados no se pueden modificar, salvo por el administrador.
     */
    public function puedeSerModificadoPor(bool $esAdmin): bool
    {
        if ($esAdmin) {
            return true;
        }

        return ! $this->estaEntregado();
    }
}
