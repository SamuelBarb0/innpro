<?php

namespace App\Http\Controllers;
use Excel;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\PrecioProducto;
use App\Models\ActualizacionPrecio;
use App\Imports\PreciosImport;
use App\Imports\ProductosImport;
use App\Exports\PlantillaProductosExport;
use App\Models\ListaPrecio;
use App\Models\VarianteProducto;
use App\Models\StockProducto;
use App\Models\MovimientoStock;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProductosController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Producto::with(['categoria', 'imagenPrincipal', 'stockPrincipal'])
                            ->select('productos.*');

            return DataTables::of($query)
                ->addColumn('categoria', fn($p) => $p->categoria?->nombre)
                ->addColumn('imagen', function($p) {
                    $url = $p->imagenPrincipal 
                        ? asset($p->imagenPrincipal->ruta_imagen)
                        : asset('images/sin-imagen.png');
                    return '<img src="'.$url.'" class="img-thumbnail" style="width:50px;">';
                })
                ->addColumn('stock', function($p) {
                    if (!$p->controlar_stock) {
                        return '<span class="badge bg-secondary">No controlado</span>';
                    }
                    
                    $stockDisponible = $p->stock_disponible;
                    $badge = 'success';
                    
                    if ($stockDisponible <= 0) {
                        $badge = 'danger';
                    } elseif ($p->tiene_stock_bajo) {
                        $badge = 'warning';
                    }
                    
                    return '<span class="badge bg-'.$badge.'">' . $stockDisponible . '</span>';
                })
                ->addColumn('tiene_extension', fn($p) => $p->tiene_extension ? 'Sí' : 'No')
                ->addColumn('variantes', fn($p) => $p->tiene_variantes ? 'Sí' : 'No')
                ->addColumn('estado', function($p) {
                    return $p->activo
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-secondary">Inactivo</span>';
                })
                ->addColumn('action', function($p) {
                    $editUrl   = route('productos.form', $p->id);
                    $toggleUrl = route('productos.toggle-activo', $p->id);
                    $deleteUrl = route('productos.eliminar', $p->id);
                    $csrf      = csrf_token();

                    $toggleIcon  = $p->activo ? 'bi-toggle-on' : 'bi-toggle-off';
                    $toggleClass = $p->activo ? 'btn-outline-warning' : 'btn-outline-success';
                    $toggleTitle = $p->activo ? 'Inactivar' : 'Activar';

                    $buttons = '<div class="d-flex justify-content-center gap-1">';
                    $buttons .= '<a href="'.$editUrl.'" class="btn btn-outline-info btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>';

                    if ($p->tiene_variantes) {
                        $buttons .= '<button type="button" class="btn btn-outline-secondary btn-sm" title="Ver Variantes" onclick="verVariantes('.$p->id.')"><i class="bi bi-list-ul"></i></button>';
                    }

                    $buttons .= '<button type="button" class="btn btn-outline-primary btn-sm" title="Ver Imágenes" onclick="verImagenes('.$p->id.')"><i class="bi bi-image"></i></button>';
                    $buttons .= '<button type="button" class="btn btn-outline-success btn-sm" title="Ver Precios" onclick="verPrecios('.$p->id.')"><i class="bi bi-currency-dollar"></i></button>';

                    if ($p->controlar_stock) {
                        $buttons .= '<button type="button" class="btn btn-outline-warning btn-sm" title="Ver Stock" onclick="verStock('.$p->id.')"><i class="bi bi-box-seam"></i></button>';
                    }

                    $buttons .= '<form method="POST" action="'.$toggleUrl.'" style="display:inline">';
                    $buttons .= '<input type="hidden" name="_token" value="'.$csrf.'">';
                    $buttons .= '<button type="submit" class="btn '.$toggleClass.' btn-sm" title="'.$toggleTitle.'"><i class="bi '.$toggleIcon.'"></i></button>';
                    $buttons .= '</form>';

                    $buttons .= '<form method="POST" action="'.$deleteUrl.'" style="display:inline" onsubmit="return confirm(\'¿Eliminar este producto? Se conservarán sus relaciones (cotizaciones, movimientos de stock, etc).\');">';
                    $buttons .= '<input type="hidden" name="_token" value="'.$csrf.'">';
                    $buttons .= '<input type="hidden" name="_method" value="DELETE">';
                    $buttons .= '<button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>';
                    $buttons .= '</form>';

                    $buttons .= '</div>';

                    return $buttons;
                })
                ->rawColumns(['imagen', 'stock', 'estado', 'action'])
                ->make(true);
        }

        return view('productos.productos_index');
    }

    public function form(Producto $producto = null)
    {
        $producto = $producto ?? new Producto();
        $categorias = Categoria::activas()->pluck('nombre', 'id');
        $listas = ListaPrecio::activas()->get();
        
        // Cargar stock si el producto existe (NUEVO)
        $stocks = [];
        if ($producto->exists) {
            if ($producto->tiene_variantes) {
                $stocks = $producto->stock()->with('variante')->get();
            } else {
                $stock = $producto->stockPrincipal;
                if ($stock) {
                    $stocks = [$stock];
                }
            }
        }
        
        return view('productos.productos_form', compact('producto', 'categorias', 'listas', 'stocks'));
    }

    public function guardar(Request $request)
    {
        $producto = $request->id
                  ? Producto::findOrFail($request->id)
                  : new Producto();

        $rules = [
            'referencia' => [
                'required','string','max:255',
                Rule::unique('productos')->ignore($producto->id)
            ],
            'nombre' => ['required','string','max:255'],
            'descripcion' => ['nullable','string'],
            'unidad_venta' => ['required','string','max:100'],
            'unidad_empaque' => ['required','string','max:100'],
            'peso_paca' => ['nullable','numeric','min:0'],
            'cubicaje_paca' => ['nullable','numeric','min:0'],
            'unidades_por_paca' => ['nullable','numeric','min:0'],
            'codigo_barras' => ['nullable','string','max:100'],
            'tiene_extension' => ['nullable','boolean'],
            'categoria_id' => ['required','exists:categorias,id'],
            'activo' => ['nullable','boolean'],
            'controlar_stock' => ['boolean'],  // NUEVO
            'permitir_venta_sin_stock' => ['boolean'],  // NUEVO
            'imagenes.*' => ['nullable','image','mimes:jpeg,png,jpg,webp','max:2048'],
            'variantes.*.extension' => ['nullable','string','max:100'],
            'variantes.*.sku' => ['nullable','string','max:255'],
            'variantes.*.stock_inicial' => ['nullable','integer','min:0'],  // NUEVO
            'variantes.*.stock_minimo' => ['nullable','integer','min:0'],  // NUEVO
            'variantes.*.stock_maximo' => ['nullable','integer','min:0'],  // NUEVO
            'variantes.*.ubicacion' => ['nullable','string','max:255'],  // NUEVO
            'precios.*' => ['nullable','numeric','min:0'],
            'stock_inicial' => ['nullable','integer','min:0'],  // NUEVO
            'stock_minimo' => ['nullable','integer','min:0'],  // NUEVO
            'stock_maximo' => ['nullable','integer','min:0'],  // NUEVO
            'ubicacion_stock' => ['nullable','string','max:255'],  // NUEVO
        ];

        $messages = [
            'required' => 'Este campo es obligatorio.',
            'max' => 'No debe superar los :max caracteres.',
            'unique' => 'Ya existe un producto con esta referencia.',
            'exists' => 'La categoría seleccionada no es válida.',
            'imagenes.*.image' => 'El archivo debe ser una imagen.',
            'imagenes.*.mimes' => 'La imagen debe ser JPG, PNG o WebP.',
            'imagenes.*.max' => 'La imagen no debe superar 2MB.',
            'precios.*.numeric' => 'El precio debe ser un número.',
            'precios.*.min' => 'El precio no puede ser negativo.',
            'stock_inicial.integer' => 'El stock debe ser un número entero.',  // NUEVO
            'stock_inicial.min' => 'El stock no puede ser negativo.',  // NUEVO
        ];

        $data = $request->validate($rules, $messages);
        
        DB::beginTransaction();
        
        try {
            // Guardar datos básicos del producto
            $data['tiene_variantes'] = $request->input('tiene_variantes', 0) == 1;
            $data['tiene_extension'] = $request->input('tiene_extension', 0) == 1;
            $data['controlar_stock'] = $request->input('controlar_stock', 1) == 1;  // NUEVO
            $data['permitir_venta_sin_stock'] = $request->input('permitir_venta_sin_stock', 0) == 1;  // NUEVO
            $data['activo'] = $producto->exists ? $request->boolean('activo') : true;
            
            $esNuevo = !$producto->exists;  // NUEVO
            $producto->fill($data)->save();
            
            // Guardar variantes
            if ($producto->tiene_variantes && $request->has('variantes')) {
                // Si es edición, eliminar variantes anteriores
                if ($request->id) {
                    // Eliminar stock de variantes eliminadas (NUEVO)
                    $variantesIds = $producto->variantes()->pluck('id');
                    StockProducto::whereIn('variante_producto_id', $variantesIds)
                                 ->where('producto_id', $producto->id)
                                 ->delete();
                    $producto->variantes()->delete();
                }
                
                foreach ($request->variantes as $index => $varianteData) {
                    $extension = $varianteData['extension'] ?? null;
                    if (!empty($extension) || !empty($varianteData['sku'])) {
                        // Generar SKU si no se proporciona
                        $sku = $varianteData['sku'] ?? null;
                        if (empty($sku)) {
                            $sku = $producto->referencia;
                            if (!empty($extension)) {
                                $sku .= '-' . strtoupper(str_replace(' ', '', $extension));
                            } else {
                                $count = $producto->variantes()->count() + 1;
                                $sku .= '-VAR' . $count;
                            }
                        }

                        $variante = $producto->variantes()->create([
                            'extension' => $extension,
                            'sku' => $sku,
                            'activo' => true
                        ]);
                        
                        // Crear registro de stock para la variante si se controla stock (NUEVO)
                        if ($producto->controlar_stock) {
                            $stockInicial = (int) ($varianteData['stock_inicial'] ?? 0);
                            $stockMinimoVar = (int) ($varianteData['stock_minimo'] ?? 0);
                            $stockMaximoVar = isset($varianteData['stock_maximo']) && $varianteData['stock_maximo'] !== '' && $varianteData['stock_maximo'] !== null
                                ? (int) $varianteData['stock_maximo']
                                : null;
                            $stock = StockProducto::create([
                                'producto_id' => $producto->id,
                                'variante_producto_id' => $variante->id,
                                'cantidad_disponible' => $stockInicial,
                                'cantidad_reservada' => 0,
                                'stock_minimo' => $stockMinimoVar,
                                'stock_maximo' => $stockMaximoVar,
                                'ubicacion' => $varianteData['ubicacion'] ?? null,
                                'alerta_stock_bajo' => true
                            ]);
                            
                            // Registrar movimiento inicial si hay stock
                            if ($stockInicial > 0) {
                                MovimientoStock::create([
                                    'producto_id' => $producto->id,
                                    'variante_producto_id' => $variante->id,
                                    'tipo_movimiento' => 'entrada',
                                    'cantidad' => $stockInicial,
                                    'stock_anterior' => 0,
                                    'stock_nuevo' => $stockInicial,
                                    'origen' => 'ajuste_inventario',
                                    'motivo' => 'Stock inicial',
                                    'usuario_id' => auth()->id() ?? 1
                                ]);
                            }
                        }
                    }
                }
            } elseif ($producto->controlar_stock && !$producto->tiene_variantes) {
                // Producto sin variantes - crear o actualizar stock principal (NUEVO)
                $stockInicial = (int) ($request->input('stock_inicial') ?? 0);
                $stockMinimo  = (int) ($request->input('stock_minimo') ?? 0);
                $stockMaximoRaw = $request->input('stock_maximo');
                $stockMaximo  = ($stockMaximoRaw === null || $stockMaximoRaw === '')
                    ? null
                    : (int) $stockMaximoRaw;
                $ubicacion    = $request->input('ubicacion_stock');

                $stock = StockProducto::firstOrNew([
                    'producto_id' => $producto->id,
                    'variante_producto_id' => null
                ]);

                // Si es nuevo o si cambió el stock
                if (!$stock->exists || ($esNuevo && $stockInicial > 0)) {
                    $stock->fill([
                        'cantidad_disponible' => $esNuevo ? $stockInicial : (int) ($stock->cantidad_disponible ?? 0),
                        'cantidad_reservada' => (int) ($stock->cantidad_reservada ?? 0),
                        'stock_minimo' => $stockMinimo,
                        'stock_maximo' => $stockMaximo,
                        'ubicacion' => $ubicacion,
                        'alerta_stock_bajo' => true
                    ])->save();

                    // Registrar movimiento si es nuevo con stock inicial
                    if ($esNuevo && $stockInicial > 0) {
                        MovimientoStock::create([
                            'producto_id' => $producto->id,
                            'variante_producto_id' => null,
                            'tipo_movimiento' => 'entrada',
                            'cantidad' => $stockInicial,
                            'stock_anterior' => 0,
                            'stock_nuevo' => $stockInicial,
                            'origen' => 'ajuste_inventario',
                            'motivo' => 'Stock inicial',
                            'usuario_id' => auth()->id() ?? 1
                        ]);
                    }
                } else {
                    // Solo actualizar configuración
                    $stock->update([
                        'stock_minimo' => $stockMinimo,
                        'stock_maximo' => $stockMaximo,
                        'ubicacion' => $ubicacion
                    ]);
                }
            }
            
            // Guardar imágenes nuevas
            if ($request->hasFile('imagenes')) {
                $directory = public_path('imagenes/productos/' . $producto->id);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                
                $orden = $producto->imagenes()->max('orden') ?? 0;
                $imagenPrincipalNueva = $request->input('imagen_principal_nueva', 0);
                
                foreach ($request->file('imagenes') as $index => $imagen) {
                    $filename = time() . '_' . uniqid() . '_' . $imagen->getClientOriginalName();
                    $imagen->move($directory, $filename);
                    $path = 'imagenes/productos/' . $producto->id . '/' . $filename;
                    
                    $orden++;
                    $producto->imagenes()->create([
                        'ruta_imagen' => $path,
                        'texto_alternativo' => $producto->nombre,
                        'es_principal' => $index == $imagenPrincipalNueva,
                        'orden' => $orden
                    ]);
                }
            }
            
            // Actualizar imagen principal existente
            if ($request->has('imagen_principal_existente')) {
                // Quitar principal de todas
                $producto->imagenes()->update(['es_principal' => false]);
                // Establecer la nueva principal
                $producto->imagenes()
                        ->where('id', $request->imagen_principal_existente)
                        ->update(['es_principal' => true]);
            }
            
            // Eliminar imágenes marcadas
            if ($request->has('eliminar_imagenes')) {
                foreach ($request->eliminar_imagenes as $imagenId) {
                    $imagen = ImagenProducto::find($imagenId);
                    if ($imagen && $imagen->producto_id == $producto->id) {
                        // Eliminar archivo físico
                        $filePath = public_path($imagen->ruta_imagen);
                        if (File::exists($filePath)) {
                            File::delete($filePath);
                        }
                        $imagen->delete();
                    }
                }
            }
            
            // Guardar precios
            if ($request->has('precios')) {
                foreach ($request->precios as $listaId => $precio) {
                    if (!empty($precio)) {
                        $producto->precios()->updateOrCreate(
                            ['lista_precio_id' => $listaId],
                            ['precio' => $precio, 'activo' => true]
                        );
                    } else {
                        $producto->precios()
                                ->where('lista_precio_id', $listaId)
                                ->delete();
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('productos')
                           ->with('success', $request->id ? 'Producto actualizado correctamente.' : 'Producto creado correctamente.');
                           
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                         ->with('error', 'Error al guardar el producto: ' . $e->getMessage());
        }
    }

public function actualizarPreciosExcel(Request $request)
{
    // 1. Validación
    $request->validate([
        'archivo' => 'required|mimes:xlsx,xls,csv|max:10240'
    ], [
        'archivo.required' => 'Debe seleccionar un archivo',
        'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx, .xls) o CSV',
        'archivo.max'      => 'El archivo no debe superar los 10MB'
    ]);

    $archivo        = $request->file('archivo');
    $nombreOriginal = $archivo->getClientOriginalName();
    $nombreArchivo  = time() . '_' . $nombreOriginal;

    $rutaPublic = public_path('uploads/actualizaciones_precios');
    if (! File::exists($rutaPublic)) {
        File::makeDirectory($rutaPublic, 0755, true);
    }

    $archivo->move($rutaPublic, $nombreArchivo);
    $rutaArchivo = 'uploads/actualizaciones_precios/' . $nombreArchivo;

    $actualizacion = ActualizacionPrecio::create([
        'usuario_id'               => auth()->id(),
        'estado'                   => 'procesando',
        'nombre_archivo'           => $nombreOriginal,
        'ruta_archivo'             => $rutaArchivo,
        'total_filas'              => 0,
        'actualizaciones_exitosas' => 0,
        'actualizaciones_fallidas' => 0,
        'errores'                  => [],
        'detalles_procesados'      => []
    ]);

    Log::info('Iniciando actualización de precios', [
        'usuario'          => auth()->user()->name,
        'archivo'          => $nombreArchivo,
        'actualizacion_id' => $actualizacion->id,
    ]);

    try {
        $pathImport = public_path($rutaArchivo);
        Excel::import(new PreciosImport($actualizacion), $pathImport);

        $actualizacion->refresh();
        if ($actualizacion->actualizaciones_fallidas === 0 && $actualizacion->actualizaciones_exitosas > 0) {
            return back()->with('success', "Actualización completada: {$actualizacion->actualizaciones_exitosas} productos actualizados.")
                         ->with('actualizacion_id', $actualizacion->id);
        } elseif ($actualizacion->actualizaciones_exitosas > 0) {
            return back()->with('warning', "Actualización parcial: {$actualizacion->actualizaciones_exitosas} éxitosas, {$actualizacion->actualizaciones_fallidas} con errores.")
                         ->with('actualizacion_id', $actualizacion->id);
        } else {
            return back()->with('error', 'No se pudo actualizar ningún producto. Revisa el reporte de errores.')
                         ->with('actualizacion_id', $actualizacion->id);
        }
    } catch (\Throwable $e) {
        Log::error('Error en actualización de precios', [
            'actualizacion_id' => $actualizacion->id,
            'mensaje'          => $e->getMessage(),
            'archivo'          => $e->getFile(),
            'linea'            => $e->getLine(),
        ]);

        $actualizacion->update([
            'estado'  => 'error',
            'errores' => [[
                'fila'       => 0,
                'referencia' => '',
                'mensaje'    => 'Error general: ' . $e->getMessage(),
            ]],
        ]);

        return back()->with('error', 'Ocurrió un error procesando el archivo: ' . $e->getMessage())
                     ->with('actualizacion_id', $actualizacion->id);
    }
}


    // Métodos AJAX para los modales
    public function variantesAjax(Producto $producto)
    {
        $variantes = $producto->variantes()->get();
        
        $html = '<div class="table-responsive">';
        
        if ($variantes->isEmpty()) {
            $html .= '<p class="text-center text-muted">Este producto no tiene variantes configuradas.</p>';
        } else {
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>SKU</th><th>Extensión</th><th>Estado</th></tr></thead>';
            $html .= '<tbody>';

            foreach ($variantes as $variante) {
                $html .= '<tr>';
                $html .= '<td><code>' . $variante->sku . '</code></td>';
                $html .= '<td>' . ($variante->extension ?: '-') . '</td>';
                $html .= '<td>' . ($variante->activo ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>') . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        $html .= '</div>';
        
        return response($html);
    }

    public function imagenesAjax(Producto $producto)
    {
        $imagenes = $producto->imagenes()->orderBy('orden')->get();
        
        $html = '<div class="row">';
        
        if ($imagenes->isEmpty()) {
            $html .= '<p class="text-center text-muted">Este producto no tiene imágenes.</p>';
        } else {
            foreach ($imagenes as $imagen) {
                $html .= '<div class="col-md-3 mb-3">';
                $html .= '<div class="card">';
                $html .= '<img src="' . asset($imagen->ruta_imagen) . '" class="card-img-top" style="height: 200px; object-fit: cover;">';
                $html .= '<div class="card-body p-2 text-center">';
                
                if ($imagen->es_principal) {
                    $html .= '<span class="badge bg-success">Principal</span>';
                }
                
                $html .= '</div></div></div>';
            }
        }
        
        $html .= '</div>';
        
        return response($html);
    }

    public function preciosAjax(Producto $producto)
    {
        $precios = $producto->precios()->with('listaPrecio')->get();
        
        $html = '<div class="table-responsive">';
        
        if ($precios->isEmpty()) {
            $html .= '<p class="text-center text-muted">Este producto no tiene precios configurados.</p>';
        } else {
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>Lista de Precios</th><th>Código</th><th>Precio</th><th>Estado</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($precios as $precio) {
                $html .= '<tr>';
                $html .= '<td>' . $precio->listaPrecio->nombre . '</td>';
                $html .= '<td><code>' . $precio->listaPrecio->codigo . '</code></td>';
                $html .= '<td>$' . number_format($precio->precio, 2) . '</td>';
                $html .= '<td>' . ($precio->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>') . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        $html .= '</div>';
        
        return response($html);
    }

    // Método AJAX para ver stock (NUEVO)
    public function stockAjax(Producto $producto)
    {
        $stocks = $producto->stock()->with('variante')->get();
        
        $html = '<div class="table-responsive">';
        
        if ($stocks->isEmpty()) {
            $html .= '<p class="text-center text-muted">Este producto no tiene stock configurado.</p>';
        } else {
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>Producto/Variante</th><th>Disponible</th><th>Reservado</th><th>Stock Real</th><th>Mín/Máx</th><th>Ubicación</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($stocks as $stock) {
                $badge = 'success';
                if ($stock->stock_real <= 0) {
                    $badge = 'danger';
                } elseif ($stock->stock_bajo) {
                    $badge = 'warning';
                }
                
                $html .= '<tr>';
                $html .= '<td>' . ($stock->variante ? $stock->variante->nombre_variante : 'Principal') . '</td>';
                $html .= '<td>' . $stock->cantidad_disponible . '</td>';
                $html .= '<td>' . $stock->cantidad_reservada . '</td>';
                $html .= '<td><span class="badge bg-' . $badge . '">' . $stock->stock_real . '</span></td>';
                $html .= '<td>' . $stock->stock_minimo . '/' . ($stock->stock_maximo ?: '∞') . '</td>';
                $html .= '<td>' . ($stock->ubicacion ?: '-') . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        $html .= '</div>';

        return response($html);
    }

    public function mostrarImportar()
    {
        $listas = ListaPrecio::activas()->get(['codigo', 'nombre']);
        $ultimas = ActualizacionPrecio::orderBy('created_at', 'desc')->limit(5)->get();
        return view('productos.importar', compact('listas', 'ultimas'));
    }

    public function descargarPlantilla()
    {
        return Excel::download(new PlantillaProductosExport(), 'plantilla_productos_precios.xlsx');
    }

    public function importarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls,csv|max:10240'
        ], [
            'archivo.required' => 'Debe seleccionar un archivo',
            'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx, .xls) o CSV',
            'archivo.max'      => 'El archivo no debe superar los 10MB',
        ]);

        $archivo = $request->file('archivo');
        $nombreOriginal = $archivo->getClientOriginalName();
        $extension = strtolower($archivo->getClientOriginalExtension());

        $readerType = match ($extension) {
            'xlsx'  => \Maatwebsite\Excel\Excel::XLSX,
            'xls'   => \Maatwebsite\Excel\Excel::XLS,
            'csv'   => \Maatwebsite\Excel\Excel::CSV,
            default => null,
        };

        $actualizacion = ActualizacionPrecio::create([
            'usuario_id'               => auth()->id(),
            'estado'                   => 'procesando',
            'nombre_archivo'           => $nombreOriginal,
            'ruta_archivo'             => '(no almacenada — procesada en memoria)',
            'total_filas'              => 0,
            'actualizaciones_exitosas' => 0,
            'actualizaciones_fallidas' => 0,
            'errores'                  => [],
            'detalles_procesados'      => [],
        ]);

        try {
            Excel::import(new ProductosImport($actualizacion), $archivo, null, $readerType);

            $actualizacion->refresh();
            $ok = $actualizacion->actualizaciones_exitosas;
            $err = $actualizacion->actualizaciones_fallidas;
            if ($err === 0 && $ok > 0) {
                return back()->with('success', "Importación completada: {$ok} filas procesadas.")
                             ->with('actualizacion_id', $actualizacion->id);
            } elseif ($ok > 0) {
                return back()->with('warning', "Importación parcial: {$ok} exitosas, {$err} con errores.")
                             ->with('errores_import', $actualizacion->errores)
                             ->with('actualizacion_id', $actualizacion->id);
            }
            return back()->with('error', 'No se pudo procesar ninguna fila. Revisa los errores.')
                         ->with('errores_import', $actualizacion->errores)
                         ->with('actualizacion_id', $actualizacion->id);
        } catch (\Throwable $e) {
            Log::error('Error importando productos', [
                'actualizacion_id' => $actualizacion->id,
                'mensaje'          => $e->getMessage(),
                'archivo'          => $e->getFile(),
                'linea'            => $e->getLine(),
            ]);

            $actualizacion->update([
                'estado'  => 'error',
                'errores' => [[
                    'fila'       => 0,
                    'referencia' => '',
                    'mensaje'    => 'Error general: ' . $e->getMessage(),
                ]],
            ]);

            return back()->with('error', 'Error procesando el archivo: ' . $e->getMessage())
                         ->with('actualizacion_id', $actualizacion->id);
        }
    }

    public function toggleActivo(Producto $producto)
    {
        $producto->update(['activo' => ! $producto->activo]);

        $msg = $producto->activo ? 'Producto activado.' : 'Producto inactivado.';
        return back()->with('success', $msg);
    }

    public function eliminar(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos')->with('success', 'Producto eliminado.');
    }
}