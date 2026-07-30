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
        'numero', 'token_publico', 'cliente_id', 'sucursal_id', 'tecnico_id', 'creado_por',
        'titulo', 'descripcion_problema', 'diagnostico',
        'estado', 'prioridad', 'costo_mano_obra',
        'fecha_ingreso', 'fecha_estimada', 'fecha_cierre',
        'bitacora_visible_cliente', 'observaciones',
        'firma_tecnico_ruta', 'firma_tecnico_nombre', 'firma_tecnico_cc', 'firma_tecnico_at',
        'firma_cliente_ruta', 'firma_cliente_nombre', 'firma_cliente_cc', 'firma_cliente_at',
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
        'firma_tecnico_at'         => 'datetime',
        'firma_cliente_at'         => 'datetime',
    ];

    /* ===================== Catálogos ===================== */

    public const ESTADOS = [
        'recibida'          => ['label' => 'Recibida',           'color' => 'secondary'],
        'en_diagnostico'    => ['label' => 'En diagnóstico',     'color' => 'info'],
        'en_proceso'        => ['label' => 'En proceso',         'color' => 'primary'],
        'espera_repuestos'  => ['label' => 'Espera de repuestos','color' => 'warning'],
        'finalizada'        => ['label' => 'Finalizada',         'color' => 'success'],
        'garantia'          => ['label' => 'Garantía',           'color' => 'warning'],
        'facturado'         => ['label' => 'Facturado',          'color' => 'dark'],
    ];

    /** Estados que puede manejar un técnico: el resto es gestión administrativa. */
    public const ESTADOS_TECNICO = ['recibida', 'en_proceso', 'finalizada'];

    /** Cierres posibles de una orden: se factura o se atiende por garantía. */
    public const ESTADOS_CIERRE = ['garantia', 'facturado'];

    public const PRIORIDADES = [
        'baja'    => ['label' => 'Baja',    'color' => 'secondary'],
        'media'   => ['label' => 'Media',   'color' => 'info'],
        'alta'    => ['label' => 'Alta',    'color' => 'warning'],
        'urgente' => ['label' => 'Urgente', 'color' => 'danger'],
    ];

    /* ===================== Relaciones ===================== */

    public function cliente()   { return $this->belongsTo(Cliente::class, 'cliente_id'); }
    public function sucursal()  { return $this->belongsTo(ClienteSucursal::class, 'sucursal_id'); }
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

    /* ===================== Firmas (colector del formato técnico) ===================== */

    public const TIPOS_FIRMA = ['tecnico', 'cliente'];

    public function tieneFirma(string $tipo): bool
    {
        return ! empty($this->{"firma_{$tipo}_ruta"});
    }

    /** URL web de la firma (relativa al host actual, no depende de APP_URL cacheada). */
    public function firmaUrl(string $tipo): ?string
    {
        $ruta = $this->{"firma_{$tipo}_ruta"};

        return $ruta ? asset($ruta) : null;
    }

    /** Data URI para incrustar la firma en el PDF (DomPDF no resuelve URLs remotas). */
    public function firmaDataUri(string $tipo): ?string
    {
        $ruta = $this->{"firma_{$tipo}_ruta"};
        if (! $ruta) {
            return null;
        }

        $absoluta = public_path($ruta);
        if (! is_file($absoluta)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($absoluta));
    }

    // El nombre que se muestra bajo la línea de firma, con respaldo al dato maestro.
    public function firmanteNombre(string $tipo): ?string
    {
        return $this->{"firma_{$tipo}_nombre"}
            ?: ($tipo === 'tecnico' ? $this->tecnico?->name : $this->cliente?->nombre_contacto);
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
        return $query->whereNotIn('estado', self::ESTADOS_CIERRE);
    }

    public function scopePorTecnico($query, $tecnicoId)
    {
        return $query->where('tecnico_id', $tecnicoId);
    }
}
