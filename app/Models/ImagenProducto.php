<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImagenProducto extends Model
{
    use HasFactory;

    protected $table = 'imagenes_productos';

    protected $fillable = [
        'producto_id',
        'ruta_imagen',
        'texto_alternativo',
        'orden',
        'es_principal'
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * URL pública de la imagen.
     *
     * Las imágenes se guardan dentro del webroot (public/imagenes/productos/...),
     * así que se resuelven con asset(): Storage::url() antepone el APP_URL de la
     * configuración cacheada y, si ese valor no coincide con el dominio real,
     * las imágenes no se ven.
     */
    public function getUrlAttribute()
    {
        return asset($this->ruta_imagen);
    }

    protected static function boot()
    {
        parent::boot();

        // Asegurar que solo una imagen sea principal por producto
        static::creating(function ($imagen) {
            if ($imagen->es_principal) {
                static::where('producto_id', $imagen->producto_id)
                    ->update(['es_principal' => false]);
            }
        });

        static::updating(function ($imagen) {
            if ($imagen->es_principal && $imagen->isDirty('es_principal')) {
                static::where('producto_id', $imagen->producto_id)
                    ->where('id', '!=', $imagen->id)
                    ->update(['es_principal' => false]);
            }
        });
    }
}