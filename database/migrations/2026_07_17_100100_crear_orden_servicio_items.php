<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B5 — Equipos de seguridad adicionales por orden + repuestos utilizados (B3).
 * Varios ítems por orden, ligados opcionalmente al inventario, reflejados en el costo/PDF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_servicio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();

            $table->string('tipo')->default('equipo');   // equipo | repuesto
            $table->string('descripcion');
            $table->decimal('cantidad', 12, 2)->default(1);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_servicio_items');
    }
};
