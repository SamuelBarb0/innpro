<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EnlaceAcceso;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\ListaPrecio;
use App\Models\SolicitudCotizacion;
use App\Models\ItemSolicitudCotizacion;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\NuevaSolicitudCreada;
use App\Models\User;

class CatalogoController extends Controller
{
    /** Productos por página en el cotizador. */
    private const PRODUCTOS_POR_PAGINA = 24;

    /**
     * Flujo A: Acceso por cliente vía link/token
     */
    public function mostrarPorToken($token)
    {
        $enlace = EnlaceAcceso::where('token', $token)->first();
        
        if (!$enlace || !$enlace->esValido()) {
            return view('catalogo.enlace_invalido');
        }
        
        // Registrar acceso
        $enlace->registrarAcceso();
        
        $cliente = $enlace->cliente;
        
        return view('catalogo.index_cliente', compact('enlace', 'cliente'));
    }
    
    /**
     * Flujo B: Acceso por vendedor (Tienda a Tienda)
     */
    public function index()
    {
        // Solo vendedores autenticados
        $this->middleware('auth');
        
        $user = Auth::user();

        // Listas disponibles para el alta rápida de prospectos. La estándar
        // (parámetro `lista_precio_temporales`) viene preseleccionada, así que
        // quien no toque nada cotiza igual que antes.
        $listas          = ListaPrecio::activas()->get(['id', 'nombre']);
        $listaProspectos = Cliente::listaPrecioProspectos();

        // Si es vendedor, mostrar selector de clientes
        if ($user->hasRole('vendedor')) {
            $clientes = Cliente::where('vendedor_id', $user->id)
                              ->activos()
                              ->orderBy('nombre_contacto')
                              ->get();

            return view('catalogo.seleccionar_cliente', compact('clientes', 'listas', 'listaProspectos'));
        }

        // Si es admin, puede ver todos los clientes
        if ($user->hasRole('admin')) {
            $clientes = Cliente::activos()
                              ->with('vendedor')
                              ->orderBy('nombre_contacto')
                              ->get();

            return view('catalogo.seleccionar_cliente', compact('clientes', 'listas', 'listaProspectos'));
        }
        
        return redirect()->route('dashboard')->with('error', 'No tiene permisos para acceder al catálogo.');
    }

    /**
     * Crea un prospecto al vuelo y entra directo a cotizarle.
     *
     * Pedido de Jorge (reunión del 29/07): poder cotizarle a alguien que
     * todavía no es cliente sin tener que darlo de alta con NIT, correo y
     * ciudad. Queda marcado con `es_temporal` para no ensuciar la base y
     * cotiza con la lista de precios estándar (pedido 16).
     */
    public function crearClienteTemporal(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin') && ! $user->hasRole('vendedor')) {
            return redirect()->route('dashboard')
                ->with('error', 'No tiene permisos para acceder al catálogo.');
        }

        $datos = $request->validate([
            'nombre_contacto' => 'required|string|max:255',
            'nombre_empresa' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'ciudad' => 'nullable|string|max:255',
            'lista_precio_id' => 'nullable|exists:listas_precios,id',
            'tipo_cotizacion' => ['nullable', Rule::in(array_keys(SolicitudCotizacion::TIPOS))],
        ], [
            'nombre_contacto.required' => 'El nombre del prospecto es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'lista_precio_id.exists' => 'La lista de precios seleccionada ya no existe.',
            'tipo_cotizacion.in' => 'Ese tipo de cotización no es válido.',
        ]);

        // Si el vendedor eligió lista, con eso basta; el respaldo solo hace falta
        // cuando no eligió ninguna.
        if (empty($datos['lista_precio_id']) && ! Cliente::listaPrecioProspectos()) {
            return redirect()->route('catalogo')
                ->with('error', 'No hay ninguna lista de precios configurada, así que no se puede cotizar a un prospecto.');
        }

        $cliente = Cliente::crearProspecto($datos, $user->id);

        // Se entra directo a cotizar: obligar a buscarlo en la lista después de
        // acabar de crearlo sería un paso de más.
        $enlace = null;

        // El tipo se pregunta al crear el prospecto pero pertenece a la
        // cotización, que nace después al enviar el carrito: por eso viaja
        // hasta la vista y de ahí a guardarSolicitud().
        $tipoCotizacion = $datos['tipo_cotizacion'] ?? null;

        return view('catalogo.index', compact('cliente', 'enlace', 'tipoCotizacion'));
    }

    /**
     * Flujo B: Mostrar catálogo para cliente seleccionado
     */
    public function mostrarParaCliente(Request $request)
    {
        $this->middleware('auth');
        
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id'
        ]);
        
        $user = Auth::user();
        $cliente = Cliente::findOrFail($request->cliente_id);
        
        // Verificar permisos
        if ($user->hasRole('vendedor') && $cliente->vendedor_id !== $user->id) {
            return redirect()->route('catalogo')
                           ->with('error', 'No tiene permisos para cotizar a este cliente.');
        }
        
        $enlace = null; // No hay enlace en el flujo B
        $tipoCotizacion = null; // solo se pregunta en el alta rápida de prospectos

        return view('catalogo.index', compact('cliente', 'enlace', 'tipoCotizacion'));
    }
    
    /**
     * Obtener productos del catálogo (AJAX)
     */
    public function obtenerProductos(Request $request)
    {
        // La configuración se resuelve ANTES de la consulta: saber qué lista de
        // precios aplica permite precargarlos de una sola vez en vez de pedirlos
        // producto por producto.
        [$listaPrecioId, $mostrarPrecios, $mostrarStock] = $this->configuracionVisualizacion($request);

        $query = Producto::activos()
            ->with([
                'imagenPrincipal',
                // obtenerStockProducto() lee `stockPrincipal`, que es una relación
                // distinta de `stock`. Sin precargarla era una consulta por producto.
                'stockPrincipal',
                'stock' => function($q) {
                    $q->select('producto_id', 'variante_producto_id', 'cantidad_disponible', 'cantidad_reservada');
                },
                'variantes' => function($q) {
                    $q->activas()->with(['stock' => function($sq) {
                        $sq->select('producto_id', 'variante_producto_id', 'cantidad_disponible', 'cantidad_reservada');
                    }]);
                }
            ])
            ->select('productos.*'); // Asegurarse de que se incluyan todos los campos, incluyendo unidad_venta

        // Precios de la lista que aplica, precargados. getPrecioPorLista() usa la
        // relación ya cargada cuando existe, así que esto elimina la otra consulta
        // por producto.
        if ($mostrarPrecios && $listaPrecioId) {
            $query->with(['precios' => function($q) use ($listaPrecioId) {
                $q->where('lista_precio_id', $listaPrecioId)->where('activo', true);
            }]);
        }

        // Filtro por categoría. El cotizador ya no lo ofrece —Innpro no clasifica
        // por categoría y la lista que se mostraba la creaba sola el importador—,
        // pero el parámetro se respeta si alguien lo manda a mano.
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        // Búsqueda por nombre o referencia
        $busqueda = trim((string) $request->input('busqueda', ''));
        if ($busqueda !== '') {
            $query->buscar($busqueda);
        }

        // Paginación real y constante. Antes, sin filtro de categoría, se pedían
        // 10.000 productos de golpe porque buildPagination() en la vista estaba
        // vacía y no había forma de pasar de página. Con catálogos grandes eso
        // eran megabytes de JSON y la petición se caía dejando la pantalla en
        // blanco, sin mensaje.
        $perPage = (int) $request->input('per_page', self::PRODUCTOS_POR_PAGINA);
        $perPage = max(12, min($perPage, 96));
        $productos = $query->orderBy('nombre')->paginate($perPage);

        // Agregar precios y stock a los productos
        foreach ($productos as $producto) {
            // Agregar precios
            if ($mostrarPrecios && $listaPrecioId) {
                $producto->precio = $producto->getPrecioPorLista($listaPrecioId);
            } else {
                $producto->precio = null;
            }
            
            // Agregar información de stock solo si se muestra Y se controla
            if ($mostrarStock) {
                $producto->stock_info = $this->obtenerStockProducto($producto);
            } else {
                $producto->stock_info = null;
            }
            
            // Asegurarse de que unidad_venta esté disponible en la respuesta
            $producto->unidad_venta = $producto->unidad_venta;
        }
        
        return response()->json([
            'productos' => $productos,
            'mostrar_precios' => $mostrarPrecios,
            'mostrar_stock' => $mostrarStock
        ]);
    }
    
    /**
     * Quién está mirando el catálogo y qué puede ver.
     *
     * Flujo B (vendedor/admin cotizando a un cliente o prospecto): precios y
     * stock siempre visibles. Flujo A (enlace por token): lo que diga el enlace.
     *
     * @return array{0:?int,1:bool,2:bool}  [listaPrecioId, mostrarPrecios, mostrarStock]
     */
    private function configuracionVisualizacion(Request $request): array
    {
        if ($request->filled('cliente_id')) {
            $cliente = Cliente::find($request->input('cliente_id'));
            if ($cliente) {
                return [$cliente->lista_precio_id, true, true];
            }
        } elseif ($request->filled('enlace_token')) {
            $enlace = EnlaceAcceso::where('token', $request->input('enlace_token'))->first();
            if ($enlace && $enlace->esValido()) {
                return [
                    $enlace->cliente->lista_precio_id,
                    (bool) $enlace->mostrar_precios,
                    (bool) $enlace->mostrar_stock,
                ];
            }
        }

        return [null, false, false];
    }

    /**
     * Obtener detalle de producto con variantes (AJAX)
     */
    public function detalleProducto(Request $request, Producto $producto)
    {
        $producto->load([
            'variantes' => function($q) {
                $q->activas()->with(['stock' => function($sq) {
                    $sq->select('producto_id', 'variante_producto_id', 'cantidad_disponible', 'cantidad_reservada');
                }]);
            }, 
            'imagenes',
            'stock' => function($q) {
                $q->select('producto_id', 'variante_producto_id', 'cantidad_disponible', 'cantidad_reservada');
            }
        ]);
        
        // Obtener configuración según el contexto
        [$listaPrecioId, $mostrarPrecios, $mostrarStock] = $this->configuracionVisualizacion($request);

        // Agregar precios y stock
        if ($mostrarPrecios && $listaPrecioId) {
            $producto->precio = $producto->getPrecioPorLista($listaPrecioId);
            
            // Precios de variantes
            foreach ($producto->variantes as $variante) {
                $variante->precio_final = $variante->getPrecioFinal($listaPrecioId);
            }
        }
        
        if ($mostrarStock) {
            $producto->stock_info = $this->obtenerStockProducto($producto);
            
            // Stock de variantes
            foreach ($producto->variantes as $variante) {
                $variante->stock_info = $this->obtenerStockVariante($producto, $variante);
            }
        }
        
        // Asegurarse de que unidad_venta esté incluida
        $producto->unidad_venta = $producto->unidad_venta;
        
        return response()->json([
            'producto' => $producto,
            'mostrar_precios' => $mostrarPrecios,
            'mostrar_stock' => $mostrarStock
        ]);
    }
    
    /**
     * Obtener información de stock de un producto
     */
    private function obtenerStockProducto($producto)
    {
        // Si no controla stock, siempre disponible
        if (!$producto->controlar_stock) {
            return [
                'tiene_stock' => true,
                'cantidad_disponible' => 999999,
                'estado' => 'disponible',
                'mensaje' => 'Disponible',
                'controla_stock' => false
            ];
        }

        if ($producto->tiene_variantes) {
            // Para productos con variantes, sumar el stock de todas las variantes
            $stockTotal = $producto->stock->sum(function($stock) {
                return $stock->cantidad_disponible - $stock->cantidad_reservada;
            });
            
            return [
                'tiene_stock' => $stockTotal > 0 || $producto->permitir_venta_sin_stock,
                'cantidad_disponible' => $stockTotal,
                'estado' => $this->getEstadoStock($stockTotal, false, $producto->permitir_venta_sin_stock),
                'mensaje' => $this->getMensajeStock($stockTotal, false, $producto->permitir_venta_sin_stock),
                'controla_stock' => true,
                'permite_sin_stock' => $producto->permitir_venta_sin_stock
            ];
        } else {
            // Para productos sin variantes
            $stock = $producto->stockPrincipal;
            if (!$stock) {
                return [
                    'tiene_stock' => $producto->permitir_venta_sin_stock,
                    'cantidad_disponible' => 0,
                    'estado' => $producto->permitir_venta_sin_stock ? 'sin_stock_permitido' : 'sin_stock',
                    'mensaje' => $producto->permitir_venta_sin_stock ? 'Sin stock (se permite venta)' : 'Sin stock',
                    'controla_stock' => true,
                    'permite_sin_stock' => $producto->permitir_venta_sin_stock
                ];
            }
            
            $disponible = $stock->cantidad_disponible - $stock->cantidad_reservada;
            
            return [
                'tiene_stock' => $disponible > 0 || $producto->permitir_venta_sin_stock,
                'cantidad_disponible' => $disponible,
                'stock_bajo' => $stock->stock_bajo,
                'estado' => $this->getEstadoStock($disponible, $stock->stock_bajo, $producto->permitir_venta_sin_stock),
                'mensaje' => $this->getMensajeStock($disponible, $stock->stock_bajo, $producto->permitir_venta_sin_stock),
                'controla_stock' => true,
                'permite_sin_stock' => $producto->permitir_venta_sin_stock
            ];
        }
    }
    
    /**
     * Obtener información de stock de una variante
     */
    private function obtenerStockVariante($producto, $variante)
    {
        // Si no controla stock, siempre disponible
        if (!$producto->controlar_stock) {
            return [
                'tiene_stock' => true,
                'cantidad_disponible' => 999999,
                'estado' => 'disponible',
                'mensaje' => 'Disponible',
                'controla_stock' => false
            ];
        }

        $stock = $variante->stock;
        if (!$stock) {
            return [
                'tiene_stock' => $producto->permitir_venta_sin_stock,
                'cantidad_disponible' => 0,
                'estado' => $producto->permitir_venta_sin_stock ? 'sin_stock_permitido' : 'sin_stock',
                'mensaje' => $producto->permitir_venta_sin_stock ? 'Sin stock (se permite venta)' : 'Sin stock',
                'controla_stock' => true,
                'permite_sin_stock' => $producto->permitir_venta_sin_stock
            ];
        }
        
        $disponible = $stock->cantidad_disponible - $stock->cantidad_reservada;
        
        return [
            'tiene_stock' => $disponible > 0 || $producto->permitir_venta_sin_stock,
            'cantidad_disponible' => $disponible,
            'stock_bajo' => $stock->stock_bajo,
            'estado' => $this->getEstadoStock($disponible, $stock->stock_bajo, $producto->permitir_venta_sin_stock),
            'mensaje' => $this->getMensajeStock($disponible, $stock->stock_bajo, $producto->permitir_venta_sin_stock),
            'controla_stock' => true,
            'permite_sin_stock' => $producto->permitir_venta_sin_stock
        ];
    }
    
    /**
     * Obtener estado de stock
     */
    private function getEstadoStock($cantidad, $stockBajo = false, $permiteSinStock = false)
    {
        if ($cantidad <= 0) {
            return $permiteSinStock ? 'sin_stock_permitido' : 'sin_stock';
        } elseif ($stockBajo) {
            return 'stock_bajo';
        } elseif ($cantidad <= 5) {
            return 'stock_limitado';
        } else {
            return 'disponible';
        }
    }
    
    /**
     * Obtener mensaje de stock
     */
    private function getMensajeStock($cantidad, $stockBajo = false, $permiteSinStock = false)
    {
        if ($cantidad <= 0) {
            return $permiteSinStock ? 'Sin stock (se permite venta)' : 'Sin stock';
        } elseif ($stockBajo) {
            return "Stock bajo ({$cantidad} disponibles)";
        } elseif ($cantidad <= 5) {
            return "Últimas {$cantidad} unidades";
        } else {
            return "{$cantidad} disponibles";
        }
    }
    
    /**
     * Verificar si se puede agregar al carrito
     */
    private function puedeAgregarAlCarrito($producto, $cantidad, $varianteId = null)
    {
        // Si no controla stock, siempre se puede agregar
        if (!$producto->controlar_stock) {
            return ['puede' => true, 'mensaje' => ''];
        }

        // Si permite venta sin stock, siempre se puede agregar
        if ($producto->permitir_venta_sin_stock) {
            return ['puede' => true, 'mensaje' => ''];
        }

        // Si controla stock y NO permite venta sin stock, verificar disponibilidad
        return [
            'puede' => $producto->hayStock($cantidad, $varianteId),
            'mensaje' => $producto->hayStock($cantidad, $varianteId) ? '' : 'Stock insuficiente'
        ];
    }
    
    /**
     * Guardar solicitud de cotización
     */
    public function guardarSolicitud(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.variante_id' => 'nullable|exists:variantes_productos,id',
            'notas_cliente' => 'nullable|string|max:1000',
            'tipo_cotizacion' => ['nullable', Rule::in(array_keys(SolicitudCotizacion::TIPOS))],
        ]);
        
        DB::beginTransaction();
        
        try {
            // Determinar cliente y enlace
            $cliente = null;
            $enlace = null;

            if ($request->input('enlace_token') !== null) {
                // Flujo A: Cliente con token
                $enlace = EnlaceAcceso::where('token', $request->enlace_token)->first();
                if (!$enlace || !$enlace->esValido()) {
                    throw new \Exception('El enlace de acceso no es válido.');
                }
                $cliente = $enlace->cliente;
            }
            elseif ($request->input('cliente_id') !== null) {
                // Flujo B: Vendedor
                $cliente = Cliente::findOrFail($request->cliente_id);

                // Verificar permisos
                if (Auth::user()->hasRole('vendedor') && $cliente->vendedor_id !== Auth::id()) {
                    throw new \Exception('No tiene permisos para crear solicitudes para este cliente.');
                }
            }
            else {
                throw new \Exception('No se pudo identificar el cliente.');
            }
            
            // Crear solicitud
            $solicitud = new SolicitudCotizacion([
                'cliente_id' => $cliente->id,
                'enlace_acceso_id' => $enlace ? $enlace->id : null,
                'estado' => 'pendiente',
                'tipo_cotizacion' => $request->input('tipo_cotizacion') ?: null,
                'notas_cliente' => $request->notas_cliente
            ]);
            $solicitud->save();
            
            // Obtener lista de precios
            $listaPrecioId = $cliente->lista_precio_id;
            $montoTotal = 0;
            
            // Agregar items y verificar stock SOLO si es necesario
            foreach ($request->items as $item) {
                $producto = Producto::with(['stockPrincipal', 'variantes.stock'])->findOrFail($item['producto_id']);
                
                // Verificar si se puede agregar al carrito
                $validacion = $this->puedeAgregarAlCarrito($producto, $item['cantidad'], $item['variante_id'] ?? null);
                if (!$validacion['puede']) {
                    throw new \Exception("Error con el producto {$producto->nombre}: {$validacion['mensaje']}");
                }
                
                // Determinar precio
                $precioUnitario = 0;
                $infoVariante = null;
                
                if (!empty($item['variante_id'])) {
                    // Producto con variante
                    $variante = $producto->variantes()->findOrFail($item['variante_id']);
                    $precioUnitario = $variante->getPrecioFinal($listaPrecioId) ?? 0;
                    $infoVariante = $variante->nombre_variante;
                } else {
                    // Producto sin variante
                    $precioUnitario = $producto->getPrecioPorLista($listaPrecioId) ?? 0;
                }
                
                $precioTotal = $precioUnitario * $item['cantidad'];
                $montoTotal += $precioTotal;
                
                // Crear item
                ItemSolicitudCotizacion::create([
                    'solicitud_cotizacion_id' => $solicitud->id,
                    'producto_id' => $producto->id,
                    'variante_producto_id' => $item['variante_id'] ?? null,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $precioUnitario,
                    'precio_total' => $precioTotal,
                    'referencia_producto' => $producto->referencia,
                    'nombre_producto' => $producto->nombre,
                    'info_variante' => $infoVariante
                ]);
            }
            
            // Actualizar monto total
            $solicitud->update(['monto_total' => $montoTotal]);

            DB::commit();

            // Notificar a administradores y al vendedor del cliente (no detiene la respuesta si falla)
            try {
                $solicitud->load(['cliente.vendedor', 'items']);
                $destinatarios = User::role('admin')->pluck('email')->all();
                if ($cliente->vendedor && $cliente->vendedor->email) {
                    $destinatarios[] = $cliente->vendedor->email;
                }
                $destinatarios = array_values(array_unique(array_filter($destinatarios)));

                if (!empty($destinatarios)) {
                    Mail::to($destinatarios)->send(new NuevaSolicitudCreada($solicitud));
                }
            } catch (\Throwable $e) {
                Log::error('No se pudo notificar nueva solicitud: ' . $e->getMessage(), [
                    'solicitud_id' => $solicitud->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'mensaje' => 'Solicitud de cotización creada exitosamente.',
                'numero_solicitud' => $solicitud->numero_solicitud,
                'codigo_corto' => $solicitud->codigo_corto,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear la solicitud: ' . $e->getMessage()
            ], 400);
        }
    }
}