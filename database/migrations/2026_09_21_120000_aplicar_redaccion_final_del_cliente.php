<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La redacción final que mandó Innpro («Innpro_Redaccion_Final_MyTech», 16-sep-2026).
 *
 * El documento trae el texto revisado bloque por bloque y, al final, una
 * «versión final para MY Tech» con reglas que mandan sobre esa columna:
 *
 *  - «Bogotá» en títulos, encabezados e introducciones, NO en cada párrafo.
 *    Por eso varios «en Bogotá» del texto revisado no están aquí: la portada ya
 *    lo dice en el título, en la presentación y en el encabezado de
 *    Acompañamiento técnico, y cada página de servicio lo lleva en su H1.
 *  - Nada de afirmaciones absolutas: «la herramienta más potente», «máxima
 *    seguridad», «100% automatizada», «Auditoría 100% exacta» (que pasa a
 *    «Registro y trazabilidad digital de los accesos», literal del documento),
 *    «control total», «funcionamiento ininterrumpido», «los más altos
 *    estándares»…
 *  - Se mantienen las cuatro páginas de servicio del alcance. BMS, control
 *    perimetral y analítica de video van DENTRO de páginas que ya existen
 *    (control de acceso y CCTV); una landing nueva sería alcance adicional.
 *
 * Interventoría, mantenimiento y soporte no tienen página, así que van a la
 * portada como una sección nueva de tarjetas, igual que misión/visión/valores.
 *
 * Una corrección que no venía marcada: el bloque de BMS revisado perdió las
 * «alarmas perimetrales» y dejó un paréntesis sin cerrar; se restituyen desde
 * el texto original.
 *
 * DECISIÓN PENDIENTE DE INNPRO: la visión sale con «para 2029», que es su
 * texto original con la errata corregida. El documento ofrece una alternativa
 * sin fecha; si la eligen se cambia desde el panel, no hace falta desplegar.
 *
 * Todo sigue siendo editable desde *Sitio web → Secciones de la portada* y
 * *Páginas*.
 */
return new class extends Migration
{
    private const CCTV = 'camaras-de-seguridad-cctv-bogota';

    private const ACCESO = 'control-de-acceso-biometrico-facial-bogota';

    /** Orden de las secciones en el panel, que es el mismo en que se ven. */
    private const ORDEN = [
        'hero' => 0, 'servicios' => 1, 'acompanamiento' => 2, 'empresa' => 3,
        'identidad' => 4, 'lineamientos' => 5, 'experiencia' => 6, 'contacto' => 7,
    ];

    private const ORDEN_ANTERIOR = [
        'hero' => 0, 'servicios' => 1, 'empresa' => 2,
        'lineamientos' => 3, 'experiencia' => 4, 'contacto' => 5,
    ];

    public function up(): void
    {
        $portada = DB::table('sitio_paginas')->where('tipo', 'landing')->orderBy('orden')->value('id');

        if ($portada) {
            $this->portada((int) $portada);
        }

        DB::table('sitio_paginas')->where('slug', self::CCTV)->update([
            'contenido' => $this->contenidoCctv(),
            'updated_at' => now(),
        ]);

        DB::table('sitio_paginas')->where('slug', self::ACCESO)->update([
            'contenido' => $this->contenidoAcceso(),
            'updated_at' => now(),
        ]);
    }

    private function portada(int $portada): void
    {
        // ── Servicios: la propuesta de valor como entrada, con su botón ──
        $this->actualizarBloque($portada, 'servicios', [
            'texto' => 'Conectamos, protegemos y automatizamos sus espacios. Más que instalar equipos, diseñamos entornos seguros e inteligentes con el respaldo de un equipo técnico altamente calificado. Consolide toda su infraestructura tecnológica con un solo proveedor: desde sistemas avanzados de seguridad (CCTV, alarmas, control de acceso y perímetros) hasta redes de cableado estructurado, megafonía y respaldo de energía (corriente regulada).',
        ], ['cta' => ['texto' => 'Hable con nuestro equipo', 'url' => '#contacto']]);

        // ── Nuestra empresa: presentación institucional (bloques 1 y 2) ──
        $this->actualizarBloque($portada, 'empresa', [
            'texto' => "Innpro Ingeniería es una compañía de consultoría e integración tecnológica. Diversificamos la prestación de servicios de seguridad electrónica en Bogotá, con asesorías, diseños a la medida y orientación posventa, para garantizar relaciones comerciales duraderas con nuestros clientes.\n\n"
                ."Nuestra sólida trayectoria nos permite diseñar estudios de seguridad personalizados que responden a las necesidades específicas de cada cliente. A través de procesos de consultoría, planificación e ingeniería de detalle, brindamos una asesoría experta para implementar soluciones eficientes en control y seguridad electrónica.",
        ], ['cta' => ['texto' => 'Solicite un estudio de seguridad', 'url' => '#contacto']]);

        // ── Nuestra experiencia: alcance de proyectos (bloque 10) ──
        $this->actualizarBloque($portada, 'experiencia', [
            'texto' => 'Diseñamos y ejecutamos proyectos de ingeniería y seguridad electrónica a la medida de los sectores industrial, comercial y residencial. Nuestro alcance integra estudios de seguridad e ingeniería de detalle con un despliegue técnico riguroso que incluye canalizaciones, cableado y la instalación de sistemas de CCTV, control de acceso, detección de incendios e intrusión, y cerramientos perimetrales.',
        ]);

        // ── Acompañamiento técnico (bloques 6, 7 y 8) ──
        $this->crearBloque($portada, 'acompanamiento', 'tarjetas', [
            'antetitulo' => 'Acompañamiento técnico',
            'titulo' => 'Interventoría, mantenimiento y soporte en Bogotá',
        ], ['tarjetas' => [
            [
                'numero' => '01 / Interventoría',
                'icono' => 'escudo',
                'titulo' => 'Interventoría y supervisión técnica de proyectos',
                'texto' => 'Ofrecemos un servicio de supervisión integral alineado con los estándares del sector. Evaluamos el desarrollo de sus proyectos auditando la correcta instalación, la puesta en marcha y la capacitación técnica, conforme a las especificaciones del fabricante y del contrato. Toda nuestra verificación técnica está fundamentada en la normatividad legal vigente y en estudios de seguridad previos.',
                'cta' => 'Solicitar interventoría',
                'url' => '#contacto',
            ],
            [
                'numero' => '02 / Mantenimiento',
                'icono' => 'llave',
                'titulo' => 'Mantenimiento preventivo y correctivo',
                'texto' => 'Una falla en la seguridad no es una opción cuando la continuidad de su negocio está en juego. Conscientes de este riesgo, nuestro departamento técnico especializado aplica protocolos de mantenimiento preventivo y correctivo. No solo cuidamos el funcionamiento continuo de sus sistemas: optimizamos el rendimiento de los equipos y prolongamos su vida útil, transformando la prevención en tranquilidad.',
                'cta' => 'Programar mantenimiento',
                'url' => '#contacto',
            ],
            [
                'numero' => '03 / Soporte',
                'icono' => 'soporte',
                'titulo' => 'Soporte técnico especializado',
                'texto' => 'Respaldamos su operación con un equipo de soporte técnico altamente calificado, que ofrece una atención proactiva, atiende las incidencias con rapidez y brinda asesoría especializada ante cualquier requerimiento.',
                'cta' => 'Solicitar soporte',
                'url' => '#contacto',
            ],
        ]]);

        // ── Misión, visión y valores ──
        $this->crearBloque($portada, 'identidad', 'tarjetas', [
            'antetitulo' => 'Quiénes somos',
            'titulo' => 'Misión, visión y valores',
        ], ['tarjetas' => [
            [
                'titulo' => 'Misión',
                'texto' => 'Garantizar la continuidad operativa y la seguridad de las empresas mediante la ingeniería, integración y automatización de sistemas electrónicos de alta disponibilidad.',
            ],
            [
                'titulo' => 'Visión',
                'texto' => 'Consolidarnos como el aliado tecnológico estratégico para el sector corporativo e industrial a nivel regional, alcanzando la formalización de contratos de servicios activos para 2029.',
            ],
            [
                'titulo' => 'Valores',
                'puntos' => [
                    'Cumplimiento normativo',
                    'Ingeniería de precisión',
                    'Confidencialidad empresarial',
                    'Soporte técnico ágil',
                ],
            ],
        ]]);

        $this->ordenar($portada, self::ORDEN);
    }

    private function contenidoCctv(): string
    {
        return <<<'HTML'
<p>Proteja lo que más importa con tecnología actual de videovigilancia. Un circuito cerrado de televisión (CCTV) le permite monitorear su entorno y recibir alertas en tiempo real sobre lo que sucede. Con nuestros sistemas, además de grabar de forma local, puede supervisar su empresa, negocio u oficina desde cualquier lugar a través de internet, ver sus operaciones en vivo y reforzar la seguridad de sus bienes y de su equipo.</p>
<h2>Qué incluye el servicio</h2>
<ul><li>Estudio de seguridad para definir cuántas cámaras se necesitan y dónde, en vez de vender un paquete cerrado.</li><li>Ingeniería de detalle, canalización, tubería y cableado estructurado.</li><li>Instalación y configuración del grabador, con acceso desde celular.</li><li>Analítica de video: alertas automáticas, conteo de personas o vehículos y detección de patrones de movimiento.</li><li>Mantenimiento preventivo y correctivo, y soporte técnico.</li></ul>
<h2>Analítica de video con inteligencia artificial</h2>
<p>Convierta sus cámaras de seguridad en una herramienta de análisis. El valor de un sistema de vigilancia no está solo en grabar, sino en entender lo que ocurre. Con software de analítica de video, las cámaras pueden identificar determinados eventos y generar alertas en tiempo real, según las capacidades de la solución implementada. Al transformar las imágenes en datos (metadatos), el sistema avisa ante acciones sospechosas que podrían pasar desapercibidas para un operador, de modo que su personal de seguridad pueda actuar a tiempo.</p>
<p>Además, optimice su gestión con herramientas de conteo y clasificación automática de personas, vehículos y objetos, que analizan su comportamiento y aspecto.</p>
<p><a href="/#contacto">Solicite una demostración de analítica de video con IA →</a></p>
<h2>Para quién</h2>
<p>Proyectos industriales, comerciales y residenciales en Bogotá y Cundinamarca: bodegas, conjuntos residenciales, oficinas, locales y plantas.</p>
<p><strong>Cotice su sistema de CCTV en Bogotá.</strong></p>
HTML;
    }

    private function contenidoAcceso(): string
    {
        return <<<'HTML'
<p>Soluciones automatizadas para gestionar, auditar y restringir de manera eficiente el ingreso de personas y vehículos. Proteja los activos de su copropiedad u organización con tecnología de vanguardia integrada en tiempo real.</p>
<p>Un control de acceso administra de forma automatizada el ingreso a cada zona mediante huella digital, contraseña alfanumérica, tarjetas o llaveros RFID, o reconocimiento facial.</p>
<h2>Control de acceso peatonal</h2>
<ul><li>Biometría de alta precisión.</li><li>Sistemas de esclusas.</li><li>Credenciales virtuales.</li></ul>
<h2>Control de acceso vehicular</h2>
<ul><li>Reconocimiento de placas (LPR).</li><li>Sistemas RFID/TAG.</li><li>Barreras de alta velocidad.</li></ul>
<h2>Beneficios principales</h2>
<ul><li>Registro y trazabilidad digital de los accesos.</li><li>Integración nativa con el CCTV y las alarmas.</li><li>Eficiencia operativa: datos de entrada y salida en tiempo real.</li><li>Elimina las tarjetas magnéticas, que se prestan, se pierden y se clonan.</li><li>Controla la asistencia del personal y genera reportes para nómina.</li><li>Reduce costos y minimiza el fraude en el registro de horas.</li><li>Recuperación rápida de la inversión.</li></ul>
<h2>Integración</h2>
<p>El control de acceso se integra con el CCTV, las alarmas y la automatización de puertas y talanqueras, de modo que un mismo evento quede registrado y verificado en video.</p>
<h2>Automatización de edificios: BMS y portería virtual</h2>
<p>Nuestras soluciones de alta ingeniería permiten unificar y centralizar el control de la infraestructura de su edificio. Integramos los sistemas de seguridad electrónica crítica —control de accesos con esclusa anti-piggybacking, CCTV analítico con IA, citofonía digital IP/SIP y alarmas perimetrales— en una plataforma de gestión centralizada, controlada en tiempo real y accesible desde dispositivos móviles.</p>
<p><a href="/#contacto">Cotice la automatización de su edificio con nuestro equipo técnico →</a></p>
<h2>Control perimetral inteligente</h2>
<p>Sistemas de alta seguridad diseñados para blindar los límites físicos de su copropiedad u organización. Actúan como un escudo invisible que detecta de manera temprana los intentos de intrusión, antes de que se vulnere la infraestructura principal.</p>
<h3>Tecnologías de protección</h3>
<ul><li>Barreras fotoeléctricas y láser.</li><li>Cercos eléctricos.</li><li>Integración analítica.</li></ul>
<h3>Ventajas clave</h3>
<ul><li>Detección temprana.</li><li>Protección continua.</li></ul>
<p><a href="/#contacto">Cotice la protección perimetral de su instalación →</a></p>
<p><strong>Solicite una demostración de nuestras soluciones de control de acceso.</strong></p>
HTML;
    }

    /**
     * Cambia columnas de un bloque existente y MEZCLA claves en su `datos`: las
     * tarjetas, cifras o chips que ya tenga no se tocan.
     */
    private function actualizarBloque(int $pagina, string $clave, array $columnas, array $datos = []): void
    {
        $bloque = DB::table('sitio_bloques')->where('pagina_id', $pagina)->where('clave', $clave)->first();

        if (! $bloque) {
            return;
        }

        if ($datos !== []) {
            $actual = json_decode((string) $bloque->datos, true);
            $columnas['datos'] = json_encode(array_merge(is_array($actual) ? $actual : [], $datos), JSON_UNESCAPED_UNICODE);
        }

        DB::table('sitio_bloques')->where('id', $bloque->id)->update($columnas + ['updated_at' => now()]);
    }

    private function crearBloque(int $pagina, string $clave, string $tipo, array $columnas, array $datos): void
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

    private function ordenar(int $pagina, array $orden): void
    {
        foreach ($orden as $clave => $posicion) {
            DB::table('sitio_bloques')->where('pagina_id', $pagina)->where('clave', $clave)->update(['orden' => $posicion]);
        }
    }

    /**
     * Deja el texto que había en producción antes de este cambio (volcado el
     * 21-sep-2026), no el de la siembra: las páginas ya se habían retocado.
     */
    public function down(): void
    {
        $portada = DB::table('sitio_paginas')->where('tipo', 'landing')->orderBy('orden')->value('id');

        if ($portada) {
            DB::table('sitio_bloques')->where('pagina_id', $portada)->whereIn('clave', ['acompanamiento', 'identidad'])->delete();

            foreach (['servicios', 'empresa'] as $clave) {
                $bloque = DB::table('sitio_bloques')->where('pagina_id', $portada)->where('clave', $clave)->first();
                $datos = $bloque ? json_decode((string) $bloque->datos, true) : null;

                if (! is_array($datos)) {
                    continue;
                }

                if ($clave === 'servicios') {
                    unset($datos['cta']);
                    $columnas = ['texto' => null];
                } else {
                    $datos['cta'] = ['texto' => 'Hablemos', 'url' => '#contacto'];
                    $columnas = ['texto' => 'Innpro Ingeniería es una compañía de consultoría e integración tecnológica en donde proveemos las mejores soluciones para satisfacer las necesidades en seguridad electrónica y control, para que nuestros clientes y sus empresas implementen los mejores proyectos, innovadores y eficientes.'];
                }

                DB::table('sitio_bloques')->where('id', $bloque->id)->update($columnas + [
                    'datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                ]);
            }

            DB::table('sitio_bloques')->where('pagina_id', $portada)->where('clave', 'experiencia')->update([
                'texto' => "Hemos ejecutado proyectos en seguridad electrónica que comprenden estudios de seguridad, ingeniería de detalle, cableado, instalación de tubería, bandejas y canalizaciones, circuito cerrado de televisión, control de acceso, instalación de sistemas de detección de incendios, intrusión y cerramientos perimetrales.\n\nTambién en el campo de la ingeniería, desarrollando proyectos a nivel industrial, residencial y comercial a la medida de sus necesidades.",
            ]);

            $this->ordenar((int) $portada, self::ORDEN_ANTERIOR);
        }

        DB::table('sitio_paginas')->where('slug', self::CCTV)->update(['contenido' => <<<'HTML'
<p>El circuito cerrado de televisión permite la visualización en tiempo real, de forma local y remota, y la grabación por Internet: puede vigilar su negocio desde cualquier parte del mundo.</p>
<h2>Qué incluye el servicio</h2>
<ul><li>Estudio de seguridad para definir cuántas cámaras se necesitan y dónde, en vez de vender un paquete cerrado.</li><li>Ingeniería de detalle, canalización, tubería y cableado estructurado.</li><li>Instalación y configuración del grabador, con acceso desde celular.</li><li>Analítica de vídeo: alertas automáticas, conteo de personas o vehículos y detección de patrones de movimiento.</li><li>Mantenimiento preventivo y correctivo, y soporte técnico.</li></ul>
<h2>Para quién</h2>
<p>Proyectos industriales, comerciales y residenciales en Bogotá y Cundinamarca: bodegas, conjuntos residenciales, oficinas, locales y plantas.</p>
HTML]);

        DB::table('sitio_paginas')->where('slug', self::ACCESO)->update(['contenido' => <<<'HTML'
<p>Un control de acceso administra de forma automatizada el ingreso a cada zona mediante huella digital, contraseña alfanumérica, tarjetas o llaveros RFID, o reconocimiento facial.</p>
<h2>Qué gana su empresa</h2>
<ul><li>Elimina las tarjetas magnéticas, que se prestan, se pierden y se clonan.</li><li>Controla la asistencia del personal y genera reportes para nómina.</li><li>Entrega datos de entrada y salida en tiempo real.</li><li>Reduce costos y minimiza el fraude en el registro de horas.</li><li>Recuperación rápida de la inversión.</li></ul>
<h2>Integración</h2>
<p>El control de acceso se integra con el CCTV, las alarmas y la automatización de puertas y talanqueras, de modo que un mismo evento quede registrado y verificado en vídeo.</p>
HTML]);
    }
};
