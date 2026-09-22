<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El «Manual de implementación y restructuración web» que mandó Innpro (22-sep-2026).
 *
 * Reestructura la portada bloque por bloque. Lo que trae de nuevo:
 *
 *  - Las tres líneas de negocio definitivas, y FUERA el alquiler de equipos
 *    para trabajo en alturas: el cliente pide eliminarlo por completo.
 *  - Marcas y aliados, por especialidad. Van en texto y no en logos: los
 *    logotipos son marcas registradas de terceros y aquí no hay permiso de uso
 *    de ninguna.
 *  - Casos de éxito, con su página propia filtrable por sector (/experiencia).
 *  - La cinta de sectores, que estaba escrita en la plantilla y ahora se edita.
 *  - Política institucional y cobertura, que también estaban en la plantilla.
 *
 * DOS DECISIONES QUE NO SALEN DEL DOCUMENTO:
 *
 *  - Los contadores. El texto pide «los 4 módulos» y la tabla trae 5
 *    indicadores. Se dejan CUATRO, que es lo que cabe en una fila sin
 *    apretarse en móvil, y se deja fuera «100% cumplimiento normativo».
 *  - Las fotos de los casos de éxito. El documento pide fotografías reales de
 *    montaje y no llegaron: las tarjetas se sostienen sin ellas y cada
 *    proyecto admite su foto desde el panel cuando Innpro las entregue.
 *
 * Y una que sí sale, pero conviene recordar: la SOLUCIÓN de cada caso de éxito
 * se deja vacía salvo donde el documento la da. Decir qué se instaló en un
 * cliente real es una afirmación que solo Innpro puede firmar.
 *
 * Todo queda editable desde *Sitio web → Secciones de la portada*, *Páginas* y
 * *Ajustes*.
 */
return new class extends Migration
{
    private const ALTURAS = 'alquiler-equipos-trabajo-en-alturas-bogota';

    /** Orden de las secciones, que es el mismo en que se ven. */
    private const ORDEN = [
        'hero' => 0, 'servicios' => 1, 'acompanamiento' => 2, 'empresa' => 3,
        'identidad' => 4, 'lineamientos' => 5, 'marcas' => 6, 'casos' => 7,
        'experiencia' => 8, 'contacto' => 9,
    ];

    public function up(): void
    {
        $portada = DB::table('sitio_paginas')->where('tipo', 'landing')->orderBy('orden')->value('id');

        if (! $portada) {
            return;
        }

        $portada = (int) $portada;

        // ── 1. Banner principal ──────────────────────────────────────────────
        // El título se queda con {anios}, que se calcula solo desde el año de
        // fundación: escribir «15» aquí obliga a acordarse cada enero.
        $this->bloque($portada, 'hero', [
            'texto' => 'En el sector de la seguridad electrónica e ingeniería electrónica. Consultoría e integración tecnológica para proteger sus activos, su operación y su gente.',
        ], ['estadisticas' => [
            ['numero' => '{anios}', 'sufijo' => '', 'etiqueta' => 'Años de experiencia'],
            ['numero' => '3', 'sufijo' => '', 'etiqueta' => 'Macro-líneas de negocio'],
            ['numero' => '12', 'sufijo' => '+', 'etiqueta' => 'Competencias especializadas'],
            ['numero' => '95', 'sufijo' => '%', 'etiqueta' => 'Satisfacción del cliente'],
        ]]);

        // ── 3. Líneas de negocio y portafolio ────────────────────────────────
        $this->bloque($portada, 'servicios', [
            'antetitulo' => 'Líneas de negocio',
            'titulo' => 'Tres líneas de negocio y portafolio',
        ], ['tarjetas' => [
            [
                'numero' => 'Bloque 01',
                'icono' => 'camara',
                'titulo' => 'Seguridad electrónica',
                'texto' => 'Circuito cerrado de televisión (CCTV), control de acceso peatonal y vehicular, detección de intrusión y alarmas, protección perimetral, detección de incendios y audio evacuación.',
                'url' => '',
            ],
            [
                'numero' => 'Bloque 02',
                'icono' => 'llave',
                'titulo' => 'Infraestructura tecnológica',
                'texto' => 'Redes de datos, soluciones en fibra óptica, canalizaciones, tubería conduit, bandejas portacables y sistemas de energía regulada.',
                'url' => '',
            ],
            [
                'numero' => 'Bloque 03',
                'icono' => 'escudo',
                'titulo' => 'Consultoría, auditoría e interventoría técnica',
                'texto' => 'Estudios de riesgo, ingeniería de detalle, elaboración de pliegos técnicos, auditoría de sistemas instalados y supervisión e interventoría de obras.',
                'url' => '',
            ],
        ]]);

        // ── 4. Lineamientos estratégicos ─────────────────────────────────────
        $this->bloque($portada, 'lineamientos', [], ['puntos' => [
            ['texto' => 'Fuerte orientación al logro de objetivos, superando las expectativas.'],
            ['texto' => 'Posicionamiento basado en respaldo técnico y reputación de marca.'],
            ['texto' => 'Gestión del conocimiento e innovación tecnológica continua.'],
            ['texto' => 'Cultura de excelencia operativa y mejoramiento continuo.'],
            ['texto' => 'Comunicación oportuna, transparente y acompañamiento permanente.'],
        ]]);

        // ── 5. Marcas y aliados tecnológicos ─────────────────────────────────
        $this->crear($portada, 'marcas', 'tarjetas', [
            'antetitulo' => 'Con qué trabajamos',
            'titulo' => 'Marcas y aliados tecnológicos',
            'texto' => 'Integramos equipos de los fabricantes con los que ya trabaja la industria, elegidos según lo que pida cada proyecto y no al revés.',
        ], ['categorias' => [
            ['titulo' => 'Infraestructura tecnológica y cableado estructurado', 'puntos' => ['Panduit', 'Leviton', 'Siemon', 'Furukawa Electric', 'Cisco', 'Ubiquiti', 'Legrand', 'Belden', 'Quest International', 'Dexson', 'Charofil / Cablofil', 'Procables', 'Centelsa']],
            ['titulo' => 'Control de acceso y biometría', 'puntos' => ['ASSA ABLOY', 'Suprema Inc', 'ZKTeco', 'Rosslare Security', 'Hikvision', 'Dahua Technology', 'Bosch Security Systems', 'Intelbras']],
            ['titulo' => 'Sistemas de alarma e intrusión', 'puntos' => ['DSC', 'Ajax', 'Ademco / Honeywell Home / Resideo', 'RISCO Group', 'Paradox Security Systems', 'Bosch Security Systems', 'Intelbras']],
            ['titulo' => 'Videovigilancia (CCTV) y analítica', 'puntos' => ['Axis Communications', 'Pelco', 'Hanwha Techwin', 'Uniview (UNV)', 'Hikvision', 'Dahua Technology', 'Bosch Security Systems', 'Intelbras']],
            ['titulo' => 'Detección de incendios y audio evacuación', 'puntos' => ['Notifier', 'Honeywell', 'Edwards (EST)', 'Simplex', 'Mircom', 'Bosch']],
            ['titulo' => 'Energía regulada y protección eléctrica', 'puntos' => ['Powest', 'APC by Schneider Electric', 'Tripp Lite']],
            ['titulo' => 'Etiquetado e identificación industrial', 'puntos' => ['Dymo', 'Brady']],
        ]]);

        // ── 8. Casos de éxito ────────────────────────────────────────────────
        // Los cuatro primeros son los que salen en la portada.
        $this->crear($portada, 'casos', 'tarjetas', [
            'antetitulo' => 'Casos de éxito',
            'titulo' => 'Proyectos que ya están funcionando',
            'texto' => 'Una muestra de los proyectos ejecutados. Cada uno arranca con un estudio de seguridad y termina con la puesta en marcha y la capacitación del personal.',
        ], [
            'cta' => ['texto' => 'Ver más casos de éxito', 'url' => '/experiencia'],
            'proyectos' => $this->proyectos(),
        ]);

        // ── 7. Cinta de sectores ─────────────────────────────────────────────
        $this->bloque($portada, 'experiencia', [], ['sectores' => array_map(
            fn (string $s) => ['texto' => $s],
            [
                'Industrial', 'Comercial', 'Propiedad horizontal', 'Retail',
                'Logístico y zonas francas', 'Corporativo', 'Educativo', 'Salud',
                'Financiero', 'Gobierno', 'Hotelero', 'Infraestructura y transporte',
            ]
        )]);

        // ── Contáctenos ──────────────────────────────────────────────────────
        $this->bloque($portada, 'contacto', [
            'texto' => 'Cada proyecto es distinto. Contáctenos para conocer sus necesidades y creamos la solución a su medida.',
        ]);

        $this->ordenar(self::ORDEN, $portada);

        // ── El servicio que el cliente pide eliminar ──────────────────────────
        // «Eliminar por completo la tarjeta y el servicio de Alquiler de equipos
        // para trabajo en alturas». La página se despublica en vez de borrarse:
        // así el texto sigue ahí si lo reclaman, deja de salir en el menú, en la
        // portada y en el sitemap, y su URL —que lleva meses indexada— se
        // redirige en vez de devolver un 404.
        DB::table('sitio_paginas')
            ->where('slug', self::ALTURAS)
            ->update(['activo' => false, 'updated_at' => now()]);

        DB::table('sitio_redirecciones')->updateOrInsert(
            ['origen' => 'servicios/'.self::ALTURAS],
            ['destino' => '/', 'codigo' => 301, 'activo' => true, 'updated_at' => now(), 'created_at' => now()],
        );

        // ── 6. Pie de página y datos de contacto ─────────────────────────────
        $this->ajustes([
            'sitio_direccion' => 'Calle 5A # 72C-23',
            'sitio_celular' => '(+57) 312 428 6670',
            'sitio_telefono' => '(+57 601) 527 6828',
            'sitio_cobertura' => 'Sede principal en Bogotá con capacidad operativa y atención de proyectos a nivel regional y nacional.',
            'sitio_politica' => 'En Innpro Ingeniería S.A.S. estamos comprometidos con la prestación de servicios de ingeniería e integración tecnológica con altos estándares de calidad, oportunidad y eficacia. El objetivo, superar las expectativas de nuestros clientes mediante un equipo humano idóneo y altamente calificado, la mejora continua de nuestros procesos y la adopción constante de tecnologías de vanguardia.',
        ]);
    }

    /**
     * Los proyectos del documento, con el sector por el que se filtran.
     *
     * La `solucion` va vacía salvo donde el documento la da: lo que se instaló
     * en cada cliente lo sabe Innpro, y ponerlo aquí sería inventarlo. Se
     * rellena desde el panel, proyecto por proyecto.
     *
     * @return array<int,array<string,string>>
     */
    private function proyectos(): array
    {
        $casos = [
            ['Ministerio del Deporte', 'Gobierno e infraestructura'],
            ['Metro de Bogotá', 'Gobierno e infraestructura'],
            ['Centro Comercial Centro Mayor', 'Comercial y retail'],
            ['Hacienda Fontanar', 'Propiedad horizontal'],
            ['Secretaría de Educación', 'Gobierno e infraestructura'],
            ['Autogermana', 'Comercial y retail'],
            ['Dollarcity', 'Comercial y retail'],
            ['D1', 'Comercial y retail'],
            ['Condominio Fusca', 'Propiedad horizontal'],
            ['Chunuguá', 'Propiedad horizontal'],
            ['Colanta', 'Industrial, alimentos y logística'],
            ['QMax', 'Industrial, alimentos y logística'],
            ['DHL', 'Industrial, alimentos y logística'],
            ['Universidad de los Andes', 'Educativo'],
            ['Universidad de La Salle', 'Educativo'],
            ['Universidad del Área Andina', 'Educativo'],
        ];

        return array_map(fn (array $c) => [
            'cliente' => $c[0],
            'sector' => $c[1],
            'solucion' => '',
            'imagen' => '',
        ], $casos);
    }

    /** Actualiza un bloque que ya existe. Si no existe, no hace nada. */
    private function bloque(int $pagina, string $clave, array $columnas, array $datos = []): void
    {
        $bloque = DB::table('sitio_bloques')->where('pagina_id', $pagina)->where('clave', $clave)->first();

        if (! $bloque) {
            return;
        }

        if ($datos !== []) {
            $actual = json_decode((string) $bloque->datos, true);
            $columnas['datos'] = json_encode(array_merge(is_array($actual) ? $actual : [], $datos), JSON_UNESCAPED_UNICODE);
        }

        if ($columnas === []) {
            return;
        }

        DB::table('sitio_bloques')->where('id', $bloque->id)->update($columnas + ['updated_at' => now()]);
    }

    private function crear(int $pagina, string $clave, string $tipo, array $columnas, array $datos): void
    {
        DB::table('sitio_bloques')->updateOrInsert(
            ['pagina_id' => $pagina, 'clave' => $clave],
            $columnas + [
                'tipo' => $tipo,
                'datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                'orden' => self::ORDEN[$clave],
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function ordenar(array $orden, int $pagina): void
    {
        foreach ($orden as $clave => $posicion) {
            DB::table('sitio_bloques')->where('pagina_id', $pagina)->where('clave', $clave)->update(['orden' => $posicion]);
        }
    }

    /** @param  array<string,string>  $valores */
    private function ajustes(array $valores): void
    {
        foreach ($valores as $nombre => $valor) {
            DB::table('parametros')->updateOrInsert(
                ['nombre_parametro' => $nombre],
                ['valor_parametro' => $valor, 'estado' => true, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    /**
     * Deja la portada como estaba (volcado de producción del 22-sep-2026).
     *
     * Los ajustes del pie NO se revierten: dirección y teléfonos son datos
     * reales del negocio, y devolverlos a los de antes sería meter un dato
     * equivocado en la ficha que Google lee.
     */
    public function down(): void
    {
        $portada = DB::table('sitio_paginas')->where('tipo', 'landing')->orderBy('orden')->value('id');

        if (! $portada) {
            return;
        }

        $portada = (int) $portada;

        $this->bloque($portada, 'hero', [
            'texto' => 'En el mercado de la seguridad electrónica e ingeniería electrónica. Consultoría e integración tecnológica para proteger sus bienes, su operación y su gente.',
        ], ['estadisticas' => [
            ['numero' => '{anios}', 'sufijo' => '', 'etiqueta' => 'Años de experiencia'],
            ['numero' => '3', 'sufijo' => '', 'etiqueta' => 'Líneas de servicio'],
            ['numero' => '10', 'sufijo' => '+', 'etiqueta' => 'Capacidades técnicas'],
        ]]);

        $this->bloque($portada, 'servicios', [
            'antetitulo' => 'Lo que hacemos',
            'titulo' => 'Tres líneas de servicio',
        ], ['tarjetas' => [
            ['numero' => '01 / Servicios', 'titulo' => 'Prestación de servicios, mantenimiento e instalación', 'texto' => 'Toda nuestra experiencia y servicio al alcance de su mano.', 'url' => ''],
            ['numero' => '02 / Productos', 'titulo' => 'Servicio y venta de productos de seguridad electrónica', 'texto' => 'Toda la seguridad que necesitan sus bienes y su gente está aquí.', 'url' => ''],
            ['numero' => '03 / Alquiler', 'titulo' => 'Alquiler de equipos para trabajo en alturas', 'texto' => 'La seguridad y protección está primero en sus proyectos.', 'url' => ''],
        ]]);

        $this->bloque($portada, 'lineamientos', [], ['puntos' => [
            ['texto' => 'Fuerte orientación en satisfacer las necesidades del cliente'],
            ['texto' => 'Generar recordación y afianzamiento del prestigio de la marca'],
            ['texto' => 'Desarrollar la cultura de gestión del conocimiento'],
            ['texto' => 'Mejoramiento continuo en busca de la más alta excelencia'],
            ['texto' => 'Comunicación oportuna, adecuada y permanente'],
        ]]);

        $this->bloque($portada, 'contacto', [
            'texto' => 'Cuéntenos qué necesita proteger y le proponemos la solución. Atendemos proyectos industriales, comerciales y residenciales en Bogotá y Cundinamarca.',
        ]);

        DB::table('sitio_bloques')->where('pagina_id', $portada)->whereIn('clave', ['marcas', 'casos'])->delete();

        DB::table('sitio_paginas')->where('slug', self::ALTURAS)->update(['activo' => true, 'updated_at' => now()]);
        DB::table('sitio_redirecciones')->where('origen', 'servicios/'.self::ALTURAS)->delete();

        $this->ordenar([
            'hero' => 0, 'servicios' => 1, 'acompanamiento' => 2, 'empresa' => 3,
            'identidad' => 4, 'lineamientos' => 5, 'experiencia' => 6, 'contacto' => 7,
        ], $portada);
    }
};
