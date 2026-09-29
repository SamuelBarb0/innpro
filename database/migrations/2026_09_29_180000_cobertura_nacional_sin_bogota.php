<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cobertura regional y nacional, no solo Bogotá.
 *
 * Innpro (29-sep-2026): «la idea es tener una cobertura regional o nacional, no
 * solo en la capital». Títulos, SEO, textos y URLs decían «en Bogotá», así que
 * el sitio se anunciaba como un proveedor de la capital.
 *
 * SE QUEDA lo que no es cobertura sino un dato:
 *   - la dirección de la sede (`sitio_ciudad`), que tiene que coincidir con
 *     el perfil de Google Business;
 *   - «Metro de Bogotá», que es el nombre de un cliente;
 *   - la frase de cobertura del panel, que ya dice «regional y nacional».
 *
 * Las URLs pierden el «-bogota» y las viejas redirigen con 301: el sitio ya
 * está indexado, y cambiar una URL sin redirección tira lo que Google ya tenía.
 * Las redirecciones que apuntaban a las URLs viejas se reapuntan a las nuevas,
 * para que no queden cadenas de dos saltos.
 *
 * Cada reemplazo es literal y solo actúa si el texto sigue siendo el
 * publicado: si Innpro ya lo editó desde el panel, se respeta.
 */
return new class extends Migration
{
    /** Frases concretas primero; el «en Bogotá» genérico, al final. */
    private const TEXTOS = [
        'en Bogotá y Cundinamarca' => 'a nivel regional y nacional',
        'Cotice su sistema de Cctv en Bogotá.' => 'Cotice su sistema de Cctv.',
        'en Bogotá' => 'en Colombia',
        ' bogotá' => ' colombia',   // palabras clave, en minúscula
    ];

    private const SLUGS = [
        'camaras-de-seguridad-cctv-bogota' => 'camaras-de-seguridad-cctv',
        'control-de-acceso-biometrico-facial-bogota' => 'control-de-acceso-biometrico-facial',
        'deteccion-de-incendios-audio-evacuacion-bogota' => 'deteccion-de-incendios-audio-evacuacion',
        'alquiler-equipos-trabajo-en-alturas-bogota' => 'alquiler-equipos-trabajo-en-alturas',
    ];

    private const CAMPOS_PAGINA = ['titulo', 'subtitulo', 'resumen', 'contenido', 'seo_titulo', 'seo_descripcion', 'seo_palabra_clave'];

    private const CAMPOS_BLOQUE = ['antetitulo', 'titulo', 'subtitulo', 'texto'];

    public function up(): void
    {
        $this->reemplazar(self::TEXTOS, self::SLUGS);
    }

    public function down(): void
    {
        // Solo textos y URLs: reutilizar reemplazar() para las redirecciones
        // crearía reglas del revés que apuntan a sí mismas.
        foreach (self::SLUGS as $viejo => $nuevo) {
            DB::table('sitio_redirecciones')->where('origen', "servicios/{$viejo}")->where('destino', "/servicios/{$nuevo}")->delete();
            DB::table('sitio_redirecciones')->where('destino', "/servicios/{$nuevo}")->update(['destino' => "/servicios/{$viejo}"]);
            DB::table('sitio_paginas')->where('slug', $nuevo)->update(['slug' => $viejo]);
        }

        $inverso = ['a nivel regional y nacional' => 'en Bogotá y Cundinamarca', 'en Colombia' => 'en Bogotá', ' colombia' => ' bogotá'];

        foreach (DB::table('sitio_paginas')->get() as $p) {
            $cambios = [];
            foreach (self::CAMPOS_PAGINA as $campo) {
                $nuevo = $this->aplicar($p->{$campo}, $inverso);
                if ($nuevo !== $p->{$campo}) {
                    $cambios[$campo] = $nuevo;
                }
            }
            if ($cambios) {
                DB::table('sitio_paginas')->where('id', $p->id)->update($cambios);
            }
        }
    }

    private function reemplazar(array $textos, array $slugs): void
    {
        foreach (DB::table('sitio_paginas')->get() as $p) {
            $cambios = [];

            foreach (self::CAMPOS_PAGINA as $campo) {
                $nuevo = $this->aplicar($p->{$campo}, $textos);
                if ($nuevo !== $p->{$campo}) {
                    $cambios[$campo] = $nuevo;
                }
            }

            if (isset($slugs[$p->slug])) {
                $cambios['slug'] = $slugs[$p->slug];
            }

            if ($cambios) {
                DB::table('sitio_paginas')->where('id', $p->id)->update($cambios + ['updated_at' => now()]);
            }
        }

        foreach (DB::table('sitio_bloques')->get() as $b) {
            $cambios = [];

            foreach (self::CAMPOS_BLOQUE as $campo) {
                $nuevo = $this->aplicar($b->{$campo} ?? null, $textos);
                if ($nuevo !== ($b->{$campo} ?? null)) {
                    $cambios[$campo] = $nuevo;
                }
            }

            // En `datos` va JSON: se decodifica para no romper los escapes, y
            // «Metro de Bogotá» sobrevive porque no coincide con ninguna frase.
            if ($b->datos) {
                $datos = json_decode($b->datos, true);
                if (is_array($datos)) {
                    array_walk_recursive($datos, function (&$v) use ($textos) {
                        $v = is_string($v) ? $this->aplicar($v, $textos) : $v;
                    });
                    $json = json_encode($datos, JSON_UNESCAPED_UNICODE);
                    if ($json !== json_encode(json_decode($b->datos, true), JSON_UNESCAPED_UNICODE)) {
                        $cambios['datos'] = $json;
                    }
                }
            }

            if ($cambios) {
                DB::table('sitio_bloques')->where('id', $b->id)->update($cambios);
            }
        }

        $activas = DB::table('sitio_paginas')->where('activo', true)->pluck('slug')->all();

        foreach ($slugs as $viejo => $nuevo) {
            // El ORIGEN se guarda normalizado, sin barras y en minúscula (ver
            // SitioRedireccion::normalizar): con la barra delante la regla
            // existe pero no casa nunca. El DESTINO sí lleva la barra.
            $origen = "servicios/{$viejo}";
            $destino = "/servicios/{$nuevo}";

            // Lo que ya apuntaba a la URL vieja pasa a apuntar a la nueva.
            DB::table('sitio_redirecciones')->where('destino', "/servicios/{$viejo}")->update(['destino' => $destino]);

            // Si existía la redirección inversa, sobra: crearía un bucle.
            DB::table('sitio_redirecciones')->where('origen', "servicios/{$nuevo}")->delete();

            // Una página retirada ya tiene su propia redirección (a la portada)
            // y no se toca: mandarla a su URL nueva daría un 404.
            if (! in_array($nuevo, $activas, true) && ! in_array($viejo, $activas, true)) {
                continue;
            }

            DB::table('sitio_redirecciones')->updateOrInsert(
                ['origen' => $origen],
                ['destino' => $destino, 'codigo' => 301, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    private function aplicar(?string $texto, array $textos): ?string
    {
        return $texto === null ? null : strtr($texto, $textos);
    }
};
