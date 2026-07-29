<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenServicioBitacoraFoto extends Model
{
    use HasFactory;

    protected $table = 'orden_servicio_bitacora_fotos';

    protected $fillable = ['bitacora_id', 'ruta'];

    public function bitacora() { return $this->belongsTo(OrdenServicioBitacora::class, 'bitacora_id'); }

    // Ver nota en OrdenServicioImagen::getUrlAttribute() sobre por qué no se usa Storage::url().
    public function getUrlAttribute(): string
    {
        return asset($this->ruta);
    }
}
