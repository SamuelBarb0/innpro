<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Matriz de acceso por rol. El alcance acordado con Innpro es:
 *   admin    → todo
 *   vendedor → Productos, Listas de precios y Cotizador (con edición completa)
 *   tecnico  → Servicio Técnico
 *
 * Se prueba contra las RUTAS, que es donde vive la autorización: antes estaba
 * delegada a los controladores y módulos enteros se quedaron sin ella.
 */
class AccesoPorRolTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioCon(string $rol): User
    {
        Role::findOrCreate($rol, 'web');
        $u = User::factory()->create(['activo' => true]);
        $u->syncRoles($rol);

        return $u;
    }

    /** @dataProvider matriz */
    public function test_acceso(string $rol, string $metodo, string $ruta, bool $permitido)
    {
        $u = $this->usuarioCon($rol);
        $status = $this->actingAs($u)->call($metodo, '/'.$ruta)->getStatusCode();

        $permitido
            ? $this->assertNotSame(403, $status, "$rol debería entrar a $metodo /$ruta")
            : $this->assertSame(403, $status, "$rol NO debería entrar a $metodo /$ruta (dio $status)");
    }

    public static function matriz(): array
    {
        $casos = [];

        // [ruta, método, roles permitidos]
        $reglas = [
            ['usuarios',        'GET',  ['admin']],
            ['usuarios/guardar','POST', ['admin']],
            ['clientes',        'GET',  ['admin']],
            ['categorias',      'GET',  ['admin']],
            ['stock',           'GET',  ['admin']],
            ['empresa',         'GET',  ['admin']],
            ['sitio/ajustes',   'GET',  ['admin']],
            ['solicitudes',     'GET',  ['admin']],

            ['productos',                 'GET',  ['admin', 'vendedor']],
            ['productos/form',            'GET',  ['admin', 'vendedor']],
            ['productos/importar',        'GET',  ['admin', 'vendedor']],
            ['productos/historial-precios','GET', ['admin', 'vendedor']],
            ['listas-precios',            'GET',  ['admin', 'vendedor']],
            ['listas-precios/guardar',    'POST', ['admin', 'vendedor']],
            ['catalogo',                  'GET',  ['admin', 'vendedor']],

            ['servicio',       'GET',  ['admin', 'tecnico']],
            ['servicio/form',  'GET',  ['admin', 'tecnico']],
        ];

        foreach ($reglas as [$ruta, $metodo, $permitidos]) {
            foreach (['admin', 'vendedor', 'tecnico'] as $rol) {
                $casos["$rol $metodo /$ruta"] = [$rol, $metodo, $ruta, in_array($rol, $permitidos, true)];
            }
        }

        return $casos;
    }
}
