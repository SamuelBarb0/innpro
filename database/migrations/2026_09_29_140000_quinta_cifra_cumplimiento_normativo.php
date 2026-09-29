<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La quinta cifra del banner: «100% Cumplimiento normativo».
 *
 * El manual de restructuración (22-sep-2026) pedía «los 4 módulos» en el texto
 * pero listaba CINCO indicadores en la tabla, y ese día se dejó fuera este.
 * Innpro lo echó en falta (29-sep-2026), así que entra en la posición que le
 * da el manual: entre «Competencias especializadas» y «Satisfacción».
 *
 * Solo se añade si no está ya: si alguien lo metió a mano desde el panel, no
 * se duplica.
 */
return new class extends Migration
{
    private const CIFRA = ['numero' => '100', 'sufijo' => '%', 'etiqueta' => 'Cumplimiento normativo'];

    public function up(): void
    {
        $this->cambiar(function (array $cifras) {
            foreach ($cifras as $c) {
                if (stripos((string) ($c['etiqueta'] ?? ''), 'cumplimiento') !== false) {
                    return $cifras;
                }
            }

            // Antes de «Satisfacción», o al final si ya no está.
            $pos = count($cifras);
            foreach ($cifras as $i => $c) {
                if (stripos((string) ($c['etiqueta'] ?? ''), 'satisfacci') !== false) {
                    $pos = $i;
                    break;
                }
            }

            array_splice($cifras, $pos, 0, [self::CIFRA]);

            return $cifras;
        });
    }

    public function down(): void
    {
        $this->cambiar(fn (array $cifras) => array_values(array_filter(
            $cifras,
            fn ($c) => ($c['etiqueta'] ?? null) !== self::CIFRA['etiqueta']
        )));
    }

    private function cambiar(callable $cambio): void
    {
        $portada = DB::table('sitio_paginas')->where('tipo', 'landing')->orderBy('orden')->value('id');
        $bloque = $portada ? DB::table('sitio_bloques')->where('pagina_id', $portada)->where('clave', 'hero')->first() : null;

        if (! $bloque) {
            return;
        }

        $datos = json_decode((string) $bloque->datos, true);
        $datos = is_array($datos) ? $datos : [];
        $datos['estadisticas'] = $cambio(array_values((array) ($datos['estadisticas'] ?? [])));

        DB::table('sitio_bloques')->where('id', $bloque->id)->update([
            'datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }
};
