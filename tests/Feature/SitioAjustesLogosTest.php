<?php

namespace Tests\Feature;

use App\Models\SitioBloque;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los ajustes de Innpro del 29-sep-2026 y su «Manual para logos».
 */
class SitioAjustesLogosTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_lineas_de_negocio_no_llevan_ver_mas(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Las tres tarjetas no tienen página propia: un «Ver más» ahí no lleva
        // a ninguna parte. El de los casos de éxito sí, y se queda.
        $this->assertStringNotContainsString('<span class="card__more">', $html);
        $this->assertStringContainsString('Ver más casos de éxito', $html);
    }

    public function test_los_textos_que_pidio_cambiar(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Interventoría, mantenimiento y soporte en Bogotá')
            ->assertSee('Acompañamiento técnico')
            ->assertDontSee('Solicite un estudio de seguridad')
            ->assertSee('¿Requiere asesoría?')
            ->assertDontSee('Proyectos que ya están funcionando')
            ->assertSee('Empresas que confiaron en nosotros');
    }

    public function test_cada_marca_del_manual_sale_con_su_logo(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['axis-communications', 'kantech', 'schneider-electric', 'charofil-cablofil', 'edwards-est', 'tripp-lite'] as $slug) {
            $this->assertStringContainsString("images/marcas/{$slug}.webp", $html);
        }

        // Las 40 del manual tienen archivo: ninguna cae al nombre escrito.
        // (+1: la vitrina de arriba pinta la primera marca.)
        $this->assertSame(40, substr_count($html, 'loading="lazy" decoding="async"'));
        $this->assertSame(41, substr_count($html, 'src="'.asset('images/marcas/')));
        $this->assertDoesNotMatchRegularExpression('/<li class="marcas__logo"[^>]*>\s*<span>/', $html);

        // Lo que el manual sacó de la lista.
        $this->assertStringNotContainsString('Etiquetado e identificación industrial', $html);
        $this->assertStringNotContainsString('alt="Dymo"', $html);
    }

    public function test_una_marca_sin_logo_sale_escrita(): void
    {
        $bloque = SitioBloque::where('clave', 'marcas')->firstOrFail();
        $datos = $bloque->datos;
        $datos['categorias'][0]['puntos'][] = 'Marca Nueva Sin Logo';
        $bloque->update(['datos' => $datos]);

        $this->get('/')->assertOk()->assertSee('<span>Marca Nueva Sin Logo</span>', false);
    }

    public function test_si_ya_lo_editaron_en_el_panel_la_migracion_no_lo_pisa(): void
    {
        $migracion = require database_path('migrations/2026_09_29_100000_marcas_con_logos_y_ajustes_de_texto.php');

        $migracion->down();
        SitioBloque::where('clave', 'casos')->update(['titulo' => 'Título propio de Innpro']);
        $migracion->up();

        $this->assertSame('Título propio de Innpro', SitioBloque::where('clave', 'casos')->value('titulo'));
        $this->assertSame('', SitioBloque::where('clave', 'acompanamiento')->value('titulo'));
    }

    public function test_la_vitrina_arranca_con_la_primera_marca_y_su_categoria(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<figure class="vitrina[^"]*"/', $html);
        $this->assertStringContainsString('src="'.asset('images/marcas/axis-communications.webp').'" alt=""', $html);
        $this->assertMatchesRegularExpression('/vitrina__cat">Videovigilancia \(Cctv\) y analítica</', $html);
        $this->assertMatchesRegularExpression('/vitrina__nombre">Axis Communications</', $html);
    }

    public function test_sin_logos_no_hay_vitrina(): void
    {
        $bloque = SitioBloque::where('clave', 'marcas')->firstOrFail();
        $bloque->update(['datos' => ['categorias' => [['titulo' => 'Otra', 'puntos' => ['Marca Sin Logo']]]]]);

        $this->get('/')->assertOk()->assertDontSee('class="vitrina', false)->assertSee('Marca Sin Logo');
    }
}
