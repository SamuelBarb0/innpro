<?php

namespace Tests\Feature;

use App\Models\Parametros;
use App\Models\SitioPagina;
use App\Models\SitioRedireccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Que la migración de contenido deje el sitio utilizable.
 *
 * Importa más de lo que parece: el contenido se siembra desde la migración
 * justamente para que el despliegue sea un solo `migrate --force`. Si ese
 * sembrado se rompe, el sitio sale a producción con la portada en blanco y
 * nadie se entera hasta que alguien la abre.
 */
class SitioSembradoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_portada_queda_sembrada_con_sus_secciones(): void
    {
        $portada = SitioPagina::deTipo(SitioPagina::LANDING)->first();

        $this->assertNotNull($portada, 'La migración no dejó portada.');
        $this->assertNotEmpty($portada->seo_titulo);
        $this->assertNotEmpty($portada->seo_descripcion);

        // Las secciones que hoy tiene la vista. Si alguien agrega una sección
        // a la plantilla y olvida sembrarla, sale vacía en producción.
        foreach (['hero', 'servicios', 'acompanamiento', 'empresa', 'identidad', 'lineamientos', 'experiencia', 'contacto'] as $clave) {
            $this->assertNotNull($portada->bloque($clave), "Falta la sección «{$clave}».");
        }
    }

    /**
     * Eran cuatro. Son TRES desde el manual de restructuración del 22-sep-2026:
     * Innpro pidió eliminar por completo el alquiler de equipos para trabajo en
     * alturas. Su página se despublicó —el texto sigue ahí por si lo reclaman— y
     * su URL, que llevaba meses indexada, quedó redirigida en vez de rota.
     */
    public function test_quedan_las_paginas_de_servicio_de_la_propuesta(): void
    {
        $servicios = SitioPagina::publicadas()->deTipo(SitioPagina::SERVICIO)->get();

        $this->assertCount(3, $servicios);
        $this->assertFalse($servicios->contains('slug', 'alquiler-equipos-trabajo-en-alturas-bogota'));
        $this->assertDatabaseHas('sitio_redirecciones', [
            'origen' => 'servicios/alquiler-equipos-trabajo-en-alturas-bogota',
            'activo' => true,
        ]);

        // Cada una tiene que llevar título y descripción propios: son el frente
        // que compite por búsquedas de compra, y sin ellos Google inventa el
        // resumen que lee el comprador.
        foreach ($servicios as $servicio) {
            $this->assertNotEmpty($servicio->seo_titulo, "«{$servicio->titulo}» sin título de SEO.");
            $this->assertNotEmpty($servicio->seo_descripcion, "«{$servicio->titulo}» sin meta descripción.");
            $this->assertLessThanOrEqual(60, mb_strlen($servicio->tituloSeo()));
            $this->assertLessThanOrEqual(155, mb_strlen($servicio->descripcionSeo()));
            $this->assertNotEmpty($servicio->contenido);
        }
    }

    /**
     * El slug es lo que Google lee de la URL. Si se siembra sin la palabra
     * clave y la ciudad, la página nace peleando en desventaja — y cambiarlo
     * después cuesta, porque una URL ya posicionada no se toca gratis.
     */
    /**
     * Antes las URLs llevaban «-bogota». Innpro pidió (29-sep-2026) cobertura
     * regional y nacional, así que el servicio ya no se ata a la capital ni en
     * la URL, y las viejas redirigen con 301 para no perder lo indexado.
     */
    public function test_los_slugs_no_atan_el_servicio_a_una_ciudad(): void
    {
        $slugs = SitioPagina::deTipo(SitioPagina::SERVICIO)->pluck('slug');

        foreach ($slugs as $slug) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
            $this->assertStringNotContainsString('bogota', $slug);
        }

        $this->get('/servicios/camaras-de-seguridad-cctv-bogota')
            ->assertStatus(301)
            ->assertRedirect(url('/servicios/camaras-de-seguridad-cctv'));
    }

    public function test_el_sitio_no_se_anuncia_como_proveedor_de_bogota(): void
    {
        foreach (['/', '/servicios/camaras-de-seguridad-cctv', '/servicios/control-de-acceso-biometrico-facial'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // «Sede principal en Bogotá» es la ubicación de la sede, no la
            // cobertura, y se queda a propósito: la frase sigue diciendo que
            // atienden a nivel regional y nacional.
            $sinSede = str_replace('Sede principal en Bogotá', '', $html);

            $this->assertStringNotContainsString('en Bogotá', $sinSede, "Queda «en Bogotá» en {$url}");
            $this->assertStringNotContainsString('"@type":"City"', $html, "El servicio sigue declarado para una ciudad en {$url}");
        }

        // La sede SÍ sigue en Bogotá: la dirección es un dato y tiene que
        // coincidir con el perfil de Google Business.
        $this->get('/')->assertSee('Bogotá');
    }

    public function test_quedan_las_redirecciones_de_las_urls_viejas(): void
    {
        $this->assertTrue(SitioRedireccion::where('origen', 'servicios')->exists());
        $this->assertTrue(SitioRedireccion::where('activo', true)->count() >= 3);
    }

    /**
     * El sitio nace SIN el interruptor de pánico puesto porque la decisión fue
     * que este sitio reemplaza al WordPress en el dominio. Si algún día se
     * siembra en 1 por error, el sitio entero queda invisible en Google.
     */
    public function test_el_sitio_no_nace_bloqueado_para_google(): void
    {
        $this->assertSame('0', Parametros::valor('sitio_noindex'));
    }

    public function test_el_nap_queda_sembrado_para_los_datos_estructurados(): void
    {
        foreach (['sitio_nombre', 'sitio_direccion', 'sitio_ciudad', 'sitio_celular', 'sitio_email'] as $clave) {
            $this->assertNotEmpty(Parametros::valor($clave), "Falta el parámetro «{$clave}».");
        }
    }
}
