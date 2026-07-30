<?php

namespace App\Imports;

use App\Models\ListaPrecio;
use App\Models\PrecioProducto;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Carga los precios de UNA lista desde un Excel de dos columnas.
 *
 * Distinto del `PreciosImport` de siempre, que trae una columna por cada
 * código de lista y va atado al flujo de "actualización de precios". Aquí el
 * archivo es de una sola lista, que es lo que pidió Jorge para poder darle su
 * propia lista a cada cliente sin pelearse con un Excel de muchas columnas.
 *
 * Columnas: `referencia` (o el nombre del producto) y `precio`.
 */
class PreciosListaImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public int $exito = 0;

    public int $fallo = 0;

    /** @var array<int,array{fila:int,referencia:string,mensaje:string}> */
    public array $errores = [];

    /** @var array<string,Producto>|null */
    private ?array $indice = null;

    public function __construct(private readonly ListaPrecio $lista)
    {
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'enclosure' => '"',
            'escape_character' => '\\',
            'contiguous' => false,
            'input_encoding' => 'UTF-8',
        ];
    }

    public function collection(Collection $rows)
    {
        $fila = 2;

        foreach ($rows as $row) {
            $r = $this->normalizar($row->toArray());
            $ref = trim((string) ($r['referencia'] ?? $r['ref'] ?? $r['producto'] ?? ''));
            $precioBruto = $r['precio'] ?? null;

            // Filas vacías y hojas de instrucciones: se ignoran sin ruido.
            if ($ref === '' && ($precioBruto === null || $precioBruto === '')) {
                $fila++;

                continue;
            }

            if ($ref === '') {
                $this->fallar($fila, '', 'Referencia vacía');
                $fila++;

                continue;
            }

            $precio = $this->aNumero($precioBruto);

            if ($precio === null) {
                $this->fallar($fila, $ref, 'Precio no válido');
                $fila++;

                continue;
            }

            if ($precio < 0) {
                $this->fallar($fila, $ref, 'El precio no puede ser negativo');
                $fila++;

                continue;
            }

            $producto = $this->buscarProducto($ref);

            if (! $producto) {
                $this->fallar($fila, $ref, "Producto '{$ref}' no encontrado.");
                $fila++;

                continue;
            }

            PrecioProducto::updateOrCreate(
                ['producto_id' => $producto->id, 'lista_precio_id' => $this->lista->id],
                ['precio' => $precio, 'activo' => true]
            );

            $this->exito++;
            $fila++;
        }
    }

    /**
     * Acepta "1.250.000", "1250000,50" y "$ 1.250.000".
     *
     * Hace falta porque el Excel viene escrito a mano y en Colombia el punto es
     * separador de miles: leerlo como decimal convertiría 1.250.000 en 1,25.
     */
    private function aNumero(mixed $valor): ?float
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        if (! is_string($valor)) {
            return null;
        }

        $limpio = trim(str_replace(['$', ' ', "\xC2\xA0"], '', $valor));

        if ($limpio === '') {
            return null;
        }

        // La última coma o punto manda como separador decimal solo si deja 1 o
        // 2 dígitos detrás; si no, todo son separadores de miles.
        $ultimaComa = strrpos($limpio, ',');
        $ultimoPunto = strrpos($limpio, '.');
        $corte = max($ultimaComa === false ? -1 : $ultimaComa, $ultimoPunto === false ? -1 : $ultimoPunto);

        if ($corte >= 0) {
            $decimales = strlen($limpio) - $corte - 1;
            if ($decimales >= 1 && $decimales <= 2) {
                $entero = preg_replace('/\D/', '', substr($limpio, 0, $corte));
                $resto = preg_replace('/\D/', '', substr($limpio, $corte + 1));

                return (float) ($entero.'.'.$resto);
            }
        }

        $soloDigitos = preg_replace('/\D/', '', $limpio);

        return $soloDigitos === '' ? null : (float) $soloDigitos;
    }

    /** Búsqueda tolerante, igual que en el importador de stock. */
    private function buscarProducto(string $referencia): ?Producto
    {
        $exacto = Producto::where('referencia', $referencia)->first()
            ?: Producto::where('nombre', $referencia)->first();

        if ($exacto) {
            return $exacto;
        }

        if ($this->indice === null) {
            $this->indice = [];

            Producto::select('id', 'referencia', 'nombre')->chunk(500, function ($productos) {
                foreach ($productos as $producto) {
                    foreach ([$producto->referencia, $producto->nombre] as $texto) {
                        $clave = $this->comparable((string) $texto);
                        if ($clave !== '' && ! isset($this->indice[$clave])) {
                            $this->indice[$clave] = $producto;
                        }
                    }
                }
            });
        }

        return $this->indice[$this->comparable($referencia)] ?? null;
    }

    private function comparable(string $texto): string
    {
        $t = str_replace("\xC2\xA0", ' ', $texto);
        $t = mb_strtolower(trim($t), 'UTF-8');
        $t = strtr($t, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
            'ò' => 'o', 'ù' => 'u',
        ]);

        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    private function normalizar(array $row): array
    {
        $out = [];

        foreach ($row as $k => $v) {
            $clean = strtolower(trim(str_replace([' ', '-', '_'], '', (string) $k)));
            $out[$clean] = is_string($v) ? trim($v) : $v;
        }

        return $out;
    }

    private function fallar(int $fila, string $ref, string $msg): void
    {
        $this->fallo++;
        $this->errores[] = ['fila' => $fila, 'referencia' => $ref, 'mensaje' => $msg];
    }
}
