<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\StockProducto;
use App\Models\MovimientoStock;
use App\Models\VarianteProducto;
use App\Imports\StockImport;
use App\Exports\PlantillaStockExport;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class StockController extends Controller
{
    // Descargar plantilla Excel de ejemplo para importar stock
    public function descargarPlantilla()
    {
        return Excel::download(
            new PlantillaStockExport(),
            'plantilla_importacion_stock.xlsx'
        );
    }

    // Importar stock desde Excel
    public function importarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls,csv|max:10240'
        ], [
            'archivo.required' => 'Debe seleccionar un archivo',
            'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx, .xls) o CSV',
            'archivo.max'      => 'El archivo no debe superar los 10MB',
        ]);

        try {
            // Pasamos el UploadedFile directamente: Maatwebsite detecta el formato
            // por la extensión original del archivo (getRealPath() da /tmp/phpXXXX sin
            // extensión → "No ReaderType or WriterType could be detected").
            $import = new StockImport();
            Excel::import($import, $request->file('archivo'));

            if ($import->exito > 0 && $import->fallo === 0) {
                return back()->with('success', "Stock actualizado: {$import->exito} registros.");
            } elseif ($import->exito > 0) {
                return back()->with('warning', "Parcial: {$import->exito} actualizados, {$import->fallo} con errores.")
                             ->with('errores_stock', $import->errores);
            }
            return back()->with('error', 'No se pudo actualizar ningún registro.')
                         ->with('errores_stock', $import->errores);
        } catch (\Throwable $e) {
            Log::error('Error import stock: ' . $e->getMessage());
            return back()->with('error', 'Error procesando el archivo: ' . $e->getMessage());
        }
    }

    // Vista principal de gestión de stock
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StockProducto::with(['producto', 'variante'])
                ->select('stock_productos.*');

            // Filtrar por producto si se especifica
            if ($request->has('producto_id') && $request->producto_id) {
                $query->where('producto_id', $request->producto_id);
            }
            
            // Filtrar por estado de stock
            if ($request->has('estado') && $request->estado) {
                switch ($request->estado) {
                    case 'con_stock':
                        $query->conStock();
                        break;
                    case 'sin_stock':
                        $query->sinStock();
                        break;
                    case 'stock_bajo':
                        $query->conStockBajo();
                        break;
                }
            }

            return DataTables::of($query)
                ->addColumn('producto_info', function($stock) {
                    $info = '<strong>' . $stock->producto->referencia . '</strong><br>';
                    $info .= $stock->producto->nombre;
                    if ($stock->variante) {
                        $info .= '<br><small class="text-muted">' . $stock->variante->nombre_variante . '</small>';
                    }
                    return $info;
                })
                ->addColumn('stock_actual', function($stock) {
                    $badge = $stock->stock_bajo ? 'danger' : ($stock->stock_real > 0 ? 'success' : 'warning');
                    return '<span class="badge bg-'.$badge.'">' . $stock->stock_real . '</span>';
                })
                ->addColumn('disponible_reservado', function($stock) {
                    return 'Disponible: ' . $stock->cantidad_disponible . '<br>Reservado: ' . $stock->cantidad_reservada;
                })
                ->addColumn('stock_minimo_maximo', function($stock) {
                    $info = 'Mínimo: ' . $stock->stock_minimo;
                    if ($stock->stock_maximo) {
                        $info .= '<br>Máximo: ' . $stock->stock_maximo;
                    }
                    return $info;
                })
                ->addColumn('ubicacion', fn($stock) => $stock->ubicacion ?: '-')
                ->addColumn('action', function($stock) {
                    $buttons = '<div class="btn-group btn-group-sm">';
                    
                    // Botón entrada
                    $buttons .= '<button type="button" class="btn btn-success" onclick="entradaStock('.$stock->id.')" title="Entrada">
                                    <i class="bi bi-plus-circle"></i>
                                </button>';
                    
                    // Botón salida
                    $buttons .= '<button type="button" class="btn btn-danger" onclick="salidaStock('.$stock->id.')" title="Salida">
                                    <i class="bi bi-dash-circle"></i>
                                </button>';
                    
                    // Botón ajuste
                    $buttons .= '<button type="button" class="btn btn-warning" onclick="ajusteStock('.$stock->id.')" title="Ajuste">
                                    <i class="bi bi-gear"></i>
                                </button>';
                    
                    // Botón configuración
                    $buttons .= '<button type="button" class="btn btn-info" onclick="configurarStock('.$stock->id.')" title="Configurar">
                                    <i class="bi bi-sliders"></i>
                                </button>';
                    
                    // Botón historial
                    $buttons .= '<button type="button" class="btn btn-secondary" onclick="verHistorial('.$stock->producto_id.', '.($stock->variante_producto_id ?: 'null').')" title="Historial">
                                    <i class="bi bi-clock-history"></i>
                                </button>';
                    
                    $buttons .= '</div>';
                    return $buttons;
                })
                ->rawColumns(['producto_info', 'stock_actual', 'disponible_reservado', 'stock_minimo_maximo', 'action'])
                ->make(true);
        }

        $productosConStockBajo = StockProducto::conStockBajo()->count();
        $productosSinStock = StockProducto::sinStock()->count();
        
        // Obtener información del producto si viene filtrado
        $productoFiltrado = null;
        if ($request->has('producto_id') && $request->producto_id) {
            $productoFiltrado = Producto::find($request->producto_id);
        }
        
        return view('stock.index', compact('productosConStockBajo', 'productosSinStock', 'productoFiltrado'));
    }

    // Entrada de stock
    public function entrada(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stock_productos,id',
            'cantidad' => 'required|integer|min:1',
            'referencia' => 'nullable|string|max:255',
            'motivo' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $stock = StockProducto::findOrFail($request->stock_id);
            $stock->entrada(
                $request->cantidad,
                'compra',
                $request->referencia,
                $request->motivo
            );

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Entrada de stock registrada correctamente',
                'stock_actual' => $stock->fresh()->stock_real
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar entrada: ' . $e->getMessage()
            ], 500);
        }
    }

    // Salida de stock
    public function salida(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stock_productos,id',
            'cantidad' => 'required|integer|min:1',
            'referencia' => 'nullable|string|max:255',
            'motivo' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $stock = StockProducto::findOrFail($request->stock_id);
            
            if (!$stock->hayDisponibilidad($request->cantidad)) {
                throw new \Exception('Stock insuficiente. Disponible: ' . $stock->stock_real);
            }
            
            $resultado = $stock->salida(
                $request->cantidad,
                'venta',
                $request->referencia,
                $request->motivo
            );

            if (!$resultado) {
                throw new \Exception('No se pudo procesar la salida de stock');
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Salida de stock registrada correctamente',
                'stock_actual' => $stock->fresh()->stock_real
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Ajuste de inventario
    public function ajuste(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stock_productos,id',
            'nueva_cantidad' => 'required|integer|min:0',
            'motivo' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $stock = StockProducto::findOrFail($request->stock_id);
            $stock->ajustar($request->nueva_cantidad, $request->motivo);

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Ajuste de inventario realizado correctamente',
                'stock_actual' => $stock->fresh()->stock_real
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al ajustar inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    // Configurar parámetros de stock
    public function configurar(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stock_productos,id',
            'stock_minimo' => 'required|integer|min:0',
            'stock_maximo' => 'nullable|integer|min:0',
            'ubicacion' => 'nullable|string|max:255',
            'alerta_stock_bajo' => 'boolean',
            'notas' => 'nullable|string'
        ]);

        try {
            $stock = StockProducto::findOrFail($request->stock_id);
            
            $stock->update([
                'stock_minimo' => $request->stock_minimo,
                'stock_maximo' => $request->stock_maximo,
                'ubicacion' => $request->ubicacion,
                'alerta_stock_bajo' => $request->alerta_stock_bajo ?? true,
                'notas' => $request->notas
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    // Ver historial de movimientos
    public function historial(Request $request)
    {
        if ($request->ajax()) {
            $query = MovimientoStock::with(['usuario', 'producto', 'variante'])
                ->select('movimientos_stock.*');

            if ($request->filled('producto_id')) {
                $query->where('producto_id', $request->producto_id);
            }
            if ($request->filled('tipo')) {
                $query->where('tipo_movimiento', $request->tipo);
            }
            if ($request->filled('origen')) {
                $query->where('origen', $request->origen);
            }
            if ($request->filled('desde')) {
                $query->whereDate('created_at', '>=', $request->desde);
            }
            if ($request->filled('hasta')) {
                $query->whereDate('created_at', '<=', $request->hasta);
            }

            return DataTables::of($query)
                ->addColumn('fecha', fn($m) => $m->created_at->format('d/m/Y H:i'))
                ->addColumn('producto', function($m) {
                    $info = '<strong>' . e($m->producto?->referencia ?? '—') . '</strong><br>';
                    $info .= e($m->producto?->nombre ?? '');
                    if ($m->variante) {
                        $info .= '<br><small class="text-muted">' . e($m->variante->nombre_variante) . '</small>';
                    }
                    return $info;
                })
                ->addColumn('tipo', function($m) {
                    return '<span class="badge bg-' . $m->color_movimiento . '">'
                        . '<i class="' . $m->icono_movimiento . '"></i> '
                        . ucfirst($m->tipo_movimiento)
                        . '</span>';
                })
                ->addColumn('cantidad_fmt', function($m) {
                    $signo = match ($m->tipo_movimiento) {
                        'entrada' => '+',
                        'salida'  => '-',
                        default   => '',
                    };
                    $clase = 'text-' . $m->color_movimiento;
                    return '<span class="' . $clase . ' fw-bold">' . $signo . $m->cantidad . '</span>';
                })
                ->addColumn('stocks', fn($m) => $m->stock_anterior . ' → <strong>' . $m->stock_nuevo . '</strong>')
                ->addColumn('origen_fmt', fn($m) => $m->descripcion_origen)
                ->addColumn('motivo_fmt', fn($m) => $m->motivo ? e(\Illuminate\Support\Str::limit($m->motivo, 60)) : '-')
                ->addColumn('usuario_fmt', fn($m) => e($m->usuario?->name ?? '-'))
                ->filterColumn('producto', function($q, $keyword) {
                    $q->whereHas('producto', function($p) use ($keyword) {
                        $p->where('referencia', 'like', "%{$keyword}%")
                          ->orWhere('nombre', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['producto', 'tipo', 'cantidad_fmt', 'stocks'])
                ->make(true);
        }

        $productos = Producto::orderBy('referencia')->get(['id', 'referencia', 'nombre']);
        return view('stock.historial', compact('productos'));
    }

    // Obtener datos de stock para edición
    public function obtenerStock($id)
    {
        $stock = StockProducto::with(['producto', 'variante'])->findOrFail($id);
        
        return response()->json([
            'stock' => $stock,
            'producto_nombre' => $stock->producto->nombre,
            'variante_nombre' => $stock->variante ? $stock->variante->nombre_variante : null
        ]);
    }

    // Dashboard de stock
    public function dashboard()
    {
        // Estadísticas generales
        $totalProductos = Producto::where('controlar_stock', true)->count();
        $productosConStock = StockProducto::conStock()->count();
        $productosSinStock = StockProducto::sinStock()->count();
        $productosStockBajo = StockProducto::conStockBajo()->count();
        
        // Valor total del inventario (necesitaría precio de costo)
        $valorInventario = 0; // Implementar si se tiene precio de costo
        
        // Movimientos del mes
        $movimientosMes = MovimientoStock::delMes()->get();
        $entradasMes = $movimientosMes->where('tipo_movimiento', 'entrada')->sum('cantidad');
        $salidasMes = $movimientosMes->where('tipo_movimiento', 'salida')->sum('cantidad');
        
        // Productos con mayor rotación
        $productosTopRotacion = DB::table('movimientos_stock')
            ->select('producto_id', DB::raw('SUM(cantidad) as total_movimiento'))
            ->where('tipo_movimiento', 'salida')
            ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
            ->groupBy('producto_id')
            ->orderBy('total_movimiento', 'desc')
            ->limit(10)
            ->get();
        
        // Productos críticos (stock bajo)
        $productosCriticos = StockProducto::with(['producto', 'variante'])
            ->conStockBajo()
            ->orderBy('cantidad_disponible', 'asc')
            ->limit(10)
            ->get();
        
        return view('stock.dashboard', compact(
            'totalProductos',
            'productosConStock',
            'productosSinStock',
            'productosStockBajo',
            'valorInventario',
            'entradasMes',
            'salidasMes',
            'productosTopRotacion',
            'productosCriticos'
        ));
    }

    // Importar stock desde Excel
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls|max:10240'
        ]);

        // TODO: Implementar importación desde Excel
        // Formato esperado: Referencia | SKU_Variante | Cantidad | Stock_Minimo | Stock_Maximo | Ubicacion
        
        return back()->with('success', 'Stock importado correctamente.');
    }

    // Exportar inventario actual
    public function exportar()
    {
        // TODO: Implementar exportación a Excel
        
        return response()->download('inventario.xlsx');
    }

    // Reporte de movimientos
    public function reporteMovimientos(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ? Carbon::parse($request->fecha_inicio) : Carbon::now()->subMonth();
        $fechaFin = $request->fecha_fin ? Carbon::parse($request->fecha_fin) : Carbon::now();
        
        $movimientos = MovimientoStock::with(['producto', 'variante', 'usuario'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        
        if ($request->producto_id) {
            $movimientos->where('producto_id', $request->producto_id);
        }
        
        if ($request->tipo_movimiento) {
            $movimientos->where('tipo_movimiento', $request->tipo_movimiento);
        }
        
        $movimientos = $movimientos->orderBy('created_at', 'desc')->get();
        
        return view('stock.reporte_movimientos', compact('movimientos', 'fechaInicio', 'fechaFin'));
    }

    // Inicializar stock para todos los productos
    public function inicializarTodos()
    {
        DB::beginTransaction();
        try {
            $productos = Producto::where('controlar_stock', true)->get();
            
            foreach ($productos as $producto) {
                $producto->inicializarStock();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock inicializado para todos los productos'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al inicializar stock: ' . $e->getMessage()
            ], 500);
        }
    }

    // Obtener productos para selector (AJAX)
    public function productosJson(Request $request)
    {
        $query = Producto::where('controlar_stock', true);
        
        if ($request->has('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('referencia', 'like', "%{$search}%");
            });
        }
        
        $productos = $query->orderBy('nombre')
                           ->limit(50)
                           ->get()
                           ->map(function($producto) {
                               return [
                                   'id' => $producto->id,
                                   'text' => $producto->referencia . ' - ' . $producto->nombre . 
                                            ($producto->tiene_variantes ? ' (Con variantes)' : ''),
                                   'tiene_variantes' => $producto->tiene_variantes,
                                   'stock_total' => $producto->stock_total
                               ];
                           });
        
        return response()->json([
            'results' => $productos
        ]);
    }
}