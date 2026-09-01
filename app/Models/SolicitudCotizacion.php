<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SolicitudCotizacion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_cotizacion';

    /**
     * Tipos de cotización. La clave es lo que se guarda; la etiqueta, lo que se
     * muestra. Se define aquí y no suelto en las vistas para que no haya dos
     * listas que puedan quedar desalineadas.
     */
    public const TIPOS = [
        'proyecto'                  => 'Proyecto',
        'mantenimiento_preventivo'  => 'Mantenimiento Preventivo',
        'mantenimiento_correctivo'  => 'Mantenimiento Correctivo',
        'asistencia'                => 'Asistencia',
    ];

    protected $fillable = [
        'numero_solicitud',
        'cliente_id',
        'enlace_acceso_id',
        'estado',
        'tipo_cotizacion',
        'monto_total',
        'notas_cliente',
        'observaciones_admin',
        'aplicada_en',
        'aplicada_por'
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'aplicada_en' => 'datetime',
    ];

    /** Etiqueta legible del tipo. Sin tipo = las que no pasaron por el alta de prospecto. */
    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo_cotizacion] ?? 'Sin tipo';
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id')->withTrashed();
    }

    public function enlaceAcceso()
    {
        return $this->belongsTo(EnlaceAcceso::class, 'enlace_acceso_id');
    }

    public function items()
    {
        return $this->hasMany(ItemSolicitudCotizacion::class, 'solicitud_cotizacion_id');
    }

    public function aplicadaPor()
    {
        return $this->belongsTo(User::class, 'aplicada_por');
    }

    public function getTotalItemsAttribute()
    {
        return $this->items->sum('cantidad');
    }

    /**
     * Código corto único basado en el id (p.ej. OE-00042).
     * Reemplaza el numero_solicitud largo para nombres de archivo y referencias.
     */
    public function getCodigoCortoAttribute(): string
    {
        return 'OE-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Nombre de archivo del PDF (sin extensión).
     */
    public function getNombreArchivoPdfAttribute(): string
    {
        return 'Solicitud ' . config('app.name') . ' ' . $this->codigo_corto;
    }

    public function calcularMontoTotal()
    {
        $this->monto_total = $this->items->sum('precio_total');
        $this->save();
        return $this->monto_total;
    }

    public function marcarComoAplicada($usuarioId, $observaciones = null)
    {
        $this->update([
            'estado' => 'aplicada',
            'aplicada_en' => now(),
            'aplicada_por' => $usuarioId,
            'observaciones_admin' => $observaciones
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($solicitud) {
            if (empty($solicitud->numero_solicitud)) {
                $solicitud->numero_solicitud = 'SC-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
            }
        });
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAplicadas($query)
    {
        return $query->where('estado', 'aplicada');
    }

    public function scopePorCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }
}