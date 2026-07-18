<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OrdenServicioBitacoraFoto extends Model
{
    use HasFactory;

    protected $table = 'orden_servicio_bitacora_fotos';

    protected $fillable = ['bitacora_id', 'ruta'];

    public function bitacora() { return $this->belongsTo(OrdenServicioBitacora::class, 'bitacora_id'); }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->ruta);
    }
}
