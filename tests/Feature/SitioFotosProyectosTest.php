<?php

namespace Tests\Feature;

use App\Models\SitioBloque;
use App\Models\User;
use App\Support\Sitio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Las fotos de «Empresas que confiaron en nosotros».
 *
 * Antes el campo solo aceptaba la dirección de una foto ya publicada en
 * internet, y lo que Innpro tiene son las fotos de sus montajes en el
 * computador. Ahora se suben desde el panel.
 */
class SitioFotosProyectosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SitioBloque $casos;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->casos = SitioBloque::where('clave', 'casos')->firstOrFail();
        Sitio::olvidar();
    }

    private function guardar(array $proyectos)
    {
        return $this->actingAs($this->admin)->post(route('sitio.admin.secciones.guardar', $this->casos), [
            'titulo' => 'Empresas que confiaron en nosotros',
            'activo' => '1',
            'datos' => ['proyectos' => $proyectos],
        ]);
    }

    private function foto(int $fila): string
    {
        return $this->casos->fresh()->lista('proyectos')[$fila]['imagen'];
    }

    public function test_la_foto_subida_se_guarda_y_sale_en_la_portada(): void
    {
        $this->guardar([[
            'cliente' => 'Ministerio del Deporte',
            'sector' => 'Gobierno',
            'solucion' => 'CCTV',
            'imagen' => '',
            'imagen__archivo' => UploadedFile::fake()->image('IMG 0001.JPG', 800, 600),
        ]])->assertSessionHasNoErrors()->assertRedirect();

        $foto = $this->foto(0);

        $this->assertMatchesRegularExpression('#^/uploads/sitio/[\w-]+\.jpg$#', $foto);
        $this->assertFileExists(public_path(ltrim($foto, '/')));
        $this->get('/')->assertOk()->assertSee($foto, false);

        File::delete(public_path(ltrim($foto, '/')));
    }

    public function test_reemplazar_la_foto_borra_la_anterior_pero_no_toca_las_externas(): void
    {
        $this->guardar([
            ['cliente' => 'Uno', 'sector' => 'S', 'solucion' => 'X', 'imagen' => '',
                'imagen__archivo' => UploadedFile::fake()->image('a.png')],
            ['cliente' => 'Dos', 'sector' => 'S', 'solucion' => 'X', 'imagen' => 'https://ejemplo.com/foto.jpg'],
        ]);
        $primera = $this->foto(0);

        $this->guardar([
            ['cliente' => 'Uno', 'sector' => 'S', 'solucion' => 'X', 'imagen' => $primera,
                'imagen__archivo' => UploadedFile::fake()->image('b.webp')],
            ['cliente' => 'Dos', 'sector' => 'S', 'solucion' => 'X', 'imagen' => 'https://ejemplo.com/foto.jpg'],
        ])->assertSessionHasNoErrors();

        $segunda = $this->foto(0);

        $this->assertNotSame($primera, $segunda);
        $this->assertFileDoesNotExist(public_path(ltrim($primera, '/')));
        $this->assertFileExists(public_path(ltrim($segunda, '/')));
        $this->assertSame('https://ejemplo.com/foto.jpg', $this->foto(1));

        // Vaciar la dirección quita la foto y su archivo.
        $this->guardar([
            ['cliente' => 'Uno', 'sector' => 'S', 'solucion' => 'X', 'imagen' => ''],
        ]);

        $this->assertSame('', $this->foto(0));
        $this->assertFileDoesNotExist(public_path(ltrim($segunda, '/')));
    }

    public function test_se_rechaza_lo_que_no_es_una_foto(): void
    {
        $this->guardar([[
            'cliente' => 'Uno', 'sector' => 'S', 'solucion' => 'X', 'imagen' => '',
            'imagen__archivo' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
        ]])->assertSessionHasErrors('datos.proyectos.0.imagen__archivo');
    }

    public function test_el_panel_muestra_el_selector_de_archivo(): void
    {
        $this->actingAs($this->admin)->get(route('sitio.admin.secciones'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="datos[proyectos][__i__][imagen__archivo]"', false);
    }
}
