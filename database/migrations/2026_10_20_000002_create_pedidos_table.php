<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->date('fecha')->index();
            $table->enum('estado', ['pendiente', 'despachado', 'entregado'])->default('pendiente')->index();
            $table->decimal('total', 12, 2);
            $table->decimal('peso_kg', 8, 2)->default(1.00);
            $table->string('ciudad_destino', 100);
            $table->decimal('costo_envio', 12, 2)->nullable();
            $table->string('referencia_cotizacion', 100)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
