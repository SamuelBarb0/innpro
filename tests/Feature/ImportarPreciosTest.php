<?php

namespace Tests\Feature;

use App\Imports\PreciosListaImport;
use App\Models\ListaPrecio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Lectura de importes del importador de precios.
 *
 * En Colombia el punto es separador de MILES, así que interpretarlo como
 * decimal divide el precio por mil. Y el error no se ve al importar —la fila
 * entra sin problema—, sino cuando alguien factura con un precio absurdo.
 */
class ImportarPreciosTest extends TestCase
{
    use RefreshDatabase;

    private function leer(mixed $valor): ?float
    {
        $lista = ListaPrecio::firstOrCreate(
            ['codigo' => 'prueba'],
            ['nombre' => 'Prueba', 'activo' => true, 'orden' => 99],
        );

        $metodo = new ReflectionMethod(PreciosListaImport::class, 'aNumero');
        $metodo->setAccessible(true);

        return $metodo->invoke(new PreciosListaImport($lista), $valor);
    }

    /**
     * El caso que se escapó y llegó a producción: `is_numeric('345.600')` es
     * TRUE, así que la cadena entraba como 345,6 sin pasar por la heurística de
     * miles. Se pasó por alto porque los casos que se probaron tenían DOS
     * grupos ('1.250.000'), que no son numéricos y sí llegaban a la heurística.
     *
     * @dataProvider miles
     */
    public function test_el_punto_de_miles_no_se_lee_como_decimal(string $escrito, float $esperado): void
    {
        $this->assertSame($esperado, $this->leer($escrito), "«{$escrito}» se leyó mal.");
    }

    public static function miles(): array
    {
        return [
            'un grupo, el que fallaba' => ['345.600', 345600.0],
            'un grupo redondo' => ['640.000', 640000.0],
            'seis cifras justas' => ['185.000', 185000.0],
            'dos grupos' => ['1.250.000', 1250000.0],
            'con signo de peso' => ['$ 1.250.000', 1250000.0],
            'miles con coma' => ['1,250,000', 1250000.0],
            'sin separadores' => ['320000', 320000.0],
        ];
    }

    /**
     * Y la otra mitad: una coma o un punto que SÍ es decimal —uno o dos
     * dígitos detrás— tiene que seguir leyéndose como decimal.
     *
     * @dataProvider decimales
     */
    public function test_los_decimales_de_verdad_se_respetan(string $escrito, float $esperado): void
    {
        $this->assertSame($esperado, $this->leer($escrito), "«{$escrito}» se leyó mal.");
    }

    public static function decimales(): array
    {
        return [
            'decimal con coma' => ['1.250.000,75', 1250000.75],
            'decimal con punto' => ['1250000.50', 1250000.5],
            'un solo decimal' => ['1500,5', 1500.5],
        ];
    }

    /** Una celda con formato de número llega ya resuelta: no hay que tocarla. */
    public function test_los_numeros_de_verdad_pasan_tal_cual(): void
    {
        $this->assertSame(345600.0, $this->leer(345600));
        $this->assertSame(345600.5, $this->leer(345600.5));
    }

    public function test_lo_que_no_es_un_importe_devuelve_nulo(): void
    {
        $this->assertNull($this->leer('abc'));
        $this->assertNull($this->leer(''));
        $this->assertNull($this->leer(null));
    }
}
