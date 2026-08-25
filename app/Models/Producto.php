<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'referencia',
        'nombre',
        'descripcion',
        'unidad_venta',
        'unidad_empaque',
        'peso_paca',
        'cubicaje_paca',
        'unidades_por_paca',
        'codigo_barras',
        'tiene_extension',
        'categoria_id',
        'activo',
        'tiene_variantes',
        'controlar_stock',
        'permitir_venta_sin_stock'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'peso_paca' => 'decimal:3',
        'cubicaje_paca' => 'decimal:4',
        'unidades_por_paca' => 'decimal:3',
        'tiene_extension' => 'boolean',
        'tiene_variantes' => 'boolean',
        'controlar_stock' => 'boolean',
        'permitir_venta_sin_stock' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function imagenes()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id')->orderBy('orden');
    }

    public function imagenPrincipal()
    {
        return $this->hasOne(ImagenProducto::class, 'producto_id')->where('es_principal', true);
    }

    public function precios()
    {
        return $this->hasMany(PrecioProducto::class, 'producto_id');
    }

    public function variantes()
    {
        return $this->hasMany(VarianteProducto::class, 'producto_id')->where('activo', true);
    }

    public function stock()
    {
        return $this->hasMany(StockProducto::class, 'producto_id');
    }

    // Stock del producto sin variantes
    public function stockPrincipal()
    {
        return $this->hasOne(StockProducto::class, 'producto_id')->whereNull('variante_producto_id');
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class, 'producto_id');
    }

    public function itemsSolicitudCotizacion()
    {
        return $this->hasMany(ItemSolicitudCotizacion::class, 'producto_id');
    }

    // ===== Cálculo de peso/volumen según cantidad pedida (paca) =====
    // pacas = cantidad / unidades_por_paca. Devuelve null si no hay unidades_por_paca.
    public function pacasParaCantidad($cantidad): ?float
    {
        $upp = $this->unidades_por_paca !== null ? (float) $this->unidades_por_paca : null;
        if (!$upp || $upp <= 0) {
            return null;
        }
        return (float) $cantidad / $upp;
    }

    // peso total = pacas * peso_paca. Null si falta el dato.
    public function pesoTotalParaCantidad($cantidad): ?float
    {
        $pacas = $this->pacasParaCantidad($cantidad);
        if ($pacas === null || $this->peso_paca === null) {
            return null;
        }
        return $pacas * (float) $this->peso_paca;
    }

    // volumen (cubicaje) total = pacas * cubicaje_paca. Null si falta el dato.
    public function volumenTotalParaCantidad($cantidad): ?float
    {
        $pacas = $this->pacasParaCantidad($cantidad);
        if ($pacas === null || $this->cubicaje_paca === null) {
            return null;
        }
        return $pacas * (float) $this->cubicaje_paca;
    }

    // Obtener precio por lista de precios
    public function getPrecioPorLista($listaPrecioId)
    {
        // Si quien llama ya precargó `precios` (el cotizador lo hace), se resuelve
        // en memoria. Sin esto era una consulta por producto: con 5.000 productos
        // en pantalla, 5.000 consultas de más en una sola petición.
        if ($this->relationLoaded('precios')) {
            $precio = $this->precios->first(function ($p) use ($listaPrecioId) {
                return (int) $p->lista_precio_id === (int) $listaPrecioId && $p->activo;
            });
            return $precio ? $precio->precio : null;
        }

        $precio = $this->precios()->where('lista_precio_id', $listaPrecioId)->where('activo', true)->first();
        return $precio ? $precio->precio : null;
    }

    // Obtener URL de imagen principal
    public function getUrlImagenPrincipalAttribute()
    {
        // asset() y no Storage::url(): ver nota en ImagenProducto::getUrlAttribute().
        $imagenPrincipal = $this->imagenPrincipal ?? $this->imagenes->first();
        return $imagenPrincipal ? asset($imagenPrincipal->ruta_imagen) : asset('images/sin-imagen.png');
    }

    // Obtener stock total del producto (suma de todas las variantes o stock principal)
    public function getStockTotalAttribute()
    {
        if ($this->tiene_variantes) {
            return $this->stock()->sum('cantidad_disponible');
        } else {
            $stockPrincipal = $this->stockPrincipal;
            return $stockPrincipal ? $stockPrincipal->cantidad_disponible : 0;
        }
    }

    // Obtener stock disponible (considerando reservas)
    public function getStockDisponibleAttribute()
    {
        if ($this->tiene_variantes) {
            // Con la relación precargada se suma en memoria; si no, se consulta.
            // Sin esto, un listado de N productos hacía N consultas de más.
            if ($this->relationLoaded('stock')) {
                return $this->stock->sum(fn ($s) => $s->cantidad_disponible - $s->cantidad_reservada);
            }

            return $this->stock()->selectRaw('SUM(cantidad_disponible - cantidad_reservada) as total')->value('total') ?? 0;
        }

        $stockPrincipal = $this->stockPrincipal;
        return $stockPrincipal ? $stockPrincipal->stock_real : 0;
    }

    // Verificar si hay stock bajo
    public function getTieneStockBajoAttribute()
    {
        if ($this->relationLoaded('stock')) {
            return $this->stock->contains(fn ($s) =>
                $s->alerta_stock_bajo
                && ($s->cantidad_disponible - $s->cantidad_reservada) <= $s->stock_minimo
            );
        }

        return $this->stock()->where('alerta_stock_bajo', true)
                              ->whereRaw('(cantidad_disponible - cantidad_reservada) <= stock_minimo')
                              ->exists();
    }

    // Verificar si hay stock disponible
    public function hayStock($cantidad = 1, $varianteId = null)
    {
        if (!$this->controlar_stock || $this->permitir_venta_sin_stock) {
            return true;
        }

        if ($varianteId) {
            $stock = $this->stock()->where('variante_producto_id', $varianteId)->first();
        } else {
            $stock = $this->stockPrincipal;
        }

        return $stock && $stock->hayDisponibilidad($cantidad);
    }

    // Inicializar stock si no existe
    public function inicializarStock()
    {
        if ($this->tiene_variantes) {
            foreach ($this->variantes as $variante) {
                StockProducto::firstOrCreate(
                    [
                        'producto_id' => $this->id,
                        'variante_producto_id' => $variante->id
                    ],
                    [
                        'cantidad_disponible' => 0,
                        'cantidad_reservada' => 0,
                        'stock_minimo' => 0,
                        'alerta_stock_bajo' => true
                    ]
                );
            }
        } else {
            StockProducto::firstOrCreate(
                [
                    'producto_id' => $this->id,
                    'variante_producto_id' => null
                ],
                [
                    'cantidad_disponible' => 0,
                    'cantidad_reservada' => 0,
                    'stock_minimo' => 0,
                    'alerta_stock_bajo' => true
                ]
            );
        }
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    public function scopeBuscar($query, $termino)
    {
        $termino = trim((string) $termino);
        if ($termino === '') {
            return $query;
        }
        $like = '%' . $termino . '%';
        return $query->where(function ($q) use ($like) {
            $q->where('nombre', 'like', $like)
              ->orWhere('referencia', 'like', $like);
        });
    }

    public function scopeConStock($query)
    {
        return $query->whereHas('stock', function($q) {
            $q->whereRaw('(cantidad_disponible - cantidad_reservada) > 0');
        });
    }

    public function scopeSinStock($query)
    {
        return $query->whereHas('stock', function($q) {
            $q->whereRaw('(cantidad_disponible - cantidad_reservada) <= 0');
        })->orWhereDoesntHave('stock');
    }

    public function scopeConStockBajo($query)
    {
        return $query->whereHas('stock', function($q) {
            $q->where('alerta_stock_bajo', true)
              ->whereRaw('(cantidad_disponible - cantidad_reservada) <= stock_minimo');
        });
    }
}