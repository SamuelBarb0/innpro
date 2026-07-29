<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renombra los estados finales de las órdenes de servicio a los que usa Innpro
 * para sus indicadores: "Entregada" pasa a ser "Garantía" (servicio atendido sin
 * cobro) y "Cancelada" pasa a ser "Facturado" (servicio que va a facturación).
 */
return new class extends Migration
{
    private const CAMBIOS = [
        'entregada' => 'garantia',
        'cancelada' => 'facturado',
    ];

    public function up(): void
    {
        foreach (self::CAMBIOS as $antiguo => $nuevo) {
            DB::table('ordenes_servicio')->where('estado', $antiguo)->update(['estado' => $nuevo]);
        }
    }

    public function down(): void
    {
        foreach (self::CAMBIOS as $antiguo => $nuevo) {
            DB::table('ordenes_servicio')->where('estado', $nuevo)->update(['estado' => $antiguo]);
        }
    }
};
