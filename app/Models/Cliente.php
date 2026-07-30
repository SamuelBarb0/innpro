<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'numero_identificacion',
        'nombre_contacto',
        'nombre_empresa',
        'email',
        'telefono',
        'pais',
        'ciudad',
        'vendedor_id',
        'lista_precio_id',
        'activo',
        'es_temporal',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_temporal' => 'boolean',
    ];

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function enlacesAcceso()
    {
        return $this->hasMany(EnlaceAcceso::class, 'cliente_id');
    }

    public function enlacesAccesoActivos()
    {
        return $this->hasMany(EnlaceAcceso::class, 'cliente_id')
            ->where('activo', true)
            ->where('expira_en', '>', now());
    }

    public function solicitudesCotizacion()
    {
        return $this->hasMany(SolicitudCotizacion::class, 'cliente_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorVendedor($query, $vendedorId)
    {
        return $query->where('vendedor_id', $vendedorId);
    }

    /** Prospectos creados al vuelo desde el cotizador. */
    public function scopeTemporales($query)
    {
        return $query->where('es_temporal', true);
    }

    /** Clientes dados de alta en forma, con sus datos completos. */
    public function scopePermanentes($query)
    {
        return $query->where('es_temporal', false);
    }

    /**
     * Un prospecto se queda a medias a propósito (sin NIT, correo ni ciudad).
     * Cuando ya tiene todo eso, deja de serlo.
     */
    public function puedeConvertirseEnCliente(): bool
    {
        return $this->es_temporal
            && filled($this->numero_identificacion)
            && filled($this->email)
            && filled($this->ciudad);
    }
}
