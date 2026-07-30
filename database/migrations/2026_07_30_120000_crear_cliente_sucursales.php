<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sucursales / sedes por cliente (pedido 9 de la reunión del 29 de julio).
 *
 * Innpro atiende a un mismo cliente en varias ciudades a la vez —Popayán,
 * Cali, Medellín— y cada una es un proyecto distinto. Hasta ahora el cliente
 * tenía una sola `ciudad`, así que no había forma de decir a qué sede fue un
 * servicio ni qué se recibió en cada una.
 *
 * Es también la base del "stock por cliente y sucursal" (pedido 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nombre');            // "Sede Norte", "Proyecto Popayán"
            $table->string('ciudad')->nullable();
            $table->string('direccion')->nullable();
            $table->string('contacto')->nullable();
            $table->string('telefono', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['cliente_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_sucursales');
    }
};
