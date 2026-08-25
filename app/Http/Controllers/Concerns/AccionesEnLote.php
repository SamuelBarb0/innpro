<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Acciones sobre varias filas de un DataTable a la vez.
 *
 * Cada módulo decide qué acciones ofrece y qué filas puede tocar; esto solo
 * pone en común la validación de la petición, la forma de la respuesta y el
 * "seleccionar todos los que coinciden con el filtro".
 */
trait AccionesEnLote
{
    /** Tope de filas por petición, para que un clic no dispare algo ilimitado. */
    private const MAXIMO_POR_LOTE = 20000;

    /**
     * Valida la petición de lote.
     *
     * @param  array<int,string>  $accionesPermitidas
     * @return array{0:string,1:array<int,int>}  [accion, ids]
     */
    protected function datosDelLote(Request $request, array $accionesPermitidas): array
    {
        $datos = $request->validate([
            'accion' => ['required', 'string', 'in:' . implode(',', $accionesPermitidas)],
            'ids'    => ['required', 'array', 'min:1', 'max:' . self::MAXIMO_POR_LOTE],
            'ids.*'  => ['integer'],
        ], [
            'accion.in'  => 'Esa acción no está disponible aquí.',
            'ids.required' => 'No hay ninguna fila seleccionada.',
            'ids.max'    => 'Son demasiadas filas para una sola operación (máximo ' . self::MAXIMO_POR_LOTE . ').',
        ]);

        // unique() evita contar dos veces si el front mandó repetidos.
        return [$datos['accion'], array_values(array_unique(array_map('intval', $datos['ids'])))];
    }

    /**
     * Los ids que coinciden con los filtros que el usuario tiene puestos ahora
     * mismo en la tabla.
     *
     * Se reusa el propio motor de DataTables (misma búsqueda, mismo orden) en vez
     * de reescribir los filtros a mano: si divergieran, el usuario podría borrar
     * filas distintas de las que está viendo. Con `length = -1` DataTables se
     * salta la paginación y devuelve todas las coincidencias.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    protected function idsDelFiltro(Request $request, $query, string $columna = 'id'): JsonResponse
    {
        $request->merge(['start' => 0, 'length' => -1]);

        $ids = DataTables::of($query)
            ->getFilteredQuery()
            ->pluck($columna)
            ->map(fn ($id) => (int) $id)
            ->all();

        return response()->json([
            'ids'   => $ids,
            'total' => count($ids),
        ]);
    }

    /**
     * Respuesta uniforme. `$omitidos` son los motivos por los que alguna fila no
     * se pudo tocar, para poder decirlo en vez de fingir que se hizo todo.
     *
     * @param  array<string,int>  $omitidos  motivo => cuántas filas
     */
    protected function respuestaDelLote(string $accion, int $aplicados, array $omitidos = []): JsonResponse
    {
        $verbo = [
            'activar'    => 'activada(s)',
            'desactivar' => 'desactivada(s)',
            'eliminar'   => 'eliminada(s)',
        ][$accion] ?? 'procesada(s)';

        $mensaje = $aplicados > 0
            ? "{$aplicados} fila(s) {$verbo}."
            : 'No se aplicó ningún cambio.';

        foreach ($omitidos as $motivo => $cuantas) {
            $mensaje .= " {$cuantas} omitida(s): {$motivo}.";
        }

        return response()->json([
            'ok'        => $aplicados > 0,
            'aplicados' => $aplicados,
            'omitidos'  => array_sum($omitidos),
            'mensaje'   => $mensaje,
        ]);
    }
}
