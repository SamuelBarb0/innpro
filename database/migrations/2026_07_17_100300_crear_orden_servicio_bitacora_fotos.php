<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B4 — Evidencia fotográfica adjunta a cada entrada de la bitácora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_servicio_bitacora_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bitacora_id')->constrained('orden_servicio_bitacora')->cascadeOnDelete();
            $table->string('ruta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_servicio_bitacora_fotos');
    }
};
