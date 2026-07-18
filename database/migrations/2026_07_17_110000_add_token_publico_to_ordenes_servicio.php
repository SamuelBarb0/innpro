<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\OrdenServicio;

/**
 * Token público para el seguimiento de la orden por parte del cliente (solo lectura).
 * La visibilidad de la bitácora sigue controlada por `bitacora_visible_cliente`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->string('token_publico', 64)->nullable()->unique()->after('numero');
        });

        // Backfill de órdenes existentes
        OrdenServicio::withTrashed()->whereNull('token_publico')->get()->each(function ($o) {
            $o->token_publico = Str::random(48);
            $o->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn('token_publico');
        });
    }
};
