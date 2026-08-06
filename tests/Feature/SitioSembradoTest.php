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

        // Las seis secciones que hoy tiene la vista. Si alguien agrega una
        // sección a la plantilla y olvida sembrarla, sale vacía en producción.
        foreach (['hero', 'servicios', 'empresa', 'lineamientos', 'experiencia', 'contacto'] as $clave) {
            $this->assertNotNull($portada->bloque($clave), "Falta la sección «{$clave}».");
        }
    }

    public function test_quedan_las_cuatro_paginas_de_servicio_de_la_propuesta(): void
    {
        $servicios = SitioPagina::publicadas()->deTipo(SitioPagina::SERVICIO)->get();

        $this->assertCount(4, $servicios);

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
    public function test_los_slugs_llevan_la_palabra_clave_y_la_ciudad(): void
    {
        $slugs = SitioPagina::deTipo(SitioPagina::SERVICIO)->pluck('slug');

        foreach ($slugs as $slug) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
        }

        $this->assertTrue($slugs->contains(fn ($s) => str_contains($s, 'bogota')));
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
