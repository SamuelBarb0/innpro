<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una sección de una página. Ver la migración para el porqué del diseño.
 */
class SitioBloque extends Model
{
    protected $table = 'sitio_bloques';

    protected $fillable = [
        'pagina_id', 'clave', 'tipo', 'antetitulo', 'titulo', 'subtitulo',
        'texto', 'imagen', 'datos', 'orden', 'activo',
    ];

    protected $casts = [
        'datos' => 'array',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function pagina(): BelongsTo
    {
        return $this->belongsTo(SitioPagina::class, 'pagina_id');
    }

    /**
     * Una lista de `datos`, siempre como array.
     *
     * La plantilla recorre esto directamente, así que un `datos` mal guardado
     * (null, un texto suelto, un objeto donde se esperaba lista) no puede
     * reventar la portada pública: se devuelve vacío y la sección simplemente
     * se pinta sin sus tarjetas.
     */
    public function lista(string $clave): array
    {
        $valor = $this->datos[$clave] ?? null;

        return is_array($valor) ? array_values(array_filter($valor, 'is_array')) : [];
    }

    /** Un valor suelto de `datos`, con respaldo. */
    public function dato(string $clave, $default = null)
    {
        $valor = $this->datos[$clave] ?? null;

        return (is_array($valor) || $valor === null || $valor === '') ? $default : $valor;
    }

    /**
     * Un grupo de campos de `datos` —un botón, por ejemplo, que son texto y
     * URL juntos—, siempre como array.
     *
     * Existe porque `dato()` descarta los arrays a propósito (para no devolver
     * una lista donde se espera un texto), y usarlo para los botones los hacía
     * desaparecer sin más: la portada perdió sus dos llamados a la acción y no
     * se vio hasta mirar la página, porque no hay error que mirar — un `null`
     * simplemente no pinta nada.
     *
     * @return array<string,mixed>
     */
    public function grupo(string $clave): array
    {
        $valor = $this->datos[$clave] ?? null;

        // Una LISTA (0,1,2…) no es un grupo de campos: eso lo sirve `lista()`.
        return is_array($valor) && ! array_is_list($valor) ? $valor : [];
    }
}
