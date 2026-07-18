<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OrdenServicio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'numero', 'token_publico', 'cliente_id', 'tecnico_id', 'creado_por',
        'titulo', 'descripcion_problema', 'diagnostico',
        'estado', 'prioridad', 'costo_mano_obra',
        'fecha_ingreso', 'fecha_estimada', 'fecha_cierre',
        'bitacora_visible_cliente', 'observaciones',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrdenServicio $orden) {
            if (empty($orden->token_publico)) {
                $orden->token_publico = Str::random(48);
            }
        });
    }

    // URL pública de seguimiento (solo lectura) para el cliente
    public function urlSeguimiento(): string
    {
        return route('servicio.seguimiento', $this->token_publico);
    }

    protected $casts = [
        'costo_mano_obra'          => 'decimal:2',
        'fecha_ingreso'            => 'date',
        'fecha_estimada'           => 'date',
        'fecha_cierre'             => 'datetime',
        'bitacora_visible_cliente' => 'boolean',
    ];

    /* ===================== Catálogos ===================== */

    public const ESTADOS = [
        'recibida'          => ['label' => 'Recibida',           'color' => 'secondary'],
        'en_diagnostico'    => ['label' => 'En diagnóstico',     'color' => 'info'],
        'en_proceso'        => ['label' => 'En proceso',         'color' => 'primary'],
        'espera_repuestos'  => ['label' => 'Espera de repuestos','color' => 'warning'],
        'finalizada'        => ['label' => 'Finalizada',         'color' => 'success'],
        'entregada'         => ['label' => 'Entregada',          'color' => 'dark'],
        'cancelada'         => ['label' => 'Cancelada',          'color' => 'danger'],
    ];

    public const PRIORIDADES = [
        'baja'    => ['label' => 'Baja',    'color' => 'secondary'],
        'media'   => ['label' => 'Media',   'color' => 'info'],
        'alta'    => ['label' => 'Alta',    'color' => 'warning'],
        'urgente' => ['label' => 'Urgente', 'color' => 'danger'],
    ];

    /* ===================== Relaciones ===================== */

    public function cliente()   { return $this->belongsTo(Cliente::class, 'cliente_id'); }
    public function tecnico()   { return $this->belongsTo(User::class, 'tecnico_id'); }
    public function creador()   { return $this->belongsTo(User::class, 'creado_por'); }

    public function items()     { return $this->hasMany(OrdenServicioItem::class, 'orden_servicio_id'); }
    public function equipos()   { return $this->items()->where('tipo', 'equipo'); }
    public function repuestos() { return $this->items()->where('tipo', 'repuesto'); }

    public function bitacora()  { return $this->hasMany(OrdenServicioBitacora::class, 'orden_servicio_id')->latest(); }
    public function imagenes()  { return $this->hasMany(OrdenServicioImagen::class, 'orden_servicio_id'); }

    /* ===================== Helpers ===================== */

    public function estadoLabel(): string { return self::ESTADOS[$this->estado]['label'] ?? $this->estado; }
    public function estadoColor(): string { return self::ESTADOS[$this->estado]['color'] ?? 'secondary'; }
    public function prioridadLabel(): string { return self::PRIORIDADES[$this->prioridad]['label'] ?? $this->prioridad; }
    public function prioridadColor(): string { return self::PRIORIDADES[$this->prioridad]['color'] ?? 'secondary'; }

    // Total de ítems (equipos + repuestos)
    public function getTotalItemsAttribute(): float
    {
        return (float) $this->items->sum(fn ($i) => (float) $i->cantidad * (float) $i->precio_unitario);
    }

    // Costo total del servicio = mano de obra + ítems (B5: reflejado en el total)
    public function getTotalAttribute(): float
    {
        return (float) $this->costo_mano_obra + $this->total_items;
    }

    public function getHorasTotalesAttribute(): float
    {
        return (float) $this->bitacora->sum('horas_trabajadas');
    }

    // Genera el siguiente número de orden (OS-000001)
    public static function generarNumero(): string
    {
        $next = (int) (static::withTrashed()->max('id') ?? 0) + 1;
        return 'OS-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /* ===================== Scopes ===================== */

    public function scopeAbiertas($query)
    {
        return $query->whereNotIn('estado', ['entregada', 'cancelada']);
    }

    public function scopePorTecnico($query, $tecnicoId)
    {
        return $query->where('tecnico_id', $tecnicoId);
    }
}
