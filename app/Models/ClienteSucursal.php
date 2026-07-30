<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sede o proyecto de un cliente en una ciudad concreta.
 *
 * Innpro atiende al mismo cliente en varias ciudades y cada una lleva su
 * propio hilo de servicios y, más adelante, su propio stock.
 */
class ClienteSucursal extends Model
{
    protected $table = 'cliente_sucursales';

    protected $fillable = [
        'cliente_id',
        'nombre',
        'ciudad',
        'direccion',
        'contacto',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /** "Sede Norte (Cali)" — la ciudad es lo que distingue una sede de otra. */
    public function getEtiquetaAttribute(): string
    {
        return $this->ciudad
            ? "{$this->nombre} ({$this->ciudad})"
            : $this->nombre;
    }
}
