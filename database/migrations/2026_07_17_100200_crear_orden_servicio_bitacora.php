<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B4 — Bitácora del ticket (diario de trabajo).
 * Registro cronológico de múltiples entradas por orden. created_at = fecha/hora de la entrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_servicio_bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // técnico responsable

            $table->text('descripcion');
            $table->decimal('horas_trabajadas', 8, 2)->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_servicio_bitacora');
    }
};
