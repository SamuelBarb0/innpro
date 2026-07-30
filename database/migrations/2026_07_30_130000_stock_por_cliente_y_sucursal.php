<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock por cliente y sucursal (pedido 11 de la reunión del 29 de julio).
 *
 * Innpro no es distribuidor: no vende de su bodega, RECIBE equipos de terceros
 * para instalarlos en el proyecto de un cliente. Lo que importa entonces no es
 * "cuántos hay", sino "cuántos se recibieron para la sede X del cliente Y".
 *
 * Las dos columnas son nulables y las filas existentes se quedan en NULL, que
 * sigue significando lo de siempre: existencia general, sin dueño.
 *
 * OJO con el índice único: MySQL considera distintos dos NULL, así que este
 * único no impide dos filas de existencia general del mismo producto. No es
 * una regresión —la tabla ya tenía el problema con `variante_producto_id`
 * nulable— y en la práctica lo evita el `firstOrNew` del código.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_productos', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('variante_producto_id')
                ->constrained('clientes')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->after('cliente_id')
                ->constrained('cliente_sucursales')->cascadeOnDelete();

            $table->dropUnique(['producto_id', 'variante_producto_id']);
            $table->unique(
                ['producto_id', 'variante_producto_id', 'cliente_id', 'sucursal_id'],
                'stock_producto_cliente_sucursal_unico'
            );

            $table->index(['cliente_id', 'sucursal_id']);
        });

        // El movimiento también se atribuye: si no, el historial no dice a qué
        // proyecto entró o salió el equipo.
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('variante_producto_id')
                ->constrained('clientes')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->after('cliente_id')
                ->constrained('cliente_sucursales')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['sucursal_id']);
            $table->dropColumn(['cliente_id', 'sucursal_id']);
        });

        Schema::table('stock_productos', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['sucursal_id']);
            $table->dropUnique('stock_producto_cliente_sucursal_unico');
            $table->dropIndex(['cliente_id', 'sucursal_id']);
            $table->dropColumn(['cliente_id', 'sucursal_id']);
            $table->unique(['producto_id', 'variante_producto_id']);
        });
    }
};
