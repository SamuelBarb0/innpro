<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockProducto extends Model
{
    use HasFactory;

    protected $table = 'stock_productos';

    protected $fillable = [
        'producto_id',
        'variante_producto_id',
        'cliente_id',
        'sucursal_id',
        'cantidad_disponible',
        'cantidad_reservada',
        'stock_minimo',
        'stock_maximo',
        'ubicacion',
        'notas',
        'alerta_stock_bajo'
    ];

    protected $casts = [
        'alerta_stock_bajo' => 'boolean',
        'cantidad_disponible' => 'integer',
        'cantidad_reservada' => 'integer',
        'stock_minimo' => 'integer',
        'stock_maximo' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($stock) {
            // Garantizar que los campos NOT NULL nunca queden en null
            $stock->cantidad_disponible = (int) ($stock->cantidad_disponible ?? 0);
            $stock->cantidad_reservada  = (int) ($stock->cantidad_reservada ?? 0);
            $stock->stock_minimo        = (int) ($stock->stock_minimo ?? 0);
            if ($stock->stock_maximo === '' ) {
                $stock->stock_maximo = null;
            }
            if ($stock->alerta_stock_bajo === null) {
                $stock->alerta_stock_bajo = true;
            }
        });
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_producto_id');
    }

    /** Dueño de la existencia: null = bodega general, sin proyecto asociado. */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(ClienteSucursal::class, 'sucursal_id');
    }

    /** Existencias recibidas para una sede concreta. */
    public function scopeDeSucursal($query, $sucursalId)
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    /** Existencias de bodega, las que no pertenecen a ningún proyecto. */
    public function scopeGenerales($query)
    {
        return $query->whereNull('cliente_id')->whereNull('sucursal_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoStock::class, 'producto_id', 'producto_id')
                    ->where(function($query) {
                        $query->whereNull('variante_producto_id')
                              ->orWhere('variante_producto_id', $this->variante_producto_id);
                    });
    }

    // Obtener stock real (disponible - reservado)
    public function getStockRealAttribute()
    {
        return $this->cantidad_disponible - $this->cantidad_reservada;
    }

    // Verificar si hay stock bajo
    public function getStockBajoAttribute()
    {
        return $this->alerta_stock_bajo && ($this->stock_real <= $this->stock_minimo);
    }

    // Verificar disponibilidad
    public function hayDisponibilidad($cantidad)
    {
        return $this->stock_real >= $cantidad;
    }

    // Reservar stock
    public function reservar($cantidad, $referencia = null)
    {
        if (!$this->hayDisponibilidad($cantidad)) {
            return false;
        }

        $this->cantidad_reservada += $cantidad;
        $this->save();

        // Registrar movimiento
        MovimientoStock::create([
            'producto_id' => $this->producto_id,
            'variante_producto_id' => $this->variante_producto_id,
            'tipo_movimiento' => 'reserva',
            'cantidad' => $cantidad,
            'stock_anterior' => $this->cantidad_disponible,
            'stock_nuevo' => $this->cantidad_disponible,
            'referencia_documento' => $referencia,
            'origen' => 'cotizacion',
            'usuario_id' => auth()->id() ?? 1
        ]);

        return true;
    }

    // Liberar reserva
    public function liberarReserva($cantidad, $referencia = null)
    {
        $this->cantidad_reservada = max(0, $this->cantidad_reservada - $cantidad);
        $this->save();

        // Registrar movimiento
        MovimientoStock::create([
            'producto_id' => $this->producto_id,
            'variante_producto_id' => $this->variante_producto_id,
            'tipo_movimiento' => 'liberacion',
            'cantidad' => $cantidad,
            'stock_anterior' => $this->cantidad_disponible,
            'stock_nuevo' => $this->cantidad_disponible,
            'referencia_documento' => $referencia,
            'origen' => 'cotizacion',
            'usuario_id' => auth()->id() ?? 1
        ]);

        return true;
    }

    // Entrada de stock
    public function entrada($cantidad, $origen = 'compra', $referencia = null, $motivo = null)
    {
        $stockAnterior = $this->cantidad_disponible;
        $this->cantidad_disponible += $cantidad;
        $this->save();

        // Registrar movimiento
        MovimientoStock::create([
            'producto_id' => $this->producto_id,
            'variante_producto_id' => $this->variante_producto_id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $this->cantidad_disponible,
            'referencia_documento' => $referencia,
            'origen' => $origen,
            'motivo' => $motivo,
            'usuario_id' => auth()->id() ?? 1
        ]);

        return true;
    }

    // Salida de stock
    public function salida($cantidad, $origen = 'venta', $referencia = null, $motivo = null)
    {
        if ($this->cantidad_disponible < $cantidad) {
            return false;
        }

        $stockAnterior = $this->cantidad_disponible;
        $this->cantidad_disponible -= $cantidad;
        
        // Si había reserva, la reducimos
        if ($this->cantidad_reservada > 0) {
            $this->cantidad_reservada = max(0, $this->cantidad_reservada - $cantidad);
        }
        
        $this->save();

        // Registrar movimiento
        MovimientoStock::create([
            'producto_id' => $this->producto_id,
            'variante_producto_id' => $this->variante_producto_id,
            'tipo_movimiento' => 'salida',
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $this->cantidad_disponible,
            'referencia_documento' => $referencia,
            'origen' => $origen,
            'motivo' => $motivo,
            'usuario_id' => auth()->id() ?? 1
        ]);

        return true;
    }

    // Ajuste de inventario
    public function ajustar($nuevaCantidad, $motivo = null)
    {
        $stockAnterior = $this->cantidad_disponible;
        $diferencia = $nuevaCantidad - $stockAnterior;
        
        $this->cantidad_disponible = $nuevaCantidad;
        $this->save();

        // Registrar movimiento
        MovimientoStock::create([
            'producto_id' => $this->producto_id,
            'variante_producto_id' => $this->variante_producto_id,
            'tipo_movimiento' => 'ajuste',
            'cantidad' => $diferencia,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $this->cantidad_disponible,
            'origen' => 'ajuste_inventario',
            'motivo' => $motivo,
            'usuario_id' => auth()->id() ?? 1
        ]);

        return true;
    }

    // Scopes
    public function scopeConStockBajo($query)
    {
        return $query->whereRaw('(cantidad_disponible - cantidad_reservada) <= stock_minimo')
                     ->where('alerta_stock_bajo', true);
    }

    public function scopeSinStock($query)
    {
        return $query->whereRaw('(cantidad_disponible - cantidad_reservada) <= 0');
    }

    public function scopeConStock($query)
    {
        return $query->whereRaw('(cantidad_disponible - cantidad_reservada) > 0');
    }
}