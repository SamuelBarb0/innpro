<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitioRedireccion extends Model
{
    protected $table = 'sitio_redirecciones';

    protected $fillable = ['origen', 'destino', 'codigo', 'activo', 'golpes', 'ultimo_golpe_at'];

    protected $casts = [
        'activo' => 'boolean',
        'codigo' => 'integer',
        'golpes' => 'integer',
        'ultimo_golpe_at' => 'datetime',
    ];

    /**
     * Normaliza una ruta para compararla: sin dominio, sin barras sobrantes y
     * en minúsculas.
     *
     * Hace falta porque las URLs viejas llegan escritas de todas las formas
     * posibles —con barra final, con mayúsculas, copiadas con el dominio
     * delante desde Search Console— y una redirección que no encaja por una
     * barra es una redirección que no existe.
     */
    public static function normalizar(?string $ruta): string
    {
        $ruta = trim((string) $ruta);

        // Si viene con dominio, se queda solo con el camino y su query.
        if (str_contains($ruta, '://')) {
            $partes = parse_url($ruta);
            $ruta = ($partes['path'] ?? '').(isset($partes['query']) ? '?'.$partes['query'] : '');
        }

        return mb_strtolower(trim($ruta, "/ \t\n\r\0\x0B"));
    }

    public function registrarGolpe(): void
    {
        // `increment` en vez de leer y guardar: dos visitas a la vez no se
        // pisan el contador.
        $this->increment('golpes');
        $this->forceFill(['ultimo_golpe_at' => now()])->saveQuietly();
    }
}
