<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los ajustes que pidió Innpro el 29-sep-2026 al revisar la portada, más su
 * «Manual para logos marcas usadas».
 *
 *  - Acompañamiento técnico: fuera el título «Interventoría, mantenimiento y
 *    soporte en Bogotá», que repetía lo que ya dicen las tres tarjetas.
 *  - Nuestra empresa: el botón «Solicite un estudio de seguridad» pasa a
 *    «¿Requiere asesoría?».
 *  - Casos de éxito: el título pasa a «Empresas que confiaron en nosotros».
 *  - Marcas y aliados: la lista del manual, que es la definitiva. Trae seis
 *    categorías (sale «Etiquetado e identificación industrial»), cambia marcas
 *    dentro de cada una y las ordena como el documento. Los logos son archivos
 *    en public/images/marcas; aquí solo va la lista de nombres.
 *
 * (El «Ver más» de las líneas de negocio que también pidieron quitar no es un
 * dato: lo pintaba la plantilla de la tarjeta y se quitó ahí.)
 *
 * Los textos se cambian SOLO si siguen siendo los que se desplegaron: si
 * alguien ya los reescribió desde el panel, manda lo que escribió.
 */
return new class extends Migration
{
    private const ACOMPANAMIENTO = 'Interventoría, mantenimiento y soporte en Bogotá';

    private const CTA_ANTES = 'Solicite un estudio de seguridad';

    private const CTA_AHORA = '¿Requiere asesoría?';

    private const CASOS_ANTES = 'Proyectos que ya están funcionando';

    private const CASOS_AHORA = 'Empresas que confiaron en nosotros';

    public function up(): void
    {
        $portada = $this->portada();

        if (! $portada) {
            return;
        }

        $this->titulo($portada, 'acompanamiento', self::ACOMPANAMIENTO, '');
        $this->titulo($portada, 'casos', self::CASOS_ANTES, self::CASOS_AHORA);
        $this->cta($portada, 'empresa', self::CTA_ANTES, self::CTA_AHORA);
        $this->categorias($portada, [
            ['titulo' => 'Videovigilancia (Cctv) y analítica', 'puntos' => ['Axis Communications', 'Pelco', 'Hanwha Vision', 'Uniview (UNV)', 'Hikvision', 'Dahua Technology']],
            ['titulo' => 'Sistemas de alarma e intrusión', 'puntos' => ['DSC', 'Ajax Systems', 'Resideo', 'RISCO Group', 'Paradox Security Systems', 'Bosch Security Systems']],
            ['titulo' => 'Control de acceso y biometría', 'puntos' => ['ASSA ABLOY', 'Kantech', 'Suprema', 'ZKTeco', 'Rosslare Security', 'Intelbras']],
            ['titulo' => 'Infraestructura tecnológica y cableado estructurado', 'puntos' => ['Panduit', 'Leviton', 'Siemon', 'Furukawa Electric', 'Cisco', 'Ubiquiti', 'Legrand', 'Schneider Electric', 'Belden', 'Quest International', 'Dexson', 'Charofil / Cablofil', 'Procables', 'Centelsa']],
            ['titulo' => 'Detección de incendios y audio evacuación', 'puntos' => ['Notifier', 'Honeywell', 'Edwards (EST)', 'Simplex', 'Mircom']],
            ['titulo' => 'Energía regulada y protección eléctrica', 'puntos' => ['Powest', 'APC by Schneider Electric', 'Tripp Lite']],
        ]);
    }

    /** Lo que había en producción: redacción del 21-sep + manual del 22-sep + «Cctv». */
    public function down(): void
    {
        $portada = $this->portada();

        if (! $portada) {
            return;
        }

        $this->titulo($portada, 'acompanamiento', '', self::ACOMPANAMIENTO);
        $this->titulo($portada, 'casos', self::CASOS_AHORA, self::CASOS_ANTES);
        $this->cta($portada, 'empresa', self::CTA_AHORA, self::CTA_ANTES);
        $this->categorias($portada, [
            ['titulo' => 'Infraestructura tecnológica y cableado estructurado', 'puntos' => ['Panduit', 'Leviton', 'Siemon', 'Furukawa Electric', 'Cisco', 'Ubiquiti', 'Legrand', 'Belden', 'Quest International', 'Dexson', 'Charofil / Cablofil', 'Procables', 'Centelsa']],
            ['titulo' => 'Control de acceso y biometría', 'puntos' => ['ASSA ABLOY', 'Suprema Inc', 'ZKTeco', 'Rosslare Security', 'Hikvision', 'Dahua Technology', 'Bosch Security Systems', 'Intelbras']],
            ['titulo' => 'Sistemas de alarma e intrusión', 'puntos' => ['DSC', 'Ajax', 'Ademco / Honeywell Home / Resideo', 'RISCO Group', 'Paradox Security Systems', 'Bosch Security Systems', 'Intelbras']],
            ['titulo' => 'Videovigilancia (Cctv) y analítica', 'puntos' => ['Axis Communications', 'Pelco', 'Hanwha Techwin', 'Uniview (UNV)', 'Hikvision', 'Dahua Technology', 'Bosch Security Systems', 'Intelbras']],
            ['titulo' => 'Detección de incendios y audio evacuación', 'puntos' => ['Notifier', 'Honeywell', 'Edwards (EST)', 'Simplex', 'Mircom', 'Bosch']],
            ['titulo' => 'Energía regulada y protección eléctrica', 'puntos' => ['Powest', 'APC by Schneider Electric', 'Tripp Lite']],
            ['titulo' => 'Etiquetado e identificación industrial', 'puntos' => ['Dymo', 'Brady']],
        ]);
    }

    private function portada(): ?int
    {
        $id = DB::table('sitio_paginas')->where('tipo', 'landing')->orderBy('orden')->value('id');

        return $id ? (int) $id : null;
    }

    private function titulo(int $pagina, string $clave, string $antes, string $ahora): void
    {
        DB::table('sitio_bloques')
            ->where('pagina_id', $pagina)
            ->where('clave', $clave)
            ->where(fn ($q) => $antes === '' ? $q->whereNull('titulo')->orWhere('titulo', '') : $q->where('titulo', $antes))
            ->update(['titulo' => $ahora, 'updated_at' => now()]);
    }

    private function cta(int $pagina, string $clave, string $antes, string $ahora): void
    {
        $this->datos($pagina, $clave, function (array $datos) use ($antes, $ahora) {
            if (($datos['cta']['texto'] ?? null) === $antes) {
                $datos['cta']['texto'] = $ahora;
            }

            return $datos;
        });
    }

    private function categorias(int $pagina, array $categorias): void
    {
        $this->datos($pagina, 'marcas', fn (array $datos) => ['categorias' => $categorias] + $datos);
    }

    private function datos(int $pagina, string $clave, callable $cambio): void
    {
        $bloque = DB::table('sitio_bloques')->where('pagina_id', $pagina)->where('clave', $clave)->first();

        if (! $bloque) {
            return;
        }

        $actual = json_decode((string) $bloque->datos, true);

        DB::table('sitio_bloques')->where('id', $bloque->id)->update([
            'datos' => json_encode($cambio(is_array($actual) ? $actual : []), JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }
};
