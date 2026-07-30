<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A qué sede del cliente corresponde la orden de servicio.
 *
 * Nulable a propósito: las órdenes que ya existen no tienen sede, y un cliente
 * de una sola ciudad no necesita registrarlas.
 *
 * `nullOnDelete` para que borrar una sede no arrastre el historial de órdenes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->foreignId('sucursal_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('cliente_sucursales')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropForeign(['sucursal_id']);
            $table->dropColumn('sucursal_id');
        });
    }
};
