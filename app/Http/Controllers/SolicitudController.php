<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SolicitudCotizacion;
use App\Models\Cliente;
use App\Models\StockProducto;
use App\Models\MovimientoStock;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SolicitudAplicada;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SolicitudesExport;
use Illuminate\Support\Facades\Log;

class SolicitudController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Devuelve cantidad de solicitudes pendientes para el usuario actual.
     * Usado por el badge del menú lateral (refresco JS).
     */
    public function pendientesCount()
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['admin', 'vendedor'])) {
            return response()->json(['count' => 0]);
        }

        $q = SolicitudCotizacion::pendientes();
        if ($user->hasRole('vendedor') && !$user->hasRole('admin')) {
            $q->whereHas('cliente', function ($c) use ($user) {
                $c->where('vendedor_id', $user->id);
            });
        }

        return response()->json(['count' => $q->count()]);
    }
    
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            
            $query = SolicitudCotizacion::with(['cliente', 'cliente.vendedor', 'items'])
                                       ->select('solicitudes_cotizacion.*');
            
            // Filtrar por rol
            if ($user->hasRole('vendedor')) {
                // Vendedor solo ve solicitudes de sus clientes
                $query->whereHas('cliente', function($q) use ($user) {
                    $q->where('vendedor_id', $user->id);
                });
            }
            // Admin ve todas las solicitudes (no se aplica filtro adicional)

            // Filtro por tipo de cotización. `sin_tipo` son las que no pasaron
            // por el alta rápida de prospectos (enlace, cliente ya existente, o
            // anteriores a que existiera el campo).
            $tipo = $request->input('tipo_cotizacion');
            if ($tipo === 'sin_tipo') {
                $query->whereNull('tipo_cotizacion');
            } elseif (filled($tipo) && array_key_exists($tipo, SolicitudCotizacion::TIPOS)) {
                $query->where('tipo_cotizacion', $tipo);
            }
            
            return DataTables::of($query)
                ->addColumn('cliente_nombre', function($s) {
                    return $s->cliente?->nombre_contacto ?? 'Cliente eliminado';
                })
                ->addColumn('vendedor', function($s) {
                    return $s->cliente?->vendedor?->name ?? 'Sin vendedor';
                })
                ->addColumn('fecha', function($s) {
                    return $s->created_at->format('d/m/Y H:i');
                })
                ->addColumn('tipo', function($s) {
                    return $s->tipo_cotizacion
                        ? '<span class="badge bg-info text-dark">'.e($s->tipoLabel()).'</span>'
                        : '<span class="text-muted">Sin tipo</span>';
                })
                ->addColumn('total_items', function($s) {
                    return $s->total_items;
                })
                ->addColumn('monto_formateado', function($s) {
                    return '$' . number_format($s->monto_total, 2);
                })
                ->addColumn('estado_badge', function($s) {
                    $class = $s->estado === 'pendiente' ? 'warning' : 'success';
                    $text = $s->estado === 'pendiente' ? 'Pendiente' : 'Aplicada';
                    return '<span class="badge bg-'.$class.'">'.$text.'</span>';
                })
                ->addColumn('action', function($s) {
                    $buttons = '<div class="d-flex justify-content-center gap-1">';
                    
                    // Botón ver detalle
                    $buttons .= '<button type="button" class="btn btn-outline-info btn-sm" 
                                        title="Ver Detalle" onclick="verDetalle('.$s->id.')">
                                   <i class="bi bi-eye"></i>
                                </button>';
                    
                    // Botón aplicar (solo si está pendiente)
                    if ($s->estado === 'pendiente') {
                        $buttons .= '<button type="button" class="btn btn-outline-success btn-sm" 
                                            title="Marcar como Aplicada" onclick="marcarAplicada('.$s->id.')">
                                       <i class="bi bi-check-circle"></i>
                                    </button>';
                    }
                    
                    // Botón descargar PDF
                    $buttons .= '<a href="'.route('solicitudes.pdf', $s->id).'" class="btn btn-outline-danger btn-sm" 
                                    title="Descargar PDF" target="_blank">
                                   <i class="bi bi-file-earmark-pdf"></i>
                                </a>';
                    
                    $buttons .= '</div>';
                    
                    return $buttons;
                })
                ->filterColumn('cliente_nombre', function($query, $keyword) {
                    $query->whereHas('cliente', function($q) use ($keyword) {
                        $q->where('nombre_contacto', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('vendedor', function($query, $keyword) {
                    $query->whereHas('cliente.vendedor', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['tipo', 'estado_badge', 'action'])
                ->make(true);
        }
        
        return view('solicitudes.solicitudes_index', [
            'tipos' => SolicitudCotizacion::TIPOS,
        ]);
    }
    
    public function detalle(SolicitudCotizacion $solicitud)
    {
        // Verificar permisos
        $user = Auth::user();
        if ($user->hasRole('vendedor') && $solicitud->cliente?->vendedor_id !== $user->id) {
            return response()->json(['error' => 'No tiene permisos para ver esta solicitud'], 403);
        }

        $solicitud->load(['cliente', 'cliente.listaPrecio', 'items.producto', 'items.varianteProducto', 'enlaceAcceso']);

        $html = '<div class="row">';

        // Información del cliente
        $html .= '<div class="col-md-6">';
        $html .= '<h6>Información del Cliente</h6>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Cliente:</strong></td><td>' . ($solicitud->cliente?->nombre_contacto ?? 'Cliente eliminado') . '</td></tr>';
        $html .= '<tr><td><strong>Email:</strong></td><td>' . ($solicitud->cliente?->email ?? '—') . '</td></tr>';
        $html .= '<tr><td><strong>Teléfono:</strong></td><td>' . ($solicitud->cliente?->telefono ?? '—') . '</td></tr>';
        $html .= '<tr><td><strong>Lista de Precios:</strong></td><td>' . ($solicitud->cliente?->listaPrecio?->nombre ?? 'Sin lista') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // Información de la solicitud
        $html .= '<div class="col-md-6">';
        $html .= '<h6>Información de la Solicitud</h6>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Número:</strong></td><td><code>' . $solicitud->numero_solicitud . '</code></td></tr>';
        $html .= '<tr><td><strong>Fecha:</strong></td><td>' . $solicitud->created_at->format('d/m/Y H:i') . '</td></tr>';
        $html .= '<tr><td><strong>Tipo:</strong></td><td>';
        $html .= $solicitud->tipo_cotizacion
            ? '<span class="badge bg-info text-dark">' . e($solicitud->tipoLabel()) . '</span>'
            : '<span class="text-muted">Sin tipo</span>';
        $html .= '</td></tr>';
        $html .= '<tr><td><strong>Estado:</strong></td><td>';
        if ($solicitud->estado === 'pendiente') {
            $html .= '<span class="badge bg-warning">Pendiente</span>';
        } else {
            $html .= '<span class="badge bg-success">Aplicada</span>';
        }
        $html .= '</td></tr>';
        
        if ($solicitud->enlaceAcceso) {
            $html .= '<tr><td><strong>Origen:</strong></td><td>Enlace de Acceso</td></tr>';
        } else {
            $html .= '<tr><td><strong>Origen:</strong></td><td>Tienda a Tienda</td></tr>';
        }
        
        if ($solicitud->estado === 'aplicada') {
            $html .= '<tr><td><strong>Aplicada por:</strong></td><td>' . $solicitud->aplicadaPor?->name . '</td></tr>';
            $html .= '<tr><td><strong>Fecha aplicación:</strong></td><td>' . $solicitud->aplicada_en->format('d/m/Y H:i') . '</td></tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        // Notas del cliente
        if ($solicitud->notas_cliente) {
            $html .= '<div class="col-12 mb-3">';
            $html .= '<h6>Notas del Cliente</h6>';
            $html .= '<div class="alert alert-info">' . nl2br(e($solicitud->notas_cliente)) . '</div>';
            $html .= '</div>';
        }
        
        // Items de la solicitud
        $html .= '<div class="col-12">';
        $html .= '<h6>Productos Solicitados</h6>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Referencia</th>';
        $html .= '<th>Producto</th>';
        $html .= '<th>Variante</th>';
        $html .= '<th>Cantidad</th>';
        $html .= '<th>Precio Unit.</th>';
        $html .= '<th>Subtotal</th>';
        if ($solicitud->estado === 'pendiente') {
            $html .= '<th>Stock Disponible</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($solicitud->items as $item) {
            $html .= '<tr>';
            $html .= '<td><code>' . $item->referencia_producto . '</code></td>';
            $html .= '<td>' . $item->nombre_producto . '</td>';
            $html .= '<td>' . ($item->info_variante ?: '-') . '</td>';
            $html .= '<td>' . $item->cantidad . '</td>';
            $html .= '<td>$' . number_format($item->precio_unitario, 2) . '</td>';
            $html .= '<td>$' . number_format($item->precio_total, 2) . '</td>';
            
            // Mostrar stock disponible solo si está pendiente
            if ($solicitud->estado === 'pendiente') {
                $stockInfo = $this->obtenerStockItem($item);
                $html .= '<td>' . $stockInfo . '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '<tfoot>';
        $html .= '<tr>';
        $colspanTotal = ($solicitud->estado === 'pendiente') ? 6 : 5;
        $html .= '<th colspan="' . $colspanTotal . '" class="text-end">Total:</th>';
        $html .= '<th>$' . number_format($solicitud->monto_total, 2) . '</th>';
        $html .= '</tr>';
        $html .= '</tfoot>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Observaciones del admin (si está aplicada)
        if ($solicitud->estado === 'aplicada' && $solicitud->observaciones_admin) {
            $html .= '<div class="col-12 mt-3">';
            $html .= '<h6>Observaciones del Administrador</h6>';
            $html .= '<div class="alert alert-secondary">' . nl2br(e($solicitud->observaciones_admin)) . '</div>';
            $html .= '</div>';
        }
        
        // Campo de observaciones si está pendiente
        if ($solicitud->estado === 'pendiente') {
            $html .= '<div class="col-12 mt-3">';
            $html .= '<hr>';
            $html .= '<div class="mb-3">';
            $html .= '<label class="form-label">Observaciones del Administrador</label>';
            $html .= '<textarea class="form-control" id="observacionesAdmin" rows="3" 
                              placeholder="Ingrese cualquier observación sobre esta solicitud..."></textarea>';
            $html .= '</div>';
            $html .= '<div class="mb-3">';
            $html .= '<div class="form-check">';
            $html .= '<input class="form-check-input" type="checkbox" id="procesarStock" checked>';
            $html .= '<label class="form-check-label" for="procesarStock">';
            $html .= '<strong>Procesar Stock:</strong> Descontar automáticamente del inventario';
            $html .= '</label>';
            $html .= '</div>';
            $html .= '<small class="text-muted">Si está marcado, se descontará el stock de los productos que lo controlen.</small>';
            $html .= '</div>';
            $html .= '<button type="button" class="btn btn-success w-100" onclick="confirmarAplicar(' . $solicitud->id . ')">
                        <i class="bi bi-check-circle"></i> Marcar como Aplicada
                      </button>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return response($html);
    }

    /**
     * Obtener información de stock para un item
     */
    private function obtenerStockItem($item)
    {
        $producto = $item->producto;
        
        // Si no controla stock
        if (!$producto->controlar_stock) {
            return '<span class="badge bg-success">Stock ilimitado</span>';
        }
        
        if ($item->variante_producto_id) {
            // Producto con variante
            $stock = StockProducto::where('producto_id', $producto->id)
                                  ->where('variante_producto_id', $item->variante_producto_id)
                                  ->first();
        } else {
            // Producto sin variante
            $stock = StockProducto::where('producto_id', $producto->id)
                                  ->whereNull('variante_producto_id')
                                  ->first();
        }
        
        if (!$stock) {
            if ($producto->permitir_venta_sin_stock) {
                return '<span class="badge bg-warning">Sin stock (se permite)</span>';
            } else {
                return '<span class="badge bg-danger">Sin stock</span>';
            }
        }
        
        $disponible = $stock->cantidad_disponible - $stock->cantidad_reservada;
        $solicitado = $item->cantidad;
        
        if ($disponible >= $solicitado) {
            return '<span class="badge bg-success">' . $disponible . ' disponibles</span>';
        } elseif ($producto->permitir_venta_sin_stock) {
            return '<span class="badge bg-warning">' . $disponible . ' disponibles (se permite déficit)</span>';
        } else {
            return '<span class="badge bg-danger">Insuficiente (' . $disponible . ' de ' . $solicitado . ')</span>';
        }
    }
    
    public function aplicar(Request $request, SolicitudCotizacion $solicitud)
    {
        // Verificar permisos
        $user = Auth::user();
        if ($user->hasRole('vendedor') && $solicitud->cliente?->vendedor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No tiene permisos para aplicar esta solicitud'
            ], 403);
        }
        
        // Verificar que esté pendiente
        if ($solicitud->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'mensaje' => 'Esta solicitud ya fue aplicada'
            ], 400);
        }
        
        $request->validate([
            'observaciones' => 'nullable|string|max:1000',
            'procesar_stock' => 'boolean'
        ]);
        
        DB::beginTransaction();
        
        try {
            $procesarStock = $request->boolean('procesar_stock', true);
            $stockProcesado = [];
            $stockInsuficiente = [];
            
            // Procesar stock si se solicita
            if ($procesarStock) {
                foreach ($solicitud->items as $item) {
                    $resultado = $this->procesarStockItem($item, $user->id, $solicitud->id);
                    
                    if ($resultado['procesado']) {
                        $stockProcesado[] = $resultado['mensaje'];
                    } elseif ($resultado['error']) {
                        $stockInsuficiente[] = $resultado['mensaje'];
                    }
                }
                
                // Si hay stock insuficiente y no se permite venta sin stock, fallar
                if (!empty($stockInsuficiente)) {
                    $errorMsg = "No se puede procesar la solicitud por stock insuficiente:\n" . implode("\n", $stockInsuficiente);
                    throw new \Exception($errorMsg);
                }
            }
            
            // Marcar como aplicada
            $observaciones = $request->observaciones;
            if ($procesarStock && !empty($stockProcesado)) {
                $observaciones .= "\n\nMovimientos de stock procesados:\n" . implode("\n", $stockProcesado);
            }
            
            $solicitud->marcarComoAplicada($user->id, $observaciones);
            
            // Cargar relaciones necesarias para el PDF
            $solicitud->load([
                'cliente',
                'cliente.listaPrecio',
                'cliente.vendedor',
                'items.producto.imagenPrincipal',
                'aplicadaPor'
            ]);

            // Generar PDF
            $pdf = PDF::loadView('pdf.solicitud-cotizacion', compact('solicitud'));
            $pdf->setPaper('letter', 'portrait');
            
            // Enviar email con PDF adjunto
            try {
                if (!$solicitud->cliente?->email) {
                    throw new \RuntimeException('Cliente sin correo (puede estar eliminado).');
                }
                Mail::to($solicitud->cliente->email)
                    ->send(new SolicitudAplicada($solicitud, $pdf));
                    
                $mensajeEmail = ' Se ha enviado el PDF por correo electrónico al cliente.';
            } catch (\Exception $e) {
                // Log del error pero no fallar la aplicación
                Log::error('Error al enviar email de solicitud aplicada: ' . $e->getMessage());
                $mensajeEmail = ' (No se pudo enviar el correo: ' . $e->getMessage() . ')';
            }
            
            DB::commit();
            
            $mensaje = 'Solicitud marcada como aplicada exitosamente.';
            if ($procesarStock) {
                $mensaje .= ' Stock procesado correctamente.';
            }
            $mensaje .= $mensajeEmail;
            
            return response()->json([
                'success' => true,
                'mensaje' => $mensaje
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al aplicar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Procesar stock de un item individual
     */
    private function procesarStockItem($item, $usuarioId, $solicitudId)
    {
        $producto = $item->producto;
        
        // Si no controla stock, no hacer nada
        if (!$producto->controlar_stock) {
            return [
                'procesado' => false,
                'error' => false,
                'mensaje' => $producto->nombre . ' - No controla stock'
            ];
        }
        
        // Buscar registro de stock
        if ($item->variante_producto_id) {
            $stock = StockProducto::where('producto_id', $producto->id)
                                  ->where('variante_producto_id', $item->variante_producto_id)
                                  ->first();
            $descripcion = $producto->nombre . ' - ' . $item->info_variante;
        } else {
            $stock = StockProducto::where('producto_id', $producto->id)
                                  ->whereNull('variante_producto_id')
                                  ->first();
            $descripcion = $producto->nombre;
        }
        
        if (!$stock) {
            // Si no existe registro de stock, crearlo
            $stock = StockProducto::create([
                'producto_id' => $producto->id,
                'variante_producto_id' => $item->variante_producto_id,
                'cantidad_disponible' => 0,
                'cantidad_reservada' => 0,
                'stock_minimo' => 0,
                'alerta_stock_bajo' => true
            ]);
        }
        
        $stockAnterior = $stock->cantidad_disponible;
        $cantidadSolicitada = $item->cantidad;
        $stockResultante = $stockAnterior - $cantidadSolicitada;
        
        // Verificar si se puede procesar
        if ($stockResultante < 0 && !$producto->permitir_venta_sin_stock) {
            return [
                'procesado' => false,
                'error' => true,
                'mensaje' => $descripcion . ' - Stock insuficiente (disponible: ' . $stockAnterior . ', solicitado: ' . $cantidadSolicitada . ')'
            ];
        }
        
        // Procesar la salida
        $resultado = $stock->salida(
            $cantidadSolicitada,
            'venta',
            $item->solicitudCotizacion->numero_solicitud,
            'Venta aplicada desde solicitud de cotización'
        );
        
        if (!$resultado && !$producto->permitir_venta_sin_stock) {
            return [
                'procesado' => false,
                'error' => true,
                'mensaje' => $descripcion . ' - Error al procesar salida de stock'
            ];
        }
        
        // Si permite venta sin stock y falló la salida normal, hacer ajuste manual
        if (!$resultado && $producto->permitir_venta_sin_stock) {
            $stock->update(['cantidad_disponible' => $stockResultante]);
            
            // Crear movimiento manual
            MovimientoStock::create([
                'producto_id' => $producto->id,
                'variante_producto_id' => $item->variante_producto_id,
                'tipo_movimiento' => 'salida',
                'cantidad' => $cantidadSolicitada,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockResultante,
                'referencia_documento' => $item->solicitudCotizacion->numero_solicitud,
                'origen' => 'venta',
                'motivo' => 'Venta aplicada desde solicitud de cotización (permite stock negativo)',
                'usuario_id' => $usuarioId,
                'solicitud_cotizacion_id' => $solicitudId
            ]);
        }
        
        return [
            'procesado' => true,
            'error' => false,
            'mensaje' => $descripcion . ' - Descontado: ' . $cantidadSolicitada . ' unidades (stock resultante: ' . $stockResultante . ')'
        ];
    }
    
    /**
     * Descargar PDF de solicitud
     */
    public function descargarPdf(SolicitudCotizacion $solicitud)
    {
        // Verificar permisos
        $user = Auth::user();
        if ($user->hasRole('vendedor') && $solicitud->cliente?->vendedor_id !== $user->id) {
            abort(403, 'No tiene permisos para descargar este PDF');
        }
        
        // Cargar relaciones necesarias
        $solicitud->load([
            'cliente',
            'cliente.listaPrecio',
            'cliente.vendedor',
            'items.producto.imagenPrincipal',
            'aplicadaPor'
        ]);
        
        $pdf = PDF::loadView('pdf.solicitud-cotizacion', compact('solicitud'));
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->download($solicitud->nombre_archivo_pdf . '.pdf');
    }
    
    /**
     * Exportar solicitudes a Excel
     */
    public function exportarExcel(Request $request)
    {
        $user = Auth::user();
        
        // Aplicar filtros según el rol
        $query = SolicitudCotizacion::with([
            'cliente', 
            'cliente.vendedor', 
            'items.producto',
            'items.varianteProducto',
            'aplicadaPor'
        ]);
        
        if ($user->hasRole('vendedor')) {
            $query->whereHas('cliente', function($q) use ($user) {
                $q->where('vendedor_id', $user->id);
            });
        }
        
        // Filtros opcionales
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->has('fecha_desde') && $request->fecha_desde) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        
        if ($request->has('fecha_hasta') && $request->fecha_hasta) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        
        $solicitudes = $query->orderBy('created_at', 'desc')->get();
        
        return Excel::download(
            new SolicitudesExport($solicitudes), 
            'solicitudes-cotizacion-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }
}