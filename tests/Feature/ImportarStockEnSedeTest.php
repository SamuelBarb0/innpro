<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClienteSucursal;
use App\Models\ListaPrecio;
use App\Models\Producto;
use App\Models\StockProducto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cargar una remisión desde la pantalla de la sede.
 *
 * El importador ya aceptaba columnas de cliente y sucursal, pero vivía en el
 * módulo de stock general, que Innpro pidió ocultar del menú: la función
 * existía y no había forma de llegar a ella.
 */
class ImportarStockEnSedeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Cliente $cliente;

    private ClienteSucursal $popayan;

    private ClienteSucursal $cali;

    private Producto $camara;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // La tabla exige vendedor y lista de precios: todo cliente cuelga de
        // alguien que lo atiende y cotiza con alguna lista.
        $lista = ListaPrecio::firstOrCreate(['codigo' => 'general'], ['nombre' => 'General', 'activo' => true]);

        $this->cliente = Cliente::create([
            'nombre_empresa' => 'Constructora Andina S.A.S.',
            'nombre_contacto' => 'Jorge Herrera',
            'numero_identificacion' => '900123456-1',
            'pais' => 'Colombia',
            'vendedor_id' => $this->admin->id,
            'lista_precio_id' => $lista->id,
            'activo' => true,
        ]);

        $this->popayan = ClienteSucursal::create([
            'cliente_id' => $this->cliente->id, 'nombre' => 'Obra Popayán', 'ciudad' => 'Popayán', 'activo' => true,
        ]);

        $this->cali = ClienteSucursal::create([
            'cliente_id' => $this->cliente->id, 'nombre' => 'Obra Cali', 'ciudad' => 'Cali', 'activo' => true,
        ]);

        $categoria = Categoria::firstOrCreate(['nombre' => 'Seguridad'], ['activo' => true]);

        $this->camara = Producto::create([
            'referencia' => 'CÁMARA BULLET IP 4MP',
            'nombre' => 'Cámara bullet',
            'unidad_venta' => 'Unidad',
            'unidad_empaque' => 'Caja',
            'categoria_id' => $categoria->id,
            'activo' => true,
        ]);
    }

    /** Un CSV con las columnas indicadas. El importador acepta csv y xlsx. */
    private function archivo(array $cabeceras, array $filas): UploadedFile
    {
        $lineas = [implode(';', $cabeceras)];
        foreach ($filas as $f) {
            $lineas[] = implode(';', $f);
        }

        $ruta = tempnam(sys_get_temp_dir(), 'remision').'.csv';
        file_put_contents($ruta, implode("\n", $lineas));

        return new UploadedFile($ruta, 'remision.csv', 'text/csv', null, true);
    }

    private function subir(ClienteSucursal $sede, UploadedFile $archivo)
    {
        return $this->actingAs($this->admin)->post(
            route('clientes.sucursales.stock.importar', [$this->cliente->id, $sede->id]),
            ['archivo' => $archivo],
        );
    }

    /**
     * Lo que hace útil la pantalla: el Excel solo lleva referencia y cantidad,
     * porque la sede la pone el sitio desde el que se sube.
     */
    public function test_sin_columnas_de_cliente_la_remision_entra_en_la_sede_de_la_pantalla(): void
    {
        $this->subir($this->popayan, $this->archivo(
            ['referencia', 'cantidad', 'modo'],
            [['CÁMARA BULLET IP 4MP', 12, 'sumar']],
        ))->assertSessionHas('success');

        $stock = StockProducto::where('sucursal_id', $this->popayan->id)->first();

        $this->assertNotNull($stock);
        $this->assertSame(12, (int) $stock->cantidad_disponible);
        $this->assertSame($this->cliente->id, $stock->cliente_id);
    }

    /**
     * Que la mitad de una remisión acabe en otra obra porque el Excel traía
     * algo escrito es el error caro de este módulo, y sería invisible: las
     * cantidades cuadran, solo están donde no son.
     */
    public function test_una_fila_que_apunta_a_otra_sede_se_rechaza(): void
    {
        $this->subir($this->popayan, $this->archivo(
            ['referencia', 'cantidad', 'modo', 'cliente', 'sucursal'],
            [['CÁMARA BULLET IP 4MP', 5, 'sumar', 'Constructora Andina S.A.S.', 'Obra Cali']],
        ))->assertSessionHas('error');

        $this->assertSame(0, StockProducto::where('sucursal_id', $this->cali->id)->count());
        $this->assertSame(0, StockProducto::where('sucursal_id', $this->popayan->id)->count());
    }

    /** Nombrar la MISMA sede es redundante, pero no es un error. */
    public function test_repetir_la_sede_correcta_se_acepta(): void
    {
        $this->subir($this->popayan, $this->archivo(
            ['referencia', 'cantidad', 'modo', 'cliente', 'sucursal'],
            [['CÁMARA BULLET IP 4MP', 7, 'sumar', 'Constructora Andina S.A.S.', 'Obra Popayán']],
        ))->assertSessionHas('success');

        $this->assertSame(7, (int) StockProducto::where('sucursal_id', $this->popayan->id)->value('cantidad_disponible'));
    }

    /** La existencia de la sede no toca la de bodega general. */
    public function test_la_bodega_general_no_se_ve_afectada(): void
    {
        StockProducto::create([
            'producto_id' => $this->camara->id,
            'cliente_id' => null,
            'sucursal_id' => null,
            'cantidad_disponible' => 30,
        ]);

        $this->subir($this->popayan, $this->archivo(
            ['referencia', 'cantidad', 'modo'],
            [['CÁMARA BULLET IP 4MP', 4, 'sumar']],
        ))->assertSessionHas('success');

        $general = StockProducto::whereNull('sucursal_id')->whereNull('cliente_id')->first();
        $this->assertSame(30, (int) $general->cantidad_disponible);
    }

    public function test_no_se_puede_cargar_en_la_sede_de_otro_cliente(): void
    {
        $otro = Cliente::create([
            'nombre_empresa' => 'Otra empresa', 'nombre_contacto' => 'Otro',
            'numero_identificacion' => '999999999-9', 'pais' => 'Colombia',
            'vendedor_id' => $this->admin->id,
            'lista_precio_id' => ListaPrecio::first()->id, 'activo' => true,
        ]);

        $this->actingAs($this->admin)->post(
            route('clientes.sucursales.stock.importar', [$otro->id, $this->popayan->id]),
            ['archivo' => $this->archivo(['referencia', 'cantidad'], [['CÁMARA BULLET IP 4MP', 3]])],
        )->assertNotFound();
    }

    public function test_la_plantilla_se_descarga(): void
    {
        $this->actingAs($this->admin)
            ->get(route('clientes.sucursales.stock.plantilla', [$this->cliente->id, $this->popayan->id]))
            ->assertOk();
    }
}
