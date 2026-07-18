<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo Servicio Técnico (Innpro) — Orden de servicio (B3).
 * Trabajos que pueden extenderse en el tiempo, con técnico asignado y flujo de estados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();                 // OS-000001
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->string('titulo');
            $table->text('descripcion_problema')->nullable();
            $table->text('diagnostico')->nullable();

            $table->string('estado')->default('recibida');       // recibida, en_diagnostico, en_proceso, espera_repuestos, finalizada, entregada, cancelada
            $table->string('prioridad')->default('media');       // baja, media, alta, urgente

            $table->decimal('costo_mano_obra', 12, 2)->default(0);

            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_estimada')->nullable();
            $table->timestamp('fecha_cierre')->nullable();

            $table->boolean('bitacora_visible_cliente')->default(false); // B4: visibilidad controlada por admin
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_servicio');
    }
};
