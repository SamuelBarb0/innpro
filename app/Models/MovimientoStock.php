<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id',
        'variante_producto_id',
        'cliente_id',
        'sucursal_id',
        'tipo_movimiento',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'referencia_documento',
        'origen',
        'motivo',
        'usuario_id',
        'solicitud_cotizacion_id'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /** A qué proyecto se atribuye el movimiento (null = bodega general). */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(ClienteSucursal::class, 'sucursal_id');
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_producto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function solicitudCotizacion()
    {
        return $this->belongsTo(SolicitudCotizacion::class, 'solicitud_cotizacion_id');
    }

    // Obtener descripción del producto/variante
    public function getDescripcionProductoAttribute()
    {
        $descripcion = $this->producto->nombre;
        if ($this->variante) {
            $descripcion .= ' - ' . $this->variante->nombre_variante;
        }
        return $descripcion;
    }

    // Obtener color del movimiento
    public function getColorMovimientoAttribute()
    {
        return match($this->tipo_movimiento) {
            'entrada' => 'success',
            'salida' => 'danger',
            'ajuste' => 'warning',
            'reserva' => 'info',
            'liberacion' => 'secondary',
            default => 'dark'
        };
    }

    // Obtener icono del movimiento
    public function getIconoMovimientoAttribute()
    {
        return match($this->tipo_movimiento) {
            'entrada' => 'bi-arrow-down-circle',
            'salida' => 'bi-arrow-up-circle',
            'ajuste' => 'bi-gear',
            'reserva' => 'bi-lock',
            'liberacion' => 'bi-unlock',
            default => 'bi-circle'
        };
    }

    // Obtener descripción del origen
    public function getDescripcionOrigenAttribute()
    {
        return match($this->origen) {
            'compra' => 'Compra',
            'venta' => 'Venta',
            'devolucion' => 'Devolución',
            'ajuste_inventario' => 'Ajuste de Inventario',
            'cotizacion' => 'Cotización',
            default => 'Otro'
        };
    }

    // Scopes
    public function scopeEntradas($query)
    {
        return $query->where('tipo_movimiento', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('tipo_movimiento', 'salida');
    }

    public function scopeDelMes($query, $mes = null, $año = null)
    {
        $mes = $mes ?? date('m');
        $año = $año ?? date('Y');
        
        return $query->whereMonth('created_at', $mes)
                     ->whereYear('created_at', $año);
    }

    public function scopePorProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopePorVariante($query, $varianteId)
    {
        return $query->where('variante_producto_id', $varianteId);
    }
}