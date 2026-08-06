<?php

namespace Tests\Feature;

use App\Models\Parametros;
use App\Models\SitioBloque;
use App\Models\SitioPagina;
use App\Models\SitioRedireccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El sitio público y su SEO.
 *
 * El diagnóstico de la propuesta encontró que ninguna página tenía meta
 * descripción, que los diez servicios compartían una sola URL —así ninguno
 * puede posicionarse— y que Google no reconocía a Innpro como negocio local.
 * Esto comprueba que eso quedó resuelto y, sobre todo, que no se rompa después.
 */
class SitioPublicoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Se parte de cero.
     *
     * La migración de contenido siembra la portada y las cuatro páginas de
     * servicio —y eso está probado aparte, en SitioSembradoTest—, pero aquí
     * estorba: cada prueba tiene que montar exactamente el caso que afirma, no
     * heredar cinco páginas que no controla.
     */
    protected function setUp(): void
    {
        parent::setUp();

        SitioBloque::query()->delete();
        SitioPagina::query()->delete();
        SitioRedireccion::query()->delete();

        // Los ajustes se memorizan por proceso y las pruebas comparten proceso:
        // sin esto, el valor que leyó la prueba anterior se arrastra a la
        // siguiente y el fallo aparece lejos de su causa.
        \App\Support\Sitio::olvidar();
    }

    /** Cambia un ajuste ya sembrado por la migración, sin duplicar la fila. */
    private function ajuste(string $clave, string $valor): void
    {
        Parametros::updateOrCreate(
            ['nombre_parametro' => $clave],
            ['valor_parametro' => $valor, 'estado' => true],
        );

        \App\Support\Sitio::olvidar();
    }

    private function portada(): SitioPagina
    {
        $pagina = SitioPagina::create([
            'tipo' => SitioPagina::LANDING,
            'slug' => 'inicio',
            'titulo' => 'Seguridad electrónica en Bogotá',
            'subtitulo' => '13 años prestando servicios profesionales',
            'seo_titulo' => 'Seguridad electrónica en Bogotá | Innpro',
            'seo_descripcion' => 'Instalación y mantenimiento de CCTV en Bogotá.',
            'activo' => true,
            'publicado_at' => now(),
        ]);

        SitioBloque::create([
            'pagina_id' => $pagina->id,
            'clave' => 'hero',
            'titulo' => '13 años prestando servicios profesionales',
            'texto' => 'Consultoría e integración tecnológica.',
            'datos' => ['estadisticas' => [['numero' => '13', 'etiqueta' => 'Años']]],
        ]);

        return $pagina;
    }

    private function servicio(array $extra = []): SitioPagina
    {
        return SitioPagina::create(array_merge([
            'tipo' => SitioPagina::SERVICIO,
            'slug' => 'camaras-de-seguridad-cctv-bogota',
            'titulo' => 'Cámaras de seguridad y CCTV en Bogotá',
            'resumen' => 'Instalamos y mantenemos circuitos cerrados de televisión.',
            'contenido' => '<h2>Qué incluye</h2><p>Estudio de seguridad.</p>',
            'activo' => true,
            'publicado_at' => now(),
        ], $extra));
    }

    public function test_la_portada_sale_con_su_titulo_y_su_meta_descripcion(): void
    {
        $this->portada();

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Seguridad electrónica en Bogotá | Innpro</title>', false)
            ->assertSee('name="description" content="Instalación y mantenimiento de CCTV en Bogotá."', false)
            ->assertSee('rel="canonical"', false);
    }

    /**
     * Los llamados a la acción del hero.
     *
     * Se perdieron en el paso a base de datos y nadie se enteró: se guardan
     * como grupo de campos (texto + URL) y el ayudante que los leía descartaba
     * los arrays, así que devolvía null y la plantilla no pintaba nada. Sin
     * error, sin log, solo dos botones que dejaron de existir en la página
     * cuyo trabajo es convertir visitas en contactos.
     */
    public function test_la_portada_pinta_los_botones_de_llamada_a_la_accion(): void
    {
        $pagina = $this->portada();

        $pagina->bloque('hero')->update(['datos' => [
            'cta_principal' => ['texto' => 'Ver servicios', 'url' => '#servicios'],
            'cta_secundario' => ['texto' => 'Contáctenos', 'url' => '#contacto'],
            'estadisticas' => [['numero' => '13', 'etiqueta' => 'Años']],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ver servicios', false)
            ->assertSee('Contáctenos', false)
            ->assertSee('href="#servicios"', false);
    }

    public function test_cada_servicio_tiene_su_propia_url(): void
    {
        $this->portada();
        $this->servicio();

        $this->get('/servicios/camaras-de-seguridad-cctv-bogota')
            ->assertOk()
            ->assertSee('Cámaras de seguridad y CCTV en Bogotá', false)
            ->assertSee('Qué incluye', false);
    }

    /**
     * Sin esto Google no puede mostrar a Innpro como negocio local, que para
     * una empresa que instala en sitio suele traer más contactos que el sitio.
     */
    public function test_la_portada_lleva_datos_estructurados_de_negocio_local(): void
    {
        $this->portada();
        $this->ajuste('sitio_direccion', 'Carrera 32A # 5C-34');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"LocalBusiness"', $html);
        $this->assertStringContainsString('Carrera 32A # 5C-34', $html);
    }

    public function test_la_pagina_de_servicio_lleva_datos_estructurados_de_servicio(): void
    {
        $this->portada();
        $this->servicio();

        $html = $this->get('/servicios/camaras-de-seguridad-cctv-bogota')->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"Service"', $html);
    }

    public function test_el_sitemap_lista_lo_publicado_y_deja_fuera_lo_marcado_noindex(): void
    {
        $this->portada();
        $this->servicio();
        // Pedirle a Google que indexe en el sitemap algo que la propia página
        // le prohíbe es una señal contradictoria, y la reporta como error.
        $this->servicio(['slug' => 'oculta', 'titulo' => 'Oculta', 'seo_noindex' => true]);
        $this->servicio(['slug' => 'borrador', 'titulo' => 'Borrador', 'activo' => false]);

        $xml = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString('/servicios/camaras-de-seguridad-cctv-bogota', $xml);
        $this->assertStringNotContainsString('/servicios/oculta', $xml);
        $this->assertStringNotContainsString('/servicios/borrador', $xml);
    }

    public function test_robots_apunta_al_sitemap_y_esconde_la_plataforma(): void
    {
        $cuerpo = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $cuerpo);
        $this->assertStringContainsString('Disallow: /dashboard', $cuerpo);
    }

    /**
     * El interruptor de pánico: mientras el sitio no sea el oficial del dominio,
     * dejarlo indexable lo pone a competir con el WordPress por las mismas
     * palabras, y dos páginas propias peleando posicionan peor que una.
     */
    public function test_el_interruptor_global_de_noindex_cierra_todo(): void
    {
        $this->portada();
        $this->ajuste('sitio_noindex', '1');

        $this->get('/')->assertOk()->assertSee('content="noindex, nofollow"', false);
        $this->assertStringContainsString('Disallow: /', $this->get('/robots.txt')->getContent());
    }

    public function test_las_urls_viejas_del_wordpress_redirigen_con_301(): void
    {
        $this->portada();
        $this->servicio();

        SitioRedireccion::create([
            'origen' => 'servicios',
            'destino' => '/servicios/camaras-de-seguridad-cctv-bogota',
            'codigo' => 301,
            'activo' => true,
        ]);

        $this->get('/servicios')
            ->assertStatus(301)
            ->assertRedirect(url('/servicios/camaras-de-seguridad-cctv-bogota'));
    }

    /** Una redirección apagada no debe seguir mandando tráfico a ningún lado. */
    public function test_una_redireccion_inactiva_no_actua(): void
    {
        $this->portada();

        SitioRedireccion::create(['origen' => 'vieja', 'destino' => '/', 'codigo' => 301, 'activo' => false]);

        $this->get('/vieja')->assertNotFound();
    }

    /**
     * El título tiene que caber en el resultado de Google: si se pasa de 60
     * caracteres se corta por el final, que es justo donde va la ciudad.
     */
    public function test_el_titulo_seo_se_calcula_solo_y_no_se_pasa_de_largo(): void
    {
        $pagina = $this->servicio(['seo_titulo' => null, 'titulo' => str_repeat('palabra larga ', 12)]);

        $this->assertLessThanOrEqual(60, mb_strlen($pagina->tituloSeo()));
    }

    public function test_una_pagina_no_publicada_no_es_alcanzable(): void
    {
        $this->portada();
        $this->servicio(['slug' => 'futura', 'publicado_at' => now()->addWeek()]);

        $this->get('/servicios/futura')->assertNotFound();
    }
}
