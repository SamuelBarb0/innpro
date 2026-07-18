<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenServicioItem extends Model
{
    use HasFactory;

    protected $table = 'orden_servicio_items';

    protected $fillable = [
        'orden_servicio_id', 'producto_id', 'tipo',
        'descripcion', 'cantidad', 'precio_unitario', 'notas',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:2',
        'precio_unitario' => 'decimal:2',
    ];

    public function orden()    { return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id'); }
    public function producto() { return $this->belongsTo(Producto::class, 'producto_id'); }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->cantidad * (float) $this->precio_unitario;
    }
}
