<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Innpro no es distribuidor: los productos no son un catálogo de venta sino el
 * control de lo que recibe por proyecto, así que el stock NO debe impedir
 * cotizar. Con `permitir_venta_sin_stock` en false y 36 de 38 productos sin
 * siquiera fila de stock, el cotizador deshabilitaba casi todo el catálogo.
 *
 * La columna existe desde 2025_08_06_100715 pero ningún formulario ni
 * controlador la escribe nunca, así que quien manda es el default de la tabla:
 * cambiándolo, los productos nuevos (a mano o por importación) ya nacen bien.
 *
 * Se usa SQL crudo porque doctrine/dbal no está instalado y `->change()` lo
 * necesita.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE productos ALTER COLUMN permitir_venta_sin_stock SET DEFAULT 1');

        DB::table('productos')->update(['permitir_venta_sin_stock' => true]);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE productos ALTER COLUMN permitir_venta_sin_stock SET DEFAULT 0');

        // A propósito no se revierten los datos: volver a poner todo en false
        // borraría cualquier ajuste hecho a mano después de esta migración.
    }
};
