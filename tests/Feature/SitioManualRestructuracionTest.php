<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El «Manual de implementación y restructuración web» de Innpro (22-sep-2026).
 *
 * Se vigila lo que el cliente pidió expresamente, no la redacción exacta: esa
 * la cambia Innpro desde el panel cuando quiera. Sobre todo lo que pidió
 * ELIMINAR, que es lo que vuelve solo si alguien restaura un respaldo viejo.
 */
class SitioManualRestructuracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_tres_lineas_de_negocio_reemplazan_a_las_viejas(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Seguridad electrónica')
            ->assertSee('Infraestructura tecnológica')
            ->assertSee('Consultoría, auditoría e interventoría técnica')
            ->assertDontSee('Alquiler de equipos para trabajo en alturas')
            ->assertDontSee('Servicio y venta de productos de seguridad electrónica');
    }

    public function test_los_contadores_son_cuatro_y_traen_los_indicadores_nuevos(): void
    {
        $portada = $this->get('/')->assertOk();

        $portada->assertSee('Macro-líneas de negocio')
            ->assertSee('Competencias especializadas')
            ->assertSee('Satisfacción del cliente')
            ->assertDontSee('Líneas de servicio')
            ->assertDontSee('Capacidades técnicas');

        // Cuatro, que es lo que cabe en una fila. El documento pide «los 4
        // módulos» en el texto y lista cinco indicadores en la tabla.
        $this->assertSame(4, substr_count($portada->getContent(), 'data-count='));
    }

    public function test_los_lineamientos_son_los_del_manual(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Fuerte orientación al logro de objetivos, superando las expectativas.')
            ->assertSee('Comunicación oportuna, transparente y acompañamiento permanente.')
            ->assertDontSee('Generar recordación y afianzamiento del prestigio de la marca');
    }

    public function test_las_marcas_salen_agrupadas_por_especialidad(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Marcas y aliados tecnológicos')
            ->assertSee('Videovigilancia (Cctv) y analítica')
            ->assertSee('Panduit')
            ->assertSee('Axis Communications')
            ->assertSee('Tripp Lite');
    }

    public function test_la_portada_ensena_cuatro_casos_y_el_boton_al_listado(): void
    {
        $portada = $this->get('/')->assertOk();

        $portada->assertSee('Ministerio del Deporte')
            ->assertSee('Ver más casos de éxito')
            // Los demás proyectos son de la página de experiencia, no de la portada.
            ->assertDontSee('Universidad del Área Andina');

        $this->assertSame(4, substr_count($portada->getContent(), 'class="caso bracket reveal"'));
    }

    public function test_la_pagina_de_experiencia_lista_todo_y_deja_filtrar(): void
    {
        $pagina = $this->get('/experiencia')->assertOk();

        $pagina->assertSee('Ministerio del Deporte')
            ->assertSee('Universidad del Área Andina')
            ->assertSee('Colanta')
            // Los sectores salen de los propios proyectos: crear uno nuevo es
            // escribirlo en un proyecto, sin tocar código.
            ->assertSee('Gobierno e infraestructura')
            ->assertSee('Propiedad horizontal')
            ->assertSee('data-filtro', false);

        $this->assertSame(16, substr_count($pagina->getContent(), 'class="caso bracket reveal"'));
    }

    public function test_la_cinta_de_sectores_sale_del_panel(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Logístico y zonas francas')
            ->assertSee('Infraestructura y transporte')
            // Lo que decía la cinta cuando estaba escrita en la plantilla.
            ->assertDontSee('Ingeniería electrónica</span>', false);
    }

    /**
     * Innpro escribe el acrónimo «Cctv». Es criterio suyo y manda sobre el
     * texto sembrado; los slugs siguen en minúscula y no se tocan, que una URL
     * posicionada no se cambia por un detalle de estilo.
     */
    public function test_el_acronimo_se_escribe_cctv(): void
    {
        foreach (['/', '/servicios/camaras-de-seguridad-cctv-bogota'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // Fuera de los slugs y de las URL, donde va en minúscula.
            $sinEnlaces = preg_replace('~(href|src|content)="[^"]*"~i', '', $html);

            $this->assertStringNotContainsString('CCTV', $sinEnlaces, "Sigue escrito «CCTV» en {$url}.");
            $this->assertStringContainsString('Cctv', $sinEnlaces);
        }
    }

    public function test_el_pie_trae_la_politica_la_cobertura_y_el_mapa(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('altos estándares de calidad, oportunidad y eficacia')
            ->assertSee('capacidad operativa y atención de proyectos a nivel regional y nacional')
            ->assertSee('google.com/maps/search/', false)
            ->assertSee('(+57) 312 428 6670')
            ->assertSee('(+57 601) 527 6828');
    }
}
