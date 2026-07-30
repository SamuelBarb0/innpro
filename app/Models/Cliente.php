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

    /** Sedes/proyectos del cliente, una por ciudad donde se le atiende. */
    public function sucursales()
    {
        return $this->hasMany(ClienteSucursal::class, 'cliente_id');
    }

    public function sucursalesActivas()
    {
        return $this->hasMany(ClienteSucursal::class, 'cliente_id')->where('activo', true);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorVendedor($query, $vendedorId)
    {
        return $query->where('vendedor_id', $vendedorId);
    }

    /**
     * Da de alta un prospecto: lo mínimo para poder cotizarle o abrirle una
     * orden sin inventarle NIT, correo ni ciudad.
     *
     * @param  array{nombre_contacto:string,nombre_empresa?:?string,telefono?:?string,email?:?string,ciudad?:?string}  $datos
     */
    public static function crearProspecto(array $datos, int $vendedorId): self
    {
        return self::create([
            'numero_identificacion' => null,
            'nombre_contacto' => $datos['nombre_contacto'],
            'nombre_empresa' => $datos['nombre_empresa'] ?? null,
            'email' => $datos['email'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'pais' => null,
            'ciudad' => $datos['ciudad'] ?? null,
            'vendedor_id' => $vendedorId,
            'lista_precio_id' => self::listaPrecioProspectos(),
            'activo' => true,
            'es_temporal' => true,
        ]);
    }

    /**
     * Lista de precios estándar para prospectos (pedido 16 de la reunión).
     *
     * Sale del parámetro `lista_precio_temporales`; si quedó vacío o apunta a
     * una lista borrada, se cae a la primera disponible para no dejar el
     * cotizador inservible.
     */
    public static function listaPrecioProspectos(): ?int
    {
        $configurada = Parametros::valor('lista_precio_temporales');

        if (filled($configurada) && ListaPrecio::whereKey($configurada)->exists()) {
            return (int) $configurada;
        }

        return ListaPrecio::orderBy('id')->value('id');
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
