<?php

use App\Models\Parametros;
use App\Models\SitioBloque;
use App\Models\SitioPagina;
use Illuminate\Database\Migrations\Migration;

/**
 * Siembra el sitio con el contenido que HOY está escrito a mano en la vista, y
 * crea las cuatro páginas de servicio de la propuesta.
 *
 * Se siembra desde la migración —y no desde un seeder aparte— para que el
 * despliegue sea un solo `migrate --force`: si el contenido quedara en un
 * seeder que hay que acordarse de correr, el sitio saldría en producción con
 * la portada vacía.
 *
 * Idempotente: `firstOrCreate` en todo. Correrla dos veces no duplica nada ni
 * pisa lo que el cliente ya haya editado desde el panel.
 */
return new class extends Migration
{
    /** @var array<string,array{0:string,1:string}> nombre => [valor, ayuda] */
    private array $parametros = [
        'sitio_nombre' => ['Innpro Ingeniería SAS', 'Nombre del negocio como debe aparecer en Google.'],
        'sitio_ciudad' => ['Bogotá', 'Ciudad principal de operación. Se usa en los títulos y en los datos estructurados.'],
        'sitio_direccion' => ['Carrera 32A # 5C-34', 'Dirección exacta. Debe ser IDÉNTICA a la del perfil de Google Business.'],
        'sitio_telefono' => ['(031) 583 0089', 'Teléfono fijo.'],
        'sitio_celular' => ['+57 312 4286670', 'Celular / WhatsApp comercial.'],
        'sitio_whatsapp' => ['573124286670', 'Número de WhatsApp en formato internacional, solo dígitos.'],
        'sitio_email' => ['comercial@innproingenieria.com', 'Correo comercial.'],
        'sitio_horario' => ['Lunes a viernes 8:00-17:30', 'Horario de atención.'],
        'sitio_cobertura' => ['Bogotá y Cundinamarca', 'Zona donde presta servicio.'],
        'sitio_facebook' => ['https://www.facebook.com/innproingenieria', 'Perfil de Facebook.'],
        'sitio_linkedin' => ['https://www.linkedin.com/company/innpro-ingenieria', 'Perfil de LinkedIn.'],
        'sitio_instagram' => ['https://www.instagram.com/innproingenieria', 'Perfil de Instagram.'],
        'sitio_ga4' => ['', 'ID de Google Analytics 4 (G-XXXXXXX). Vacío = no se carga el script.'],
        'sitio_search_console' => ['', 'Código de verificación de Google Search Console.'],
        'sitio_noindex' => ['0', 'En 1, TODO el sitio se marca noindex. Úsalo solo mientras no sea el sitio oficial del dominio.'],
    ];

    public function up(): void
    {
        foreach ($this->parametros as $nombre => [$valor, $ayuda]) {
            Parametros::firstOrCreate(
                ['nombre_parametro' => $nombre],
                ['valor_parametro' => $valor, 'comentario' => $ayuda, 'estado' => true, 'reservado' => false],
            );
        }

        $this->portada();
        $this->paginasDeServicio();
        $this->redirecciones();
    }

    /**
     * La portada, con exactamente el texto que ya estaba en la vista: el
     * objetivo de esta migración no es reescribir el sitio, es hacer editable
     * lo que ya se aprobó visualmente.
     */
    private function portada(): void
    {
        $portada = SitioPagina::firstOrCreate(
            ['slug' => 'inicio'],
            [
                'tipo' => SitioPagina::LANDING,
                'titulo' => 'Seguridad electrónica e ingeniería en Bogotá',
                'subtitulo' => '13 años prestando servicios profesionales',
                'resumen' => 'Instalación y mantenimiento de CCTV, control de acceso, detección de incendios y control perimetral en Bogotá. Consultoría e integración tecnológica con 13 años de experiencia.',
                // El diagnóstico marcó ALTO que la portada se titulara
                // «Mantenimiento y venta de equipos industriales»: no decía ni
                // seguridad electrónica ni Bogotá, que es justo lo que la gente
                // escribe en Google.
                'seo_titulo' => 'Seguridad electrónica en Bogotá | Innpro Ingeniería',
                'seo_descripcion' => 'Instalación y mantenimiento de cámaras CCTV, control de acceso y detección de incendios en Bogotá. 13 años de experiencia. Cotice hoy.',
                'seo_palabra_clave' => 'seguridad electrónica bogotá',
                'orden' => 0,
                'activo' => true,
                'publicado_at' => now(),
            ],
        );

        $bloques = [
            [
                'clave' => 'hero', 'orden' => 0, 'tipo' => 'hero',
                'antetitulo' => 'Secure Technology',
                'titulo' => '13 años prestando servicios profesionales',
                'texto' => 'En el mercado de la seguridad electrónica e ingeniería electrónica. Consultoría e integración tecnológica para proteger sus bienes, su operación y su gente.',
                'datos' => [
                    'cta_principal' => ['texto' => 'Ver servicios', 'url' => '#servicios'],
                    'cta_secundario' => ['texto' => 'Contáctenos', 'url' => '#contacto'],
                    'estadisticas' => [
                        ['numero' => '13', 'sufijo' => '', 'etiqueta' => 'Años de experiencia'],
                        ['numero' => '3', 'sufijo' => '', 'etiqueta' => 'Líneas de servicio'],
                        ['numero' => '10', 'sufijo' => '+', 'etiqueta' => 'Capacidades técnicas'],
                    ],
                ],
            ],
            [
                'clave' => 'servicios', 'orden' => 1, 'tipo' => 'tarjetas',
                'antetitulo' => 'Lo que hacemos',
                'titulo' => 'Tres líneas de servicio',
                'datos' => ['tarjetas' => [
                    ['numero' => '01 / Servicios', 'titulo' => 'Prestación de servicios, mantenimiento e instalación', 'texto' => 'Toda nuestra experiencia y servicio al alcance de su mano.', 'url' => ''],
                    ['numero' => '02 / Productos', 'titulo' => 'Servicio y venta de productos de seguridad electrónica', 'texto' => 'Toda la seguridad que necesitan sus bienes y su gente está aquí.', 'url' => ''],
                    ['numero' => '03 / Alquiler', 'titulo' => 'Alquiler de equipos para trabajo en alturas', 'texto' => 'La seguridad y protección está primero en sus proyectos.', 'url' => ''],
                ]],
            ],
            [
                'clave' => 'empresa', 'orden' => 2, 'tipo' => 'texto',
                'antetitulo' => 'Nuestra empresa',
                'titulo' => 'Conozca más sobre nuestra empresa',
                'texto' => 'Innpro Ingeniería es una compañía de consultoría e integración tecnológica en donde proveemos las mejores soluciones para satisfacer las necesidades en seguridad electrónica y control, para que nuestros clientes y sus empresas implementen los mejores proyectos, innovadores y eficientes.',
                'datos' => ['cta' => ['texto' => 'Hablemos', 'url' => '#contacto']],
            ],
            [
                'clave' => 'lineamientos', 'orden' => 3, 'tipo' => 'lista',
                'antetitulo' => 'Cómo trabajamos',
                'titulo' => 'Lineamientos estratégicos',
                'datos' => ['puntos' => [
                    ['texto' => 'Fuerte orientación en satisfacer las necesidades del cliente'],
                    ['texto' => 'Generar recordación y afianzamiento del prestigio de la marca'],
                    ['texto' => 'Desarrollar la cultura de gestión del conocimiento'],
                    ['texto' => 'Mejoramiento continuo en busca de la más alta excelencia'],
                    ['texto' => 'Comunicación oportuna, adecuada y permanente'],
                ]],
            ],
            [
                'clave' => 'experiencia', 'orden' => 4, 'tipo' => 'texto',
                'antetitulo' => 'Trayectoria',
                'titulo' => 'Nuestra experiencia',
                'texto' => "Hemos ejecutado proyectos en seguridad electrónica que comprenden estudios de seguridad, ingeniería de detalle, cableado, instalación de tubería, bandejas y canalizaciones, circuito cerrado de televisión, control de acceso, instalación de sistemas de detección de incendios, intrusión y cerramientos perimetrales.\n\nTambién en el campo de la ingeniería, desarrollando proyectos a nivel industrial, residencial y comercial a la medida de sus necesidades.",
                'datos' => ['chips' => [
                    ['texto' => 'Estudios de seguridad'], ['texto' => 'Ingeniería de detalle'],
                    ['texto' => 'Cableado estructurado'], ['texto' => 'Tubería y bandejas'],
                    ['texto' => 'CCTV'], ['texto' => 'Control de acceso'],
                    ['texto' => 'Detección de incendios'], ['texto' => 'Intrusión'],
                    ['texto' => 'Cerramientos perimetrales'],
                ]],
            ],
            [
                'clave' => 'contacto', 'orden' => 5, 'tipo' => 'contacto',
                'antetitulo' => 'Hablemos',
                'titulo' => 'Contáctenos',
                'texto' => 'Cuéntenos qué necesita proteger y le proponemos la solución. Atendemos proyectos industriales, comerciales y residenciales en Bogotá y Cundinamarca.',
            ],
        ];

        foreach ($bloques as $bloque) {
            SitioBloque::firstOrCreate(
                ['pagina_id' => $portada->id, 'clave' => $bloque['clave']],
                $bloque + ['activo' => true],
            );
        }
    }

    /**
     * Las cuatro páginas del frente B3 de la propuesta, el que produce el
     * crecimiento: cada servicio deja de esconderse dentro de una sola URL y
     * pasa a competir por su propia búsqueda.
     *
     * Los slugs llevan la palabra clave y la ciudad porque son la parte de la
     * URL que Google lee; el contenido queda redactado y listo para que Innpro
     * lo ajuste desde el panel, no como relleno de ejemplo.
     */
    private function paginasDeServicio(): void
    {
        $paginas = [
            [
                'slug' => 'camaras-de-seguridad-cctv-bogota',
                'titulo' => 'Cámaras de seguridad y CCTV en Bogotá',
                'subtitulo' => 'Instalación, mantenimiento y monitoreo remoto',
                'icono' => 'camara',
                'resumen' => 'Instalamos y mantenemos circuitos cerrados de televisión con visualización en tiempo real desde cualquier lugar, para empresas, conjuntos y locales en Bogotá.',
                'seo_titulo' => 'Cámaras de seguridad y CCTV en Bogotá | Innpro',
                'seo_descripcion' => 'Instalación de cámaras de seguridad y CCTV en Bogotá para empresas y conjuntos. Visualización remota, mantenimiento y soporte. Cotice sin costo.',
                'seo_palabra_clave' => 'instalación de cámaras de seguridad en bogotá',
                'contenido' => "<p>El circuito cerrado de televisión permite la visualización en tiempo real, de forma local y remota, y la grabación por Internet: puede vigilar su negocio desde cualquier parte del mundo.</p>\n<h2>Qué incluye el servicio</h2>\n<ul><li>Estudio de seguridad para definir cuántas cámaras se necesitan y dónde, en vez de vender un paquete cerrado.</li><li>Ingeniería de detalle, canalización, tubería y cableado estructurado.</li><li>Instalación y configuración del grabador, con acceso desde celular.</li><li>Analítica de vídeo: alertas automáticas, conteo de personas o vehículos y detección de patrones de movimiento.</li><li>Mantenimiento preventivo y correctivo, y soporte técnico.</li></ul>\n<h2>Para quién</h2>\n<p>Proyectos industriales, comerciales y residenciales en Bogotá y Cundinamarca: bodegas, conjuntos residenciales, oficinas, locales y plantas.</p>",
                'orden' => 1,
            ],
            [
                'slug' => 'control-de-acceso-biometrico-facial-bogota',
                'titulo' => 'Control de acceso biométrico y facial en Bogotá',
                'subtitulo' => 'Huella, reconocimiento facial, RFID y control de asistencia',
                'icono' => 'acceso',
                'resumen' => 'Sistemas automatizados que administran quién entra a cada zona por huella, rostro, clave o tarjeta, con reportes de asistencia en tiempo real.',
                'seo_titulo' => 'Control de acceso biométrico y facial en Bogotá | Innpro',
                'seo_descripcion' => 'Control de acceso por huella, rostro o RFID en Bogotá. Reportes de asistencia, menos fraude y recuperación rápida de la inversión. Cotice hoy.',
                'seo_palabra_clave' => 'control de acceso biométrico bogotá',
                'contenido' => "<p>Un control de acceso administra de forma automatizada el ingreso a cada zona mediante huella digital, contraseña alfanumérica, tarjetas o llaveros RFID, o reconocimiento facial.</p>\n<h2>Qué gana su empresa</h2>\n<ul><li>Elimina las tarjetas magnéticas, que se prestan, se pierden y se clonan.</li><li>Controla la asistencia del personal y genera reportes para nómina.</li><li>Entrega datos de entrada y salida en tiempo real.</li><li>Reduce costos y minimiza el fraude en el registro de horas.</li><li>Recuperación rápida de la inversión.</li></ul>\n<h2>Integración</h2>\n<p>El control de acceso se integra con el CCTV, las alarmas y la automatización de puertas y talanqueras, de modo que un mismo evento quede registrado y verificado en vídeo.</p>",
                'orden' => 2,
            ],
            [
                'slug' => 'deteccion-de-incendios-audio-evacuacion-bogota',
                'titulo' => 'Detección de incendios y audio de evacuación',
                'subtitulo' => 'Sistemas convencionales, análogos y algorítmicos',
                'icono' => 'incendio',
                'resumen' => 'Detección temprana de incendio con registro automático de eventos y sistemas de audio de evacuación integrados con la megafonía del edificio.',
                'seo_titulo' => 'Detección de incendios y evacuación en Bogotá | Innpro',
                'seo_descripcion' => 'Sistemas de detección de incendios y audio de evacuación en Bogotá: detectores ópticos, térmicos y de llama, con registro histórico de eventos.',
                'seo_palabra_clave' => 'sistemas de detección de incendios bogotá',
                'contenido' => "<p>Instalamos sistemas de detección de incendio convencionales, análogos y algorítmicos, con detectores iónicos, ópticos, de llama y térmicos. Los eventos quedan registrados automáticamente en el servidor principal, con histórico consultable.</p>\n<h2>Audio de evacuación</h2>\n<p>El sistema de evacuación integra la alarma por voz con el sonido ambiente del edificio, lo que reduce el costo de la inversión: una sola infraestructura cumple las dos funciones.</p>\n<h2>Qué incluye</h2>\n<ul><li>Estudio del inmueble y definición de zonas de detección.</li><li>Instalación de la central, detectores, pulsadores y sirenas.</li><li>Megafonía y audio de evacuación integrados.</li><li>Pruebas, puesta en marcha y capacitación al personal.</li><li>Mantenimiento periódico para que el sistema siga sirviendo el día que haga falta.</li></ul>",
                'orden' => 3,
            ],
            [
                'slug' => 'alquiler-equipos-trabajo-en-alturas-bogota',
                'titulo' => 'Alquiler de equipos para trabajo en alturas en Bogotá',
                'subtitulo' => 'Manlift, elevadores, andamios certificados y escaleras',
                'icono' => 'alturas',
                'resumen' => 'Alquiler de manlift, elevadores, andamios certificados y escaleras para acceder con seguridad a lugares de difícil acceso, reduciendo costos y tiempos de obra.',
                'seo_titulo' => 'Alquiler de equipos para trabajo en alturas en Bogotá',
                'seo_descripcion' => 'Alquiler de manlift, elevadores y andamios certificados en Bogotá. Equipos con certificación vigente para trabajo seguro en alturas. Cotice hoy.',
                'seo_palabra_clave' => 'alquiler equipos trabajo en alturas bogotá',
                'contenido' => "<p>Alquilamos manlift, elevadores, andamios certificados y escaleras, reduciendo el costo y los tiempos de sus proyectos al permitir el acceso seguro a lugares de difícil alcance.</p>\n<h2>Equipos disponibles</h2>\n<ul><li>Manlift y elevadores de personal.</li><li>Andamios certificados.</li><li>Escaleras de distintas alturas.</li></ul>\n<h2>Por qué alquilar y no comprar</h2>\n<p>Un equipo de alturas se usa por temporadas y exige certificación vigente y mantenimiento. Alquilarlo traslada esa carga y deja el costo dentro del proyecto que lo necesita.</p>",
                'orden' => 4,
            ],
        ];

        foreach ($paginas as $pagina) {
            SitioPagina::firstOrCreate(
                ['slug' => $pagina['slug']],
                $pagina + ['tipo' => SitioPagina::SERVICIO, 'activo' => true, 'publicado_at' => now()],
            );
        }
    }

    /**
     * Las URLs que el WordPress ya tenía indexadas. Sin esto, el día que el
     * dominio apunte aquí, cada una devuelve 404 y se pierde la antigüedad del
     * dominio, que es lo único que no se puede acelerar.
     */
    private function redirecciones(): void
    {
        $mapa = [
            'servicios' => '/servicios/camaras-de-seguridad-cctv-bogota',
            'nuestra-empresa' => '/#empresa',
            'contactenos' => '/#contacto',
            'noticias' => '/',
            'inicio' => '/',
        ];

        foreach ($mapa as $origen => $destino) {
            \App\Models\SitioRedireccion::firstOrCreate(
                ['origen' => $origen],
                ['destino' => $destino, 'codigo' => 301, 'activo' => true],
            );
        }
    }

    public function down(): void
    {
        SitioPagina::query()->delete();
        \App\Models\SitioRedireccion::query()->delete();
        Parametros::whereIn('nombre_parametro', array_keys($this->parametros))->delete();
    }
};
