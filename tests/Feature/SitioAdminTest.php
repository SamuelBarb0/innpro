<?php

namespace Tests\Feature;

use App\Models\Parametros;
use App\Models\SitioPagina;
use App\Models\SitioRedireccion;
use App\Models\User;
use App\Support\Sitio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El panel del sitio.
 *
 * La propuesta promete que Innpro pueda publicar contenido «sin depender del
 * proveedor». Esto comprueba que se puede de verdad, y que solo pueda hacerlo
 * quien debe: el sitio público es la cara comercial de la empresa.
 */
class SitioAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('vendedor');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->vendedor = User::factory()->create();
        $this->vendedor->assignRole('vendedor');

        Sitio::olvidar();
    }

    public function test_solo_el_admin_entra_al_panel_del_sitio(): void
    {
        $this->actingAs($this->vendedor)->get(route('sitio.admin.ajustes'))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('sitio.admin.paginas'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('sitio.admin.ajustes'))->assertOk();
        $this->actingAs($this->admin)->get(route('sitio.admin.paginas'))->assertOk();
    }

    public function test_un_visitante_sin_sesion_no_llega_al_panel(): void
    {
        $this->get(route('sitio.admin.ajustes'))->assertRedirect(route('login'));
    }

    /** Cambiar el teléfono en un sitio tiene que cambiarlo en toda la página. */
    public function test_el_admin_edita_los_datos_de_contacto_y_salen_en_el_sitio(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sitio.admin.ajustes.guardar'), [
                'sitio_celular' => '+57 300 000 0000',
                'sitio_email' => 'nuevo@innproingenieria.com',
                'sitio_direccion' => 'Calle Nueva 123',
            ])
            ->assertRedirect();

        $this->assertSame('+57 300 000 0000', Parametros::valor('sitio_celular'));

        Sitio::olvidar();
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('nuevo@innproingenieria.com', $html);
        $this->assertStringContainsString('Calle Nueva 123', $html);
    }

    public function test_el_admin_publica_una_pagina_nueva_y_queda_en_linea(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sitio.admin.paginas.guardar'), [
                'tipo' => SitioPagina::SERVICIO,
                'slug' => 'alarmas-y-monitoreo-bogota',
                'titulo' => 'Alarmas y monitoreo en Bogotá',
                'resumen' => 'Sistemas de alarma e intrusión con equipos de alta tecnología.',
                'contenido' => '<p>Detección de intrusos con mínimas falsas alarmas.</p>',
                'seo_titulo' => 'Alarmas y monitoreo en Bogotá | Innpro',
                'seo_descripcion' => 'Instalación de alarmas y monitoreo en Bogotá.',
                'activo' => '1',
            ])
            ->assertRedirect(route('sitio.admin.paginas'));

        $this->get('/servicios/alarmas-y-monitoreo-bogota')
            ->assertOk()
            ->assertSee('Alarmas y monitoreo en Bogotá', false);

        // Y entra sola al mapa del sitio: si hubiera que avisarle a Google a
        // mano, publicar desde el panel serviría de poco.
        $this->assertStringContainsString('/servicios/alarmas-y-monitoreo-bogota', $this->get('/sitemap.xml')->getContent());
    }

    /**
     * Una URL con tildes o espacios sale escapada en el navegador y es ilegible
     * en los resultados de Google.
     */
    public function test_se_rechaza_una_direccion_de_pagina_mal_escrita(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sitio.admin.paginas.guardar'), [
                'tipo' => SitioPagina::SERVICIO,
                'slug' => 'Cámaras de Seguridad Bogotá',
                'titulo' => 'Cámaras',
                'activo' => '1',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_no_se_permiten_dos_paginas_con_la_misma_direccion(): void
    {
        SitioPagina::create([
            'tipo' => SitioPagina::SERVICIO, 'slug' => 'repetida', 'titulo' => 'Primera', 'activo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('sitio.admin.paginas.guardar'), [
                'tipo' => SitioPagina::SERVICIO, 'slug' => 'repetida', 'titulo' => 'Segunda', 'activo' => '1',
            ])
            ->assertSessionHasErrors('slug');
    }

    /** Sin portada la raíz del sitio devuelve 404. */
    public function test_la_portada_no_se_puede_eliminar(): void
    {
        $portada = SitioPagina::deTipo(SitioPagina::LANDING)->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('sitio.admin.paginas.eliminar', $portada))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('sitio_paginas', ['id' => $portada->id]);
    }

    public function test_despublicar_una_pagina_la_saca_del_sitio_y_del_sitemap(): void
    {
        $pagina = SitioPagina::deTipo(SitioPagina::SERVICIO)->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('sitio.admin.paginas.toggle-activo', $pagina))
            ->assertRedirect();

        $this->get($pagina->url())->assertNotFound();
        $this->assertStringNotContainsString($pagina->slug, $this->get('/sitemap.xml')->getContent());
    }

    /**
     * Se guarda normalizada: pegada desde Search Console viene con dominio y
     * barra final, y una redirección que no encaja por una barra no existe.
     */
    public function test_la_redireccion_se_guarda_normalizada_y_funciona(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sitio.admin.redirecciones.guardar'), [
                'origen' => 'https://innproingenieria.com/Servicios/Camaras/',
                'destino' => '/servicios/camaras-de-seguridad-cctv-bogota',
                'codigo' => 301,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sitio_redirecciones', ['origen' => 'servicios/camaras']);

        $this->get('/servicios/camaras')->assertStatus(301);
    }

    public function test_no_se_duplica_una_redireccion_para_la_misma_direccion(): void
    {
        SitioRedireccion::create(['origen' => 'vieja', 'destino' => '/', 'codigo' => 301, 'activo' => true]);

        $this->actingAs($this->admin)
            ->post(route('sitio.admin.redirecciones.guardar'), [
                'origen' => '/VIEJA/', 'destino' => '/otra', 'codigo' => 301,
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, SitioRedireccion::where('origen', 'vieja')->count());
    }

    public function test_el_interruptor_de_noindex_se_puede_apagar_desde_el_panel(): void
    {
        Parametros::updateOrCreate(['nombre_parametro' => 'sitio_noindex'], ['valor_parametro' => '1', 'estado' => true]);

        // Ausente en el formulario = desmarcado.
        $this->actingAs($this->admin)
            ->post(route('sitio.admin.ajustes.guardar'), ['sitio_nombre' => 'Innpro'])
            ->assertRedirect();

        $this->assertSame('0', Parametros::valor('sitio_noindex'));
    }
}
