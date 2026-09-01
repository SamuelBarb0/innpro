<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de cotización: Proyecto, Mantenimiento Preventivo, Mantenimiento
 * Correctivo o Asistencia.
 *
 * Va nullable a propósito: solo se pregunta en el alta rápida de prospectos,
 * así que las cotizaciones que ya existen —y las que entran por enlace o para
 * un cliente ya dado de alta— se quedan sin tipo. En la interfaz eso se muestra
 * como "Sin tipo", no como un valor inventado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_cotizacion', function (Blueprint $table) {
            $table->string('tipo_cotizacion', 40)->nullable()->after('estado');
            $table->index('tipo_cotizacion');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_cotizacion', function (Blueprint $table) {
            $table->dropIndex(['tipo_cotizacion']);
            $table->dropColumn('tipo_cotizacion');
        });
    }
};
