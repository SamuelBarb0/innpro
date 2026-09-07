<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los años de experiencia dejan de estar escritos a mano.
 *
 * El cliente reportó que el dato no cuadra, y es cierto: el sitio viejo dice
 * «13 años» en la portada y «más de 9 años» en Nuestra Empresa, y el sitio
 * nuevo heredó el 13 en cuatro sitios distintos —el titular del hero, la cifra
 * animada, el resumen y la meta descripción—. Escrito así el número envejece
 * solo: hay que acordarse de subirlo cada enero, en los cuatro, y nadie se
 * acuerda.
 *
 * Se guarda el AÑO DE INICIO en los ajustes y los textos pasan a llevar el
 * marcador `{anios}`, que se resuelve al pintar. El contenido sigue siendo
 * editable desde el panel: quien escriba puede dejar el marcador o poner un
 * número fijo si algún día quiere otra cosa.
 *
 * OJO CON EL AÑO: 2011 es una deducción, no un dato confirmado. Sale de cruzar
 * las dos cifras del sitio viejo («más de 9 años» en una página de 2020 y «13
 * años» en la portada), que apuntan las dos a 2011. Hay que confirmarlo con
 * Innpro y corregirlo desde *Sitio web → Ajustes* si no es exacto; es un campo,
 * no un despliegue.
 */
return new class extends Migration
{
    private const ANIO_DEDUCIDO = '2011';

    public function up(): void
    {
        DB::table('parametros')->updateOrInsert(
            ['nombre_parametro' => 'sitio_anio_fundacion'],
            [
                'valor_parametro' => self::ANIO_DEDUCIDO,
                'estado' => true,
                'comentario' => 'Año en que Innpro empezó a operar. Los años de experiencia se calculan desde aquí: escriba {anios} en cualquier texto del sitio.',
            ]
        );

        // Textos de la página: el número suelto pasa a ser el marcador.
        foreach (['resumen', 'seo_descripcion', 'titulo', 'subtitulo'] as $campo) {
            DB::table('sitio_paginas')
                ->where($campo, 'like', '%13 años%')
                ->update([$campo => DB::raw("REPLACE($campo, '13 años', '{anios} años')")]);
        }

        foreach (['antetitulo', 'titulo', 'subtitulo', 'texto'] as $campo) {
            DB::table('sitio_bloques')
                ->where($campo, 'like', '%13 años%')
                ->update([$campo => DB::raw("REPLACE($campo, '13 años', '{anios} años')")]);
        }

        // La cifra animada vive dentro del JSON de `datos`, así que hay que
        // abrirlo. Se busca por la ETIQUETA y no por el valor 13: si alguien ya
        // lo había corregido a mano a 14, la fila sigue siendo la misma y hay
        // que convertirla igual.
        foreach (DB::table('sitio_bloques')->whereNotNull('datos')->get(['id', 'datos']) as $bloque) {
            $datos = json_decode((string) $bloque->datos, true);

            if (! is_array($datos) || ! isset($datos['estadisticas']) || ! is_array($datos['estadisticas'])) {
                continue;
            }

            $tocado = false;

            foreach ($datos['estadisticas'] as $i => $stat) {
                if (! is_array($stat)) {
                    continue;
                }

                $etiqueta = mb_strtolower((string) ($stat['etiqueta'] ?? ''));

                if (str_contains($etiqueta, 'años de experiencia') && ($stat['numero'] ?? '') !== '{anios}') {
                    $datos['estadisticas'][$i]['numero'] = '{anios}';
                    $tocado = true;
                }
            }

            if ($tocado) {
                DB::table('sitio_bloques')->where('id', $bloque->id)->update([
                    'datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Se deshace dejando el número que corresponda HOY, no el 13 original:
        // volver a escribir un dato caduco sería reintroducir el fallo.
        $anios = (string) max(1, (int) date('Y') - (int) self::ANIO_DEDUCIDO);

        foreach (['resumen', 'seo_descripcion', 'titulo', 'subtitulo'] as $campo) {
            DB::table('sitio_paginas')
                ->where($campo, 'like', '%{anios}%')
                ->update([$campo => DB::raw("REPLACE($campo, '{anios}', '$anios')")]);
        }

        foreach (['antetitulo', 'titulo', 'subtitulo', 'texto'] as $campo) {
            DB::table('sitio_bloques')
                ->where($campo, 'like', '%{anios}%')
                ->update([$campo => DB::raw("REPLACE($campo, '{anios}', '$anios')")]);
        }

        foreach (DB::table('sitio_bloques')->whereNotNull('datos')->get(['id', 'datos']) as $bloque) {
            $datos = json_decode((string) $bloque->datos, true);

            if (! is_array($datos) || ! isset($datos['estadisticas']) || ! is_array($datos['estadisticas'])) {
                continue;
            }

            $tocado = false;

            foreach ($datos['estadisticas'] as $i => $stat) {
                if (is_array($stat) && ($stat['numero'] ?? '') === '{anios}') {
                    $datos['estadisticas'][$i]['numero'] = $anios;
                    $tocado = true;
                }
            }

            if ($tocado) {
                DB::table('sitio_bloques')->where('id', $bloque->id)->update([
                    'datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        DB::table('parametros')->where('nombre_parametro', 'sitio_anio_fundacion')->delete();
    }
};
