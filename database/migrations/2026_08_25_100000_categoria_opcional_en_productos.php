<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La categoría deja de ser obligatoria, y borrar una deja de borrar productos.
 *
 * Innpro no clasifica por categoría (pedido 12 de la reunión del 29/07): el
 * módulo se ocultó del menú, pero la columna siguió siendo `NOT NULL` mientras
 * `ProductosController` ya la validaba como `nullable`. Es decir: guardar un
 * producto sin categoría pasaba la validación de Laravel y reventaba contra
 * MySQL.
 *
 * Y el `onDelete('cascade')` original era una mina: borrar una categoría se
 * llevaba por delante TODOS sus productos, incluidos los que solo estaban
 * borrados en suave (el cascade de la base no sabe de SoftDeletes, así que ese
 * borrado sí era definitivo). Pasa a `SET NULL`: se pierde la clasificación,
 * nunca el producto.
 *
 * Se usa SQL crudo porque doctrine/dbal no está instalado y `->change()` lo
 * necesita, igual que en 2026_07_30_100000.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('ALTER TABLE productos DROP FOREIGN KEY productos_categoria_id_foreign');
        DB::statement('ALTER TABLE productos MODIFY categoria_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE productos
            ADD CONSTRAINT productos_categoria_id_foreign
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Para poder volver a NOT NULL hay que darle una categoría a los que
        // quedaron sin ella; si no existe ninguna, se crea una de respaldo.
        $huerfanos = DB::table('productos')->whereNull('categoria_id')->count();

        if ($huerfanos > 0) {
            $categoriaId = DB::table('categorias')->orderBy('id')->value('id');

            if (! $categoriaId) {
                $categoriaId = DB::table('categorias')->insertGetId([
                    'nombre'     => 'Sin categoría',
                    'slug'       => 'sin-categoria',
                    'activo'     => true,
                    'orden'      => 999,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('productos')->whereNull('categoria_id')->update(['categoria_id' => $categoriaId]);
        }

        DB::statement('ALTER TABLE productos DROP FOREIGN KEY productos_categoria_id_foreign');
        DB::statement('ALTER TABLE productos MODIFY categoria_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE productos
            ADD CONSTRAINT productos_categoria_id_foreign
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE');

        Schema::enableForeignKeyConstraints();
    }
};
