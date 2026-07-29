<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colector de firmas del formato técnico.
 *
 * Guarda la firma manuscrita (capturada en canvas) del técnico responsable y
 * del cliente que recibe a conformidad, junto con el nombre, documento y el
 * momento exacto en que se firmó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->string('firma_tecnico_ruta')->nullable()->after('observaciones');
            $table->string('firma_tecnico_nombre')->nullable()->after('firma_tecnico_ruta');
            $table->string('firma_tecnico_cc', 60)->nullable()->after('firma_tecnico_nombre');
            $table->timestamp('firma_tecnico_at')->nullable()->after('firma_tecnico_cc');

            $table->string('firma_cliente_ruta')->nullable()->after('firma_tecnico_at');
            $table->string('firma_cliente_nombre')->nullable()->after('firma_cliente_ruta');
            $table->string('firma_cliente_cc', 60)->nullable()->after('firma_cliente_nombre');
            $table->timestamp('firma_cliente_at')->nullable()->after('firma_cliente_cc');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn([
                'firma_tecnico_ruta', 'firma_tecnico_nombre', 'firma_tecnico_cc', 'firma_tecnico_at',
                'firma_cliente_ruta', 'firma_cliente_nombre', 'firma_cliente_cc', 'firma_cliente_at',
            ]);
        });
    }
};
