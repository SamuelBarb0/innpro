<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametros extends Model
{
    // Nombre de la tabla
    protected $table = 'parametros';

    // Nombre de la clave primaria
    protected $primaryKey = 'id_parametro';

    // No utiliza timestamps automáticos (created_at, updated_at)
    public $timestamps = false;

    // Casts para campos booleanos y fechas
    protected $casts = [
        'estado' => 'boolean',
        'reservado' => 'boolean',
        'created' => 'datetime',
        'updated' => 'datetime',
    ];

    // Campos que pueden ser asignados masivamente
    protected $fillable = [
        'nombre_parametro',
        'valor_parametro',
        'estado',
        'comentario',
        'reservado',
        'created',
        'updated',
    ];

    /**
     * Obtiene el valor de un parámetro por su nombre.
     */
    public static function valor(string $nombre, ?string $default = null): ?string
    {
        $valor = static::where('nombre_parametro', $nombre)->value('valor_parametro');
        return ($valor === null || $valor === '') ? $default : $valor;
    }

    /**
     * Datos corporativos usados en el encabezado del PDF de cotización.
     * Se leen desde la tabla `parametros` (editables por BD) con fallback seguro.
     */
    public static function empresa(): array
    {
        return [
            'razon_social' => static::valor('empresa_razon_social', 'Innpro Ingeniería SAS'),
            'ruc'          => static::valor('empresa_ruc', ''),
            'direccion'    => static::valor('empresa_direccion', ''),
            'telefonos'    => static::valor('empresa_telefonos', ''),
            'email'        => static::valor('empresa_email', ''),
            'sitio_web'    => static::valor('empresa_sitio_web', ''),
        ];
    }
}
