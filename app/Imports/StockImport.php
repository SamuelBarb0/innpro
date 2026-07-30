<?php

namespace App\Imports;

use App\Models\Cliente;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\StockProducto;
use App\Models\VarianteProducto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa cantidades de stock desde Excel.
 *
 * Columnas reconocidas:
 *   referencia        (obligatoria) — referencia del producto o SKU de la variante.
 *   cantidad          (obligatoria) — número entero de unidades disponibles.
 *   stock_minimo      (opcional)
 *   stock_maximo      (opcional)
 *   ubicacion         (opcional)
 *   modo              (opcional) — 'set' (default) reemplaza, 'sumar' suma a lo existente, 'restar' resta.
 */
class StockImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public int $exito = 0;
    public int $fallo = 0;
    public array $errores = [];

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
            $ref = trim($r['referencia'] ?? $r['ref'] ?? $r['sku'] ?? '');
            $cantidadRaw = $r['cantidad'] ?? null;
            $cantidadVacia = $cantidadRaw === null || $cantidadRaw === '';

            // Filas completamente vacías (o de hojas con otro propósito como "Instrucciones")
            // se ignoran silenciosamente — no son errores.
            if ($ref === '' && $cantidadVacia) {
                $fila++; continue;
            }

            if ($ref === '') {
                $this->fallar($fila, '', 'Referencia vacía');
                $fila++; continue;
            }
            if ($cantidadVacia || !is_numeric($cantidadRaw)) {
                $this->fallar($fila, $ref, 'Cantidad no válida');
                $fila++; continue;
            }
            $cantidad = (int) $cantidadRaw;
            if ($cantidad < 0) {
                $this->fallar($fila, $ref, 'Cantidad negativa no permitida');
                $fila++; continue;
            }

            try {
                $this->aplicar($ref, $cantidad, $r);
                $this->exito++;
            } catch (\Throwable $e) {
                Log::error('Error import stock', ['fila' => $fila, 'ref' => $ref, 'msg' => $e->getMessage()]);
                $this->fallar($fila, $ref, $e->getMessage());
            }

            $fila++;
        }
    }

    private function aplicar(string $referencia, int $cantidad, array $r): void
    {
        // Intentar primero como variante (SKU)
        $variante = VarianteProducto::where('sku', $referencia)->first();
        if ($variante) {
            $producto = $variante->producto;
            $varianteId = $variante->id;
        } else {
            $producto = $this->buscarProducto($referencia);
            if (!$producto) {
                throw new \RuntimeException($this->mensajeNoEncontrado($referencia));
            }
            $varianteId = null;
        }

        [$clienteId, $sucursalId] = $this->resolverDestino($r);

        $stock = StockProducto::firstOrNew([
            'producto_id'          => $producto->id,
            'variante_producto_id' => $varianteId,
            'cliente_id'           => $clienteId,
            'sucursal_id'          => $sucursalId,
        ]);

        $stockAnterior = (int) ($stock->cantidad_disponible ?? 0);
        $modo = strtolower(trim($r['modo'] ?? 'set'));
        $stockNuevo = match ($modo) {
            'sumar'  => $stockAnterior + $cantidad,
            'restar' => max(0, $stockAnterior - $cantidad),
            default  => $cantidad,
        };

        $stock->fill([
            'cliente_id'          => $clienteId,
            'sucursal_id'         => $sucursalId,
            'cantidad_disponible' => $stockNuevo,
            'cantidad_reservada'  => $stock->cantidad_reservada ?? 0,
            'stock_minimo'        => $r['stockminimo'] ?? $r['stock_minimo'] ?? $stock->stock_minimo ?? 0,
            'stock_maximo'        => $r['stockmaximo'] ?? $r['stock_maximo'] ?? $stock->stock_maximo,
            'ubicacion'           => $r['ubicacion'] ?? $stock->ubicacion,
            'alerta_stock_bajo'   => $stock->alerta_stock_bajo ?? true,
        ])->save();

        $diferencia = $stockNuevo - $stockAnterior;
        if ($diferencia !== 0) {
            MovimientoStock::create([
                'producto_id'          => $producto->id,
                'variante_producto_id' => $varianteId,
                'cliente_id'           => $clienteId,
                'sucursal_id'          => $sucursalId,
                'tipo_movimiento'      => $diferencia > 0 ? 'entrada' : 'salida',
                'cantidad'             => abs($diferencia),
                'stock_anterior'       => $stockAnterior,
                'stock_nuevo'          => $stockNuevo,
                'origen'               => 'otro',
                'motivo'               => 'Importación desde Excel (modo: ' . $modo . ')',
                'usuario_id'           => auth()->id() ?? 1,
            ]);
        }
    }

    /**
     * A qué bolsa de existencias va la fila: bodega general o la sede de un
     * cliente (pedido 11 de la reunión).
     *
     * Reglas, pensadas para que un Excel a medio llenar falle claro:
     *   - sin cliente ni sucursal → existencia general, como siempre
     *   - cliente y sucursal      → esa sede
     *   - solo cliente            → del cliente, sin sede concreta
     *   - solo sucursal           → error: los nombres de sede se repiten entre
     *                               clientes, así que no se puede adivinar
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function resolverDestino(array $r): array
    {
        $cliente = trim((string) ($r['cliente'] ?? ''));
        $sucursal = trim((string) ($r['sucursal'] ?? $r['sede'] ?? ''));

        if ($cliente === '' && $sucursal === '') {
            return [null, null];
        }

        if ($cliente === '') {
            throw new \RuntimeException("Se indicó la sede '{$sucursal}' pero no el cliente; sin el cliente no se sabe de qué sede se trata.");
        }

        $modelo = $this->buscarCliente($cliente);
        if (! $modelo) {
            throw new \RuntimeException("Cliente '{$cliente}' no encontrado.");
        }

        if ($sucursal === '') {
            return [$modelo->id, null];
        }

        $clave = $this->comparable($sucursal);
        $sede = $modelo->sucursales->first(
            fn ($s) => $this->comparable((string) $s->nombre) === $clave
                || $this->comparable((string) $s->ciudad) === $clave
        );

        if (! $sede) {
            $disponibles = $modelo->sucursales->pluck('nombre')->implode(', ');

            throw new \RuntimeException(
                "El cliente '{$modelo->nombre_contacto}' no tiene la sede '{$sucursal}'."
                .($disponibles !== '' ? " Tiene: {$disponibles}." : ' No tiene ninguna sede registrada.')
            );
        }

        return [$modelo->id, $sede->id];
    }

    /**
     * Cliente por empresa, contacto o NIT, con la misma tolerancia que las
     * referencias (mayúsculas, tildes y espacios).
     *
     * @var array<string,Cliente>|null
     */
    private ?array $indiceClientes = null;

    private function buscarCliente(string $texto): ?Cliente
    {
        if ($this->indiceClientes === null) {
            $this->indiceClientes = [];

            foreach (Cliente::with('sucursales')->get() as $c) {
                foreach ([$c->nombre_empresa, $c->nombre_contacto, $c->numero_identificacion] as $campo) {
                    $clave = $this->comparable((string) $campo);
                    if ($clave !== '' && ! isset($this->indiceClientes[$clave])) {
                        $this->indiceClientes[$clave] = $c;
                    }
                }
            }
        }

        return $this->indiceClientes[$this->comparable($texto)] ?? null;
    }

    /**
     * Índice de búsqueda tolerante: clave normalizada => producto.
     *
     * Se arma UNA vez por importación (no por fila) porque se recorre entero
     * para sugerir parecidos cuando algo no cuadra.
     *
     * @var array<string,Producto>|null
     */
    private ?array $indice = null;

    /**
     * Busca el producto siendo indulgente con cómo quedó escrita la referencia.
     *
     * Hace falta porque aquí la `referencia` no es un código corto: suele ser la
     * descripción completa del producto (80+ caracteres). Pedir coincidencia
     * exacta de esa cadena hacía fallar la importación por una tilde, un espacio
     * doble o el espacio duro que mete Excel al copiar y pegar.
     */
    private function buscarProducto(string $referencia): ?Producto
    {
        $exacto = Producto::where('referencia', $referencia)->first()
            ?: Producto::where('nombre', $referencia)->first();

        if ($exacto) {
            return $exacto;
        }

        return $this->indice()[$this->comparable($referencia)] ?? null;
    }

    /**
     * @return array<string,Producto>
     */
    private function indice(): array
    {
        if ($this->indice !== null) {
            return $this->indice;
        }

        $this->indice = [];

        Producto::select('id', 'referencia', 'nombre')->chunk(500, function ($productos) {
            foreach ($productos as $producto) {
                // Se indexa por referencia Y por nombre: en la práctica el
                // usuario pega cualquiera de los dos en la columna.
                foreach ([$producto->referencia, $producto->nombre] as $texto) {
                    $clave = $this->comparable((string) $texto);
                    if ($clave !== '' && ! isset($this->indice[$clave])) {
                        $this->indice[$clave] = $producto;
                    }
                }
            }
        });

        return $this->indice;
    }

    /**
     * Deja el texto en una forma comparable: sin tildes, en minúsculas y con los
     * espacios colapsados (incluido el espacio duro U+00A0 de Excel).
     */
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

    /**
     * Error con una pista: decir solo "no encontrado" sobre una descripción de
     * 80 caracteres no le sirve de nada a quien está cargando el Excel.
     */
    private function mensajeNoEncontrado(string $referencia): string
    {
        $base = "Producto/SKU '{$referencia}' no encontrado.";

        // Buscar el parecido cuesta O(catálogo) por fila fallida; con muchos
        // errores no vale la pena seguir sugiriendo.
        if (count($this->errores) >= 20) {
            return $base;
        }

        $buscado = $this->comparable($referencia);
        $mejor = null;
        $mejorPuntaje = 0.0;

        foreach ($this->indice() as $clave => $producto) {
            similar_text($buscado, $clave, $porcentaje);
            if ($porcentaje > $mejorPuntaje) {
                $mejorPuntaje = $porcentaje;
                $mejor = $producto;
            }
        }

        if ($mejor && $mejorPuntaje >= 60) {
            return $base." ¿Querías decir «{$mejor->referencia}»?";
        }

        return $base;
    }

    private function normalizar(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $clean = strtolower(trim(str_replace([' ', '-', '_'], '', $k)));
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
