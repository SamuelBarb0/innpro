<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuPorRolTest extends TestCase
{
    use RefreshDatabase;

    private function menuDe(string $rol): array
    {
        Role::findOrCreate($rol, 'web');
        $u = User::factory()->create(['activo' => true]);
        $u->syncRoles($rol);

        $html = $this->actingAs($u)->get('/dashboard')->getContent();

        $secciones = [
            'Usuarios', 'Clientes', 'Productos', 'Listas de precios',
            'Sitio web', 'Cotizador', 'Solicitudes', 'Servicio Técnico',
        ];

        return array_values(array_filter(
            $secciones,
            fn ($s) => str_contains($html, '<span>'.$s.'</span>')
        ));
    }

    public function test_el_vendedor_solo_ve_productos_listas_y_cotizador()
    {
        $this->assertSame(
            ['Productos', 'Listas de precios', 'Cotizador'],
            $this->menuDe('vendedor')
        );
    }

    public function test_el_tecnico_solo_ve_servicio_tecnico()
    {
        $this->assertSame(['Servicio Técnico'], $this->menuDe('tecnico'));
    }

    public function test_el_admin_las_ve_todas()
    {
        $this->assertSame(
            ['Usuarios', 'Clientes', 'Productos', 'Listas de precios',
             'Sitio web', 'Cotizador', 'Solicitudes', 'Servicio Técnico'],
            $this->menuDe('admin')
        );
    }
}
