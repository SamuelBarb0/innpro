<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Innpro escribe el acrónimo como «Cctv», no «CCTV» (22-sep-2026).
 *
 * Es su criterio de marca, así que manda sobre el texto sembrado. Se cambia en
 * el contenido guardado —portada, páginas de servicio y sus campos de SEO—
 * porque ahí es donde lo lee el visitante.
 *
 * Lo que NO se toca, a propósito:
 *
 *  - Los SLUGS (`camaras-de-seguridad-cctv-bogota`). Van en minúsculas, así que
 *    esto no los afecta, y además una URL posicionada no se cambia por un
 *    detalle de estilo: costaría el posicionamiento que ya tiene.
 *  - Los títulos que la hoja de estilo pinta en mayúsculas igualmente (las
 *    categorías de marcas, las etiquetas de sector): ahí se seguirá LEYENDO
 *    «CCTV» aunque en la base diga «Cctv». Es cosa del diseño, no del texto.
 */
return new class extends Migration
{
    /** Columnas de texto donde puede aparecer el acrónimo. */
    private const COLUMNAS = [
        'sitio_bloques' => ['antetitulo', 'titulo', 'subtitulo', 'texto', 'datos'],
        'sitio_paginas' => ['titulo', 'subtitulo', 'resumen', 'contenido', 'seo_titulo', 'seo_descripcion'],
    ];

    public function up(): void
    {
        $this->reemplazar('CCTV', 'Cctv');
    }

    public function down(): void
    {
        $this->reemplazar('Cctv', 'CCTV');
    }

    private function reemplazar(string $de, string $a): void
    {
        foreach (self::COLUMNAS as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                DB::table($tabla)
                    ->where($columna, 'like', '%'.$de.'%')
                    ->update([$columna => DB::raw("REPLACE({$columna}, ".DB::getPdo()->quote($de).', '.DB::getPdo()->quote($a).')')]);
            }
        }
    }
};
