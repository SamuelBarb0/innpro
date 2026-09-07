<?php

namespace Tests\Feature;

use App\Models\Parametros;
use App\Models\SitioBloque;
use App\Models\SitioPagina;
use App\Support\Sitio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los pendientes que reportó Innpro sobre el sitio publicado: la política de
 * privacidad con URL legible, el aviso de cookies en español y los años de
 * experiencia sin contradecirse.
 *
 * Las tres cosas comparten la misma raíz —dato escrito a mano en varios sitios,
 * o sencillamente ausente—, así que van juntas.
 */
class SitioLegalYCookiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Los ajustes se memorizan por proceso y las pruebas lo comparten.
        Sitio::olvidar();
    }

    private function ajuste(string $clave, string $valor): void
    {
        Parametros::updateOrCreate(
            ['nombre_parametro' => $clave],
            ['valor_parametro' => $valor, 'estado' => true]
        );

        Sitio::olvidar();
    }

    /*
    |---------------------------------------------------------------------------
    | Páginas legales
    |---------------------------------------------------------------------------
    */

    public function test_la_politica_de_privacidad_responde_en_una_url_legible()
    {
        $this->get('/politica-de-privacidad')
            ->assertOk()
            ->assertSee('Política de tratamiento de datos personales')
            ->assertSee('Ley 1581 de 2012');
    }

    public function test_la_politica_de_cookies_responde()
    {
        $this->get('/politica-de-cookies')
            ->assertOk()
            ->assertSee('Cookies necesarias');
    }

    /**
     * La ruta legal cuelga de la raíz, así que sin restricción se tragaría el
     * resto del sitio. Esta es la prueba que impide que eso pase inadvertido.
     */
    public function test_la_ruta_legal_no_se_traga_las_demas_rutas()
    {
        $this->get('/login')->assertOk();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/una-pagina-que-no-existe')->assertNotFound();
    }

    public function test_las_legales_estan_en_el_sitemap_y_despues_de_los_servicios()
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/politica-de-privacidad', $xml);

        // Existen para el visitante, no para competir por búsquedas: van al
        // final de la cola de rastreo, detrás de la portada y los servicios.
        $this->assertGreaterThan(
            strpos($xml, '/servicios/'),
            strpos($xml, '/politica-de-privacidad'),
            'Las páginas legales deben ir después de las de servicio en el sitemap'
        );
    }

    public function test_el_pie_enlaza_las_paginas_legales()
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('/politica-de-privacidad', $html);
        $this->assertStringContainsString('/politica-de-cookies', $html);
        $this->assertStringContainsString('data-ck-abrir', $html);
    }

    /*
    |---------------------------------------------------------------------------
    | Aviso de cookies
    |---------------------------------------------------------------------------
    */

    public function test_el_aviso_de_cookies_se_pinta_en_espanol()
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('avisoCookies', $html);
        $this->assertStringContainsString('Este sitio usa cookies', $html);
        $this->assertStringContainsString('Rechazar', $html);
        $this->assertStringContainsString('Aceptar', $html);
    }

    /**
     * Lo que de verdad importa del aviso: que Analytics NO pueda dejar cookies
     * antes de que alguien acepte. Si el orden se invirtiera, el visitante ya
     * estaría marcado cuando le preguntamos.
     */
    public function test_analytics_arranca_denegado_y_carga_despues_del_consentimiento()
    {
        $this->ajuste('sitio_ga4', 'G-PRUEBA12345');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString("gtag('consent', 'default'", $html);
        $this->assertStringContainsString("analytics_storage: 'denied'", $html);

        $this->assertLessThan(
            strpos($html, 'googletagmanager.com'),
            strpos($html, "'consent', 'default'"),
            'El consentimiento por defecto debe declararse ANTES de cargar gtag'
        );
    }

    public function test_sin_id_de_analytics_no_se_carga_nada_de_google()
    {
        $this->ajuste('sitio_ga4', '');

        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com');
    }

    public function test_se_mide_el_envio_de_formularios()
    {
        $this->ajuste('sitio_ga4', 'G-PRUEBA12345');

        // La propuesta mide contactos, WhatsApp Y formularios.
        $this->get('/')->assertOk()
            ->assertSee('contacto_formulario', false)
            ->assertSee('contacto_whatsapp', false);
    }

    /*
    |---------------------------------------------------------------------------
    | Años de experiencia
    |---------------------------------------------------------------------------
    */

    public function test_los_anios_se_calculan_desde_el_anio_de_inicio()
    {
        $this->ajuste('sitio_anio_fundacion', '2011');

        $this->assertSame((int) date('Y') - 2011, Sitio::aniosExperiencia());
        $this->assertSame((string) ((int) date('Y') - 2011), Sitio::txt('{anios}'));
    }

    public function test_el_marcador_no_llega_nunca_a_la_pagina()
    {
        foreach (['/', '/politica-de-privacidad', '/politica-de-cookies'] as $ruta) {
            $html = $this->get($ruta)->assertOk()->getContent();

            $this->assertStringNotContainsString('{anios}', $html, "Marcador sin resolver en $ruta");
            $this->assertStringNotContainsString('{empresa}', $html, "Marcador sin resolver en $ruta");
            $this->assertStringNotContainsString('{direccion}', $html, "Marcador sin resolver en $ruta");
        }
    }

    /**
     * El fallo original: el número escrito a mano en cuatro sitios distintos,
     * que envejece solo y acaba contradiciéndose. Aquí se comprueba que la
     * portada entera —titular, cifra animada y meta descripción— sale del mismo
     * ajuste.
     */
    public function test_toda_la_portada_dice_los_mismos_anios()
    {
        $this->ajuste('sitio_anio_fundacion', '2000');

        $html = $this->get('/')->assertOk()->getContent();
        $esperado = (string) ((int) date('Y') - 2000);

        // El titular parte cada palabra en su propia etiqueta para animarlas,
        // así que la frase no aparece junta en el HTML: hay que quitarlas antes
        // de comparar.
        preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $m);
        $titular = preg_replace('/\s+/', ' ', trim(strip_tags($m[1] ?? '')));

        $this->assertStringContainsString($esperado.' años prestando', $titular);
        $this->assertStringContainsString('data-count="'.$esperado.'"', $html);
        $this->assertMatchesRegularExpression(
            '/name="description" content="[^"]*'.$esperado.' años de experiencia/',
            $html
        );
    }

    public function test_un_anio_de_inicio_ausente_no_rompe_la_portada()
    {
        Parametros::where('nombre_parametro', 'sitio_anio_fundacion')->delete();
        Sitio::olvidar();

        // Cae al respaldo del código, no a cero ni a un número negativo.
        $this->assertGreaterThan(0, Sitio::aniosExperiencia());
        $this->get('/')->assertOk();
    }

    /**
     * El NIT lo pide la Ley 1581 para identificar al responsable, y no está
     * publicado en ningún sitio de Innpro. Mientras siga vacío la página tiene
     * que DECIRLO: un hueco mudo en un texto legal no se descubre nunca.
     */
    public function test_el_nit_sin_configurar_se_ve_en_la_pagina()
    {
        $this->ajuste('sitio_nit', '');

        $this->get('/politica-de-privacidad')->assertOk()->assertSee('NIT por configurar');

        $this->ajuste('sitio_nit', '900.123.456-7');

        $this->get('/politica-de-privacidad')
            ->assertOk()
            ->assertSee('900.123.456-7')
            ->assertDontSee('NIT por configurar');
    }

    /*
    |---------------------------------------------------------------------------
    | Que lo de antes siga en pie
    |---------------------------------------------------------------------------
    */

    public function test_las_cuatro_landings_de_servicio_siguen_publicadas()
    {
        // Son los puntos 5 a 8 del cliente: existen desde agosto, pero el
        // reclamo llegó igual porque el dominio apunta al WordPress viejo.
        $slugs = [
            'camaras-de-seguridad-cctv-bogota',
            'control-de-acceso-biometrico-facial-bogota',
            'deteccion-de-incendios-audio-evacuacion-bogota',
            'alquiler-equipos-trabajo-en-alturas-bogota',
        ];

        foreach ($slugs as $slug) {
            $html = $this->get('/servicios/'.$slug)->assertOk()->getContent();

            $this->assertStringContainsString('"@type":"Service"', $html, "Sin datos estructurados en $slug");
            $this->assertMatchesRegularExpression('/<meta name="description" content=".{40,}"/', $html);
        }
    }

    public function test_el_boton_flotante_de_whatsapp_sigue_en_su_sitio()
    {
        $this->ajuste('sitio_whatsapp', '573124286670');

        $this->get('/')->assertOk()->assertSee('wa.me/573124286670', false);
    }
}
