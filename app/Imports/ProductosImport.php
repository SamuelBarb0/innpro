<?php

namespace App\Imports;

use App\Models\ActualizacionPrecio;
use App\Models\ListaPrecio;
use App\Models\PrecioProducto;
use App\Models\PrecioVariante;
use App\Models\Producto;
use App\Models\VarianteProducto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Importa productos y precios desde la plantilla del cliente.
 *
 * Encabezados (la columna `referencia` es la única obligatoria):
 *   referencia       — código del producto (obligatorio)
 *   nombre           — nombre del producto (si no viene, usa descripción o referencia)
 *   descripcion      — descripción larga del producto
 *   unidad_venta     — unidad de venta (default: UND)
 *   unidad_empaque   — unidad de empaque (default: UND)
 *   color_o_motivo   — si trae valor, esa fila es una variante del producto
 *   <codigo_lista>   — una columna por cada lista de precios activa, usando su `codigo`
 *                      (ej. export1, export2, local1, local2, local3, local4, …)
 *
 * Reglas:
 *   - Las listas de precios se descubren dinámicamente desde BD por su `codigo`.
 *     Si mañana se agrega una "local5", basta con incluirla en BD; la plantilla
 *     automáticamente reconocerá la columna.
 *   - Si la referencia ya existe, se actualizan los campos que vengan llenos.
 *   - Si no existe, se crea con nombre/descripción del Excel (con fallback a
 *     referencia) y SIN categoría: Innpro no clasifica por categoría.
 *   - Si la misma referencia aparece varias veces con distinto "Color o motivo",
 *     se interpretan como variantes del mismo producto.
 */
class ProductosImport implements WithMultipleSheets, WithCustomCsvSettings
{
    protected ActualizacionPrecio $actualizacion;

    public function __construct(ActualizacionPrecio $actualizacion)
    {
        $this->actualizacion = $actualizacion;
    }

    public function sheets(): array
    {
        // Solo procesamos la primera hoja. La hoja "Instrucciones" (índice 1) se ignora.
        return [
            0 => new ProductosImportSheet($this->actualizacion),
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter'        => ';',
            'enclosure'        => '"',
            'escape_character' => '\\',
            'contiguous'       => false,
            'input_encoding'   => 'UTF-8',
        ];
    }
}

class ProductosImportSheet implements ToCollection, WithHeadingRow
{
    protected ActualizacionPrecio $actualizacion;

    /** @var array<string,int>  codigo de lista (normalizado) => id de la lista */
    protected array $listasPorCodigo = [];

    /** @var array<string,string>  codigo de lista normalizado => nombre legible */
    protected array $nombresListas = [];

    public function __construct(ActualizacionPrecio $actualizacion)
    {
        $this->actualizacion = $actualizacion;

        // Descubrir listas activas dinámicamente. La clave es el código normalizado
        // (lowercase, sin espacios/guiones/underscores) que es como llegan los headers.
        foreach (ListaPrecio::activas()->get(['id', 'codigo', 'nombre']) as $lista) {
            $key = $this->normalizarClave($lista->codigo);
            $this->listasPorCodigo[$key] = $lista->id;
            $this->nombresListas[$key]   = $lista->nombre;
        }
    }

    public function collection(Collection $rows)
    {
        // Agrupamos filas por referencia para soportar productos con variantes
        // (misma referencia repetida con distinto "color o motivo").
        $grupos = [];
        $filaNumero = 2;
        foreach ($rows as $row) {
            $r = $this->normalizar($row->toArray());
            $ref = trim((string) ($r['referencia'] ?? $r['ref'] ?? $r['codigo'] ?? $r['sku'] ?? ''));

            // Ignoramos silenciosamente filas vacías o de hojas distintas (instrucciones, etc.)
            if ($ref === '' && $this->filaSinDatosUtiles($r)) {
                $filaNumero++;
                continue;
            }

            if ($ref === '') {
                $this->actualizacion->agregarError($filaNumero, '', 'Referencia vacía');
                $filaNumero++;
                continue;
            }

            $grupos[$ref][] = ['fila' => $filaNumero, 'datos' => $r];
            $filaNumero++;
        }

        $exito = 0;
        $fallo = 0;
        foreach ($grupos as $referencia => $filas) {
            try {
                $resultado = $this->procesarGrupo((string) $referencia, $filas);
                $exito += $resultado['exito'];
                $fallo += $resultado['fallo'];
            } catch (\Throwable $e) {
                Log::error('Error procesando grupo en import productos', [
                    'referencia' => $referencia,
                    'msg'        => $e->getMessage(),
                ]);
                foreach ($filas as $f) {
                    $this->actualizacion->agregarError($f['fila'], (string) $referencia, $e->getMessage());
                    $fallo++;
                }
            }
        }

        $this->actualizacion->update([
            'total_filas'              => $exito + $fallo,
            'actualizaciones_exitosas' => $exito,
            'actualizaciones_fallidas' => $fallo,
            'errores'                  => $this->actualizacion->errores,
            'detalles_procesados'      => $this->actualizacion->detalles_procesados,
            'estado'                   => 'completado',
        ]);
    }

    /**
     * Procesa todas las filas de una misma referencia.
     * @return array{exito:int,fallo:int}
     */
    private function procesarGrupo(string $referencia, array $filas): array
    {
        // ¿Hay alguna fila con "color o motivo"? Si sí, este es un producto con variantes.
        $tieneVariantes = false;
        foreach ($filas as $f) {
            $color = trim((string) ($f['datos']['coloromotivo'] ?? $f['datos']['colorymotivo'] ?? $f['datos']['colormotivo'] ?? $f['datos']['color'] ?? ''));
            if ($color !== '') {
                $tieneVariantes = true;
                break;
            }
        }
        // Caso especial: misma referencia repetida sin color → también tratamos como variantes
        if (! $tieneVariantes && count($filas) > 1) {
            $tieneVariantes = true;
        }

        // Primera fila define el producto base
        $primera = $filas[0];
        $producto = $this->upsertProducto($referencia, $primera['datos'], $tieneVariantes);

        $exito = 0;
        $fallo = 0;

        if (! $tieneVariantes) {
            // Producto simple: aplicar precios al producto
            $algo = $this->upsertPreciosProducto($producto, $primera['datos'], $primera['fila'], $referencia);
            if ($producto->wasRecentlyCreated || $algo) {
                $exito++;
            } else {
                $this->actualizacion->agregarError($primera['fila'], $referencia, 'Sin cambios aplicables.');
                $fallo++;
            }
            return ['exito' => $exito, 'fallo' => $fallo];
        }

        // Producto con variantes: la primera fila setea los precios base del producto;
        // las filas siguientes son las variantes adicionales con eventual ajuste de precio.
        $this->upsertPreciosProducto($producto, $primera['datos'], $primera['fila'], $referencia);
        $preciosBase = $this->extraerPreciosFila($primera['datos']);

        foreach ($filas as $f) {
            $color = trim((string) ($f['datos']['coloromotivo'] ?? $f['datos']['colorymotivo'] ?? $f['datos']['colormotivo'] ?? $f['datos']['color'] ?? ''));
            // Si la primera fila no traía color pero las siguientes sí, esta primera fila
            // ya quedó capturada como precio base. Solo creamos variantes para filas con color.
            if ($color === '') {
                $exito++;
                continue;
            }

            $variante = $this->upsertVariante($producto, $color);
            $this->upsertAjustesVariante($variante, $f['datos'], $preciosBase, $f['fila'], $referencia);
            $exito++;
        }

        return ['exito' => $exito, 'fallo' => $fallo];
    }

    private function upsertProducto(string $referencia, array $r, bool $tieneVariantes): Producto
    {
        $nombre        = trim((string) ($r['nombre'] ?? ''));
        $descripcion   = trim((string) ($r['descripcion'] ?? ''));
        $unidadVenta   = trim((string) ($r['unidadventa'] ?? '')) ?: 'UND';
        $unidadEmpaque = trim((string) ($r['unidadempaque'] ?? '')) ?: 'UND';
        $pesoPaca      = $this->numeroONull($r['pesopaca'] ?? null);
        $cubicajePaca  = $this->numeroONull($r['cubicajepaca'] ?? null);
        $unidadesPaca  = $this->numeroONull($r['unidadesporpaca'] ?? null);
        $codigoBarras  = trim((string) ($r['codigobarras'] ?? ''));

        // Buscamos incluyendo soft-deleted para no romper el UNIQUE de referencia.
        // Si existe pero está borrado, lo restauramos al importar.
        $producto = Producto::withTrashed()->where('referencia', $referencia)->first();
        if ($producto && method_exists($producto, 'trashed') && $producto->trashed()) {
            $producto->restore();
        }

        if (! $producto) {
            // Fallback de nombre: usa nombre del Excel; si no viene, usa la descripción;
            // si tampoco hay descripción, usa la referencia como último recurso.
            $nombreFinal = $nombre !== ''
                ? $nombre
                : ($descripcion !== '' ? $descripcion : $referencia);

            $producto = Producto::create([
                'referencia'      => $referencia,
                'nombre'          => $nombreFinal,
                'descripcion'     => $descripcion !== '' ? $descripcion : null,
                'unidad_venta'    => $unidadVenta,
                'unidad_empaque'  => $unidadEmpaque,
                'peso_paca'         => $pesoPaca,
                'cubicaje_paca'     => $cubicajePaca,
                'unidades_por_paca' => $unidadesPaca,
                'codigo_barras'     => $codigoBarras !== '' ? $codigoBarras : null,
                'tiene_extension' => $tieneVariantes,
                'tiene_variantes' => $tieneVariantes,
                'controlar_stock' => true,
                // Innpro no clasifica por categoría: el producto nace sin ella y
                // se asigna a mano desde Productos si algún día hace falta. Antes
                // el importador creaba una categoría por cada nombre distinto del
                // Excel y reasignaba las de los productos que ya existían, y eso
                // dejaba una lista que nadie podía mantener.
                'categoria_id'    => null,
                'activo'          => true,
            ]);
            return $producto;
        }

        // Producto existente: actualizar solo lo que venga lleno
        $cambios = [];
        if ($nombre !== '') {
            $cambios['nombre'] = $nombre;
        }
        if ($descripcion !== '') {
            $cambios['descripcion'] = $descripcion;
        }
        if (! empty($r['unidadventa'])) {
            $cambios['unidad_venta'] = $unidadVenta;
        }
        if (! empty($r['unidadempaque'])) {
            $cambios['unidad_empaque'] = $unidadEmpaque;
        }
        if ($pesoPaca !== null) {
            $cambios['peso_paca'] = $pesoPaca;
        }
        if ($cubicajePaca !== null) {
            $cambios['cubicaje_paca'] = $cubicajePaca;
        }
        if ($unidadesPaca !== null) {
            $cambios['unidades_por_paca'] = $unidadesPaca;
        }
        if ($codigoBarras !== '') {
            $cambios['codigo_barras'] = $codigoBarras;
        }
        if ($tieneVariantes && ! $producto->tiene_variantes) {
            $cambios['tiene_variantes'] = true;
            $cambios['tiene_extension'] = true;
        }
        if (! empty($cambios)) {
            $producto->update($cambios);
        }

        return $producto;
    }

    /**
     * Normaliza un valor de celda a float >= 0 o null si viene vacío/!numérico.
     * Acepta coma o punto decimal (ej. "12,5" o "12.5").
     */
    private function numeroONull($valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $valor = str_replace(',', '.', trim((string) $valor));
        if (! is_numeric($valor)) {
            return null;
        }
        $num = (float) $valor;
        return $num < 0 ? null : $num;
    }

    private function upsertPreciosProducto(Producto $producto, array $r, int $fila, string $referencia): bool
    {
        $algo = false;
        foreach ($this->listasPorCodigo as $key => $listaId) {
            $val = $r[$key] ?? null;
            if ($val === null || $val === '' || ! is_numeric($val)) {
                continue;
            }
            $precio = (float) $val;
            if ($precio < 0) {
                $this->actualizacion->agregarError($fila, $referencia, "Precio negativo en {$key}: {$precio}");
                continue;
            }

            $anterior = PrecioProducto::where('producto_id', $producto->id)
                ->where('lista_precio_id', $listaId)
                ->value('precio');

            PrecioProducto::updateOrCreate(
                ['producto_id' => $producto->id, 'lista_precio_id' => $listaId],
                ['precio' => $precio, 'activo' => true]
            );

            $this->actualizacion->agregarProcesado(
                $fila,
                $referencia,
                $this->nombresListas[$key] ?? $key,
                $anterior,
                $precio
            );
            $algo = true;
        }
        return $algo;
    }

    private function upsertVariante(Producto $producto, string $color): VarianteProducto
    {
        $sku = $producto->referencia . '-' . Str::slug($color);
        return VarianteProducto::firstOrCreate(
            ['producto_id' => $producto->id, 'sku' => $sku],
            ['extension' => $color, 'activo' => true]
        );
    }

    /**
     * @return array<string,float>  codigo lista normalizado => precio
     */
    private function extraerPreciosFila(array $r): array
    {
        $out = [];
        foreach ($this->listasPorCodigo as $key => $_) {
            $val = $r[$key] ?? null;
            if ($val !== null && $val !== '' && is_numeric($val)) {
                $out[$key] = (float) $val;
            }
        }
        return $out;
    }

    private function upsertAjustesVariante(VarianteProducto $variante, array $r, array $preciosBase, int $fila, string $referencia): void
    {
        foreach ($this->listasPorCodigo as $key => $listaId) {
            $val = $r[$key] ?? null;
            if ($val === null || $val === '' || ! is_numeric($val)) {
                continue;
            }
            $precio = (float) $val;
            $base = $preciosBase[$key] ?? null;

            if ($base === null) {
                // No hay precio base en la primera fila → no podemos calcular ajuste.
                // Guardamos el ajuste como el precio absoluto y dejamos rastro.
                $ajuste = $precio;
            } else {
                $ajuste = round($precio - $base, 2);
            }

            if ($ajuste == 0.0) {
                // Sin diferencia respecto al producto base → no creamos registro.
                continue;
            }

            PrecioVariante::updateOrCreate(
                ['variante_producto_id' => $variante->id, 'lista_precio_id' => $listaId],
                ['ajuste_precio' => $ajuste, 'activo' => true]
            );

            $this->actualizacion->agregarProcesado(
                $fila,
                $referencia . ' (' . $variante->extension . ')',
                ($this->nombresListas[$key] ?? $key) . ' [ajuste]',
                null,
                $ajuste
            );
        }
    }

    private function normalizar(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $clean = $this->normalizarClave((string) $k);
            $out[$clean] = is_string($v) ? trim($v) : $v;
        }
        return $out;
    }

    private function normalizarClave(string $s): string
    {
        // lowercase, sin espacios/guiones/underscores, sin tildes
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
        return str_replace([' ', '-', '_'], '', $s);
    }

    private function filaSinDatosUtiles(array $r): bool
    {
        // Una fila "sin datos útiles" no tiene precios ni texto reconocible.
        foreach ($this->listasPorCodigo as $key => $_) {
            if (! empty($r[$key])) return false;
        }
        return empty($r['nombre'])
            && empty($r['descripcion'])
            && empty($r['unidadventa'])
            && empty($r['unidadempaque']);
    }
}
