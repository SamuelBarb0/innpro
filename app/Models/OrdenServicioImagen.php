<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenServicioImagen extends Model
{
    use HasFactory;

    protected $table = 'orden_servicio_imagenes';

    protected $fillable = ['orden_servicio_id', 'ruta', 'descripcion'];

    public function orden() { return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id'); }

    /**
     * URL pública de la imagen.
     *
     * Se resuelve con asset() sobre el webroot: no depende de la config cacheada
     * de APP_URL (que era lo que rompía las imágenes en producción) y sirve tanto
     * las rutas nuevas (imagenes/ordenes/...) como las antiguas (ordenes_servicio/...).
     */
    public function getUrlAttribute(): string
    {
        return asset($this->ruta);
    }
}
