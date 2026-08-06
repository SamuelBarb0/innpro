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
}
