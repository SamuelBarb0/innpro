<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EnlaceAcceso;
use App\Models\Cliente;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnlaceCreado;
use Illuminate\Support\Facades\Log;

class EnlacesController extends Controller
{
    use \App\Http\Controllers\Concerns\AccionesEnLote;

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Mostrar listado de enlaces
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            
            // El conteo iba fila por fila; así viaja en la misma consulta.
            // `select()` va PRIMERO: si va después borra la subconsulta del
            // conteo y el valor llega nulo.
            $query = EnlaceAcceso::select('enlaces_acceso.*')
                                ->with(['cliente', 'creadoPor'])
                                ->withCount('solicitudesCotizacion');
            
            // Filtrar por rol
            if ($user->hasRole('vendedor')) {
                // Vendedor solo ve enlaces que él creó
                $query->where('creado_por', $user->id);
            }
            // Admin ve todos los enlaces

            if ($request->boolean('solo_ids')) {
                return $this->idsDelFiltro($request, $query, 'enlaces_acceso.id');
            }

            return DataTables::of($query)
                ->addColumn('seleccion', fn($e) =>
                    '<input type="checkbox" class="fila-lote" value="'.$e->id.'" aria-label="Seleccionar enlace">')
                ->addColumn('cliente_nombre', function($e) {
                    return $e->cliente->nombre_contacto;
                })
                ->addColumn('creado_por_nombre', function($e) {
                    return $e->creadoPor->name;
                })
                ->addColumn('fecha_creacion', function($e) {
                    return $e->created_at->format('d/m/Y H:i');
                })
                ->addColumn('fecha_expiracion', function($e) {
                    return $e->expira_en->format('d/m/Y H:i');
                })
                ->addColumn('estado', function($e) {
                    if (!$e->activo) {
                        return '<span class="badge bg-secondary">Inactivo</span>';
                    } elseif ($e->expira_en < now()) {
                        return '<span class="badge bg-danger">Expirado</span>';
                    } else {
                        return '<span class="badge bg-success">Activo</span>';
                    }
                })
                ->addColumn('mostrar_precios_badge', function($e) {
                    return $e->mostrar_precios 
                        ? '<span class="badge bg-success">Sí</span>' 
                        : '<span class="badge bg-warning">No</span>';
                })
                ->addColumn('mostrar_stock_badge', function($e) {
                    return $e->mostrar_stock 
                        ? '<span class="badge bg-success">Sí</span>' 
                        : '<span class="badge bg-warning">No</span>';
                })
                ->addColumn('ultimo_acceso_formateado', function($e) {
                    return $e->ultimo_acceso 
                        ? $e->ultimo_acceso->format('d/m/Y H:i') 
                        : '<span class="text-muted">Nunca</span>';
                })
                ->addColumn('solicitudes_count', fn($e) => $e->solicitudes_cotizacion_count)
                ->addColumn('action', function($e) {
                    $buttons = '<div class="d-flex justify-content-center gap-1">';
                    
                    // Botón copiar enlace
                    if ($e->esValido()) {
                        $url = route('catalogo.token', $e->token);
                        $buttons .= '<button type="button" class="btn btn-outline-primary btn-sm" 
                                            title="Copiar enlace" onclick="copiarEnlace(\''.$url.'\')">
                                       <i class="bi bi-link-45deg"></i>
                                    </button>';
                    }
                    
                    // Botón ver detalle
                    $buttons .= '<button type="button" class="btn btn-outline-info btn-sm" 
                                        title="Ver detalle" onclick="verDetalle('.$e->id.')">
                                   <i class="bi bi-eye"></i>
                                </button>';
                    
                    // Botón desactivar/activar
                    if ($e->activo) {
                        $buttons .= '<button type="button" class="btn btn-outline-warning btn-sm" 
                                            title="Desactivar" onclick="cambiarEstado('.$e->id.', false)">
                                       <i class="bi bi-toggle-on"></i>
                                    </button>';
                    } else {
                        $buttons .= '<button type="button" class="btn btn-outline-success btn-sm" 
                                            title="Activar" onclick="cambiarEstado('.$e->id.', true)">
                                       <i class="bi bi-toggle-off"></i>
                                    </button>';
                    }
                    
                    $buttons .= '</div>';
                    
                    return $buttons;
                })
                ->filterColumn('cliente_nombre', function($query, $keyword) {
                    $query->whereHas('cliente', function($q) use ($keyword) {
                        $q->where('nombre_contacto', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['seleccion', 'estado', 'mostrar_precios_badge', 'mostrar_stock_badge', 'ultimo_acceso_formateado', 'action'])
                ->make(true);
        }
        
        return view('enlaces.index');
    }
    
    /**
     * Mostrar formulario de nuevo enlace
     */
    public function crear()
    {
        $user = Auth::user();
        
        // Obtener clientes según el rol
        if ($user->hasRole('vendedor')) {
            $clientes = Cliente::where('vendedor_id', $user->id)
                              ->activos()
                              ->orderBy('nombre_contacto')
                              ->pluck('nombre_contacto', 'id');
        } else {
            $clientes = Cliente::activos()
                              ->orderBy('nombre_contacto')
                              ->pluck('nombre_contacto', 'id');
        }
        
        return view('enlaces.form', compact('clientes'));
    }
    
    /**
     * Guardar nuevo enlace
     */
    public function guardar(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dias_validos' => 'required|integer|min:1|max:365',
            'mostrar_precios' => 'required|boolean',
            'mostrar_stock' => 'required|boolean',
            'notas' => 'nullable|string|max:500'
        ]);
        
        // Verificar permisos sobre el cliente
        $cliente = Cliente::findOrFail($request->cliente_id);
        if ($user->hasRole('vendedor') && $cliente->vendedor_id !== $user->id) {
            return back()->with('error', 'No tiene permisos para crear enlaces para este cliente.');
        }
        
        try {
            $enlace = new EnlaceAcceso([
                'cliente_id' => $request->cliente_id,
                'creado_por' => $user->id,
                'token' => Str::random(32),
                'dias_validos' => $request->dias_validos,
                'mostrar_precios' => $request->mostrar_precios,
                'mostrar_stock' => $request->mostrar_stock,
                'expira_en' => now()->addDays($request->dias_validos),
                'activo' => true,
                'notas' => $request->notas
            ]);
            
            $enlace->save();
            
            // Cargar relaciones necesarias para el email
            $enlace->load(['cliente', 'cliente.listaPrecio', 'creadoPor']);
            
            // Enviar email al cliente
            try {
                Mail::to($enlace->cliente->email)
                    ->send(new EnlaceCreado($enlace));
                    
                $mensajeEmail = ' Se ha enviado el enlace por correo electrónico al cliente.';
            } catch (\Exception $e) {
                // Log del error pero no fallar la creación del enlace
                Log::error('Error al enviar email de enlace creado: ' . $e->getMessage());
                $mensajeEmail = ' (No se pudo enviar el correo: ' . $e->getMessage() . ')';
            }
            
            return redirect()->route('enlaces')
                           ->with('success', 'Enlace creado exitosamente. El enlace expirará el ' . 
                                   $enlace->expira_en->format('d/m/Y') . $mensajeEmail);
            
        } catch (\Exception $e) {
            return back()->withInput()
                         ->with('error', 'Error al crear el enlace: ' . $e->getMessage());
        }
    }
    
    /**
     * Ver detalle del enlace (AJAX)
     */
    public function detalle(EnlaceAcceso $enlace)
    {
        // Verificar permisos
        $user = Auth::user();
        if ($user->hasRole('vendedor') && $enlace->creado_por !== $user->id) {
            return response()->json(['error' => 'No tiene permisos para ver este enlace'], 403);
        }
        
        $enlace->load(['cliente', 'creadoPor', 'solicitudesCotizacion.items']);
        
        $html = '<div class="row">';
        
        // Información del enlace
        $html .= '<div class="col-md-6">';
        $html .= '<h6>Información del Enlace</h6>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Token:</strong></td><td><code class="user-select-all">' . $enlace->token . '</code></td></tr>';
        $html .= '<tr><td><strong>Estado:</strong></td><td>';
        if (!$enlace->activo) {
            $html .= '<span class="badge bg-secondary">Inactivo</span>';
        } elseif ($enlace->expira_en < now()) {
            $html .= '<span class="badge bg-danger">Expirado</span>';
        } else {
            $html .= '<span class="badge bg-success">Activo</span>';
        }
        $html .= '</td></tr>';
        $html .= '<tr><td><strong>Creado por:</strong></td><td>' . $enlace->creadoPor->name . '</td></tr>';
        $html .= '<tr><td><strong>Fecha creación:</strong></td><td>' . $enlace->created_at->format('d/m/Y H:i') . '</td></tr>';
        $html .= '<tr><td><strong>Expira en:</strong></td><td>' . $enlace->expira_en->format('d/m/Y H:i') . '</td></tr>';
        $html .= '<tr><td><strong>Días válidos:</strong></td><td>' . $enlace->dias_validos . ' días</td></tr>';
        $html .= '<tr><td><strong>Mostrar precios:</strong></td><td>' . ($enlace->mostrar_precios ? 'Sí' : 'No') . '</td></tr>';
        $html .= '<tr><td><strong>Mostrar stock:</strong></td><td>' . ($enlace->mostrar_stock ? 'Sí' : 'No') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // Información del cliente
        $html .= '<div class="col-md-6">';
        $html .= '<h6>Información del Cliente</h6>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Cliente:</strong></td><td>' . $enlace->cliente->nombre_contacto . '</td></tr>';
        $html .= '<tr><td><strong>Email:</strong></td><td>' . $enlace->cliente->email . '</td></tr>';
        $html .= '<tr><td><strong>Teléfono:</strong></td><td>' . $enlace->cliente->telefono . '</td></tr>';
        $html .= '<tr><td><strong>Lista de Precios:</strong></td><td>' . ($enlace->cliente->listaPrecio?->nombre ?? 'Sin lista') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // URL del enlace
        if ($enlace->esValido()) {
            $url = route('catalogo.token', $enlace->token);
            $html .= '<div class="col-12 mb-3">';
            $html .= '<h6>URL del Enlace</h6>';
            $html .= '<div class="input-group">';
            $html .= '<input type="text" class="form-control" value="' . $url . '" id="urlEnlace" readonly>';
            $html .= '<button class="btn btn-outline-primary" type="button" onclick="copiarTexto(\'urlEnlace\')">';
            $html .= '<i class="bi bi-clipboard"></i> Copiar';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Estadísticas de uso
        $html .= '<div class="col-12 mb-3">';
        $html .= '<h6>Estadísticas de Uso</h6>';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card text-center">';
        $html .= '<div class="card-body">';
        $html .= '<h3>' . $enlace->visitas . '</h3>';
        $html .= '<p class="text-muted mb-0">Visitas</p>';
        $html .= '</div></div></div>';
        
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card text-center">';
        $html .= '<div class="card-body">';
        $html .= '<h3>' . $enlace->solicitudesCotizacion->count() . '</h3>';
        $html .= '<p class="text-muted mb-0">Solicitudes</p>';
        $html .= '</div></div></div>';
        
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card text-center">';
        $html .= '<div class="card-body">';
        $html .= '<h3>' . $enlace->solicitudesCotizacion->sum(function($s) { return $s->items->sum('cantidad'); }) . '</h3>';
        $html .= '<p class="text-muted mb-0">Productos</p>';
        $html .= '</div></div></div>';
        
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card text-center">';
        $html .= '<div class="card-body">';
        $html .= '<h3>$' . number_format($enlace->solicitudesCotizacion->sum('monto_total'), 2) . '</h3>';
        $html .= '<p class="text-muted mb-0">Monto Total</p>';
        $html .= '</div></div></div>';
        
        $html .= '</div></div>';
        
        // Último acceso
        if ($enlace->ultimo_acceso) {
            $html .= '<div class="col-12">';
            $html .= '<p class="text-muted">Último acceso: ' . $enlace->ultimo_acceso->format('d/m/Y H:i') . '</p>';
            $html .= '</div>';
        }
        
        // Notas
        if ($enlace->notas) {
            $html .= '<div class="col-12">';
            $html .= '<h6>Notas</h6>';
            $html .= '<div class="alert alert-info">' . nl2br(e($enlace->notas)) . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return response($html);
    }
    
    /**
     * Cambiar estado del enlace (AJAX)
     */
    public function cambiarEstado(Request $request, EnlaceAcceso $enlace)
    {
        // Verificar permisos
        $user = Auth::user();
        if ($user->hasRole('vendedor') && $enlace->creado_por !== $user->id) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No tiene permisos para modificar este enlace'
            ], 403);
        }
        
        $request->validate([
            'activo' => 'required'
        ]);
        
        try {
            // Convertir explícitamente a entero (1 o 0)
            $activo = $request->activo === 'true' || $request->activo === true || $request->activo === 1 || $request->activo === '1' ? 1 : 0;
            
            $enlace->update(['activo' => $activo]);
            
            return response()->json([
                'success' => true,
                'mensaje' => $activo ? 'Enlace activado' : 'Enlace desactivado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar o desactivar varios enlaces de una vez.
     *
     * No hay "eliminar" porque el modulo nunca tuvo borrado: un enlace se
     * desactiva, no se borra, para no perder el rastro de a quien se le compartio.
     * Se respeta el mismo limite que `cambiarEstado`: un vendedor solo puede
     * tocar los enlaces que el creo.
     */
    public function accionEnLote(Request $request)
    {
        [$accion, $ids] = $this->datosDelLote($request, ['activar', 'desactivar']);

        $query = EnlaceAcceso::whereIn('id', $ids);

        $user = Auth::user();
        if ($user->hasRole('vendedor')) {
            $query->where('creado_por', $user->id);
        }

        $alcanzables = (clone $query)->count();
        $aplicados   = $query->update(['activo' => $accion === 'activar']);

        $ajenos   = count($ids) - $alcanzables;
        $omitidos = $ajenos > 0 ? ['no los creaste tu' => $ajenos] : [];

        return $this->respuestaDelLote($accion, $aplicados, $omitidos);
    }
}
