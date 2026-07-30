<?php

use App\Models\ListaPrecio;
use App\Models\Parametros;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes temporales (prospectos) para poder cotizar sin dar de alta un
 * cliente completo, que es lo que pidió Jorge en la reunión del 29 de julio.
 *
 * Un prospecto se crea con el nombre y poco más, así que hay que aflojar las
 * columnas que hoy son obligatorias. Se usa SQL crudo porque doctrine/dbal no
 * está instalado y `->change()` lo necesita.
 *
 * `numero_identificacion` conserva su índice UNIQUE: MySQL admite varios NULL
 * en un índice único, así que los prospectos sin NIT no chocan entre sí.
 */
return new class extends Migration
{
    /** Nombre del parámetro con la lista de precios estándar para prospectos. */
    private const PARAMETRO_LISTA = 'lista_precio_temporales';

    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('es_temporal')->default(false)->after('activo');
            $table->index(['es_temporal', 'activo']);
        });

        foreach (['numero_identificacion', 'email', 'pais', 'ciudad'] as $columna) {
            DB::statement("ALTER TABLE clientes MODIFY {$columna} VARCHAR(255) NULL");
        }

        // Pedido 16 de la reunión: los temporales cotizan con un precio
        // estandarizado. Se deja apuntando a la primera lista para que exista
        // un valor válido; el admin lo cambia desde Parámetros.
        if (! Parametros::where('nombre_parametro', self::PARAMETRO_LISTA)->exists()) {
            Parametros::create([
                'nombre_parametro' => self::PARAMETRO_LISTA,
                'valor_parametro' => (string) (ListaPrecio::orderBy('id')->value('id') ?? ''),
                'estado' => true,
                'comentario' => 'Lista de precios que se asigna a los clientes temporales (prospectos) creados desde el cotizador.',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['es_temporal', 'activo']);
            $table->dropColumn('es_temporal');
        });

        // Las columnas NO se vuelven a poner NOT NULL: si ya quedaron filas con
        // nulos, la migración inversa fallaría y dejaría la tabla a medias.
    }
};
