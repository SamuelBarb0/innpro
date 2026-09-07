<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use App\Models\OrdenServicioItem;
use App\Models\OrdenServicioBitacora;
use App\Models\OrdenServicioBitacoraFoto;
use App\Models\OrdenServicioImagen;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class OrdenServicioController extends Controller
{
    /* ============ Helpers de autorización ============ */

    private function puedeGestionar(): bool
    {
        // El vendedor salió del módulo: su alcance es Productos, Listas de
        // precios y Cotizador. Debe coincidir con `role:admin|tecnico`.
        return Auth::user()->hasAnyRole(['admin', 'tecnico']);
    }

    // Bitácora: registro/edición exclusivo de técnicos y admin (brief B4)
    private function puedeBitacora(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'tecnico']);
    }

    private function esAdmin(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    /** Técnico "puro": ve el trabajo, no la parte económica ni los estados administrativos. */
    private function esTecnico(): bool
    {
        $u = Auth::user();
        return $u->hasRole('tecnico') && ! $u->hasAnyRole(['admin', 'vendedor']);
    }

    /**
     * Los costos los determina facturación, no el técnico: se le ocultan
     * el costo de mano de obra, el resumen del servicio y los precios de los ítems.
     */
    private function verCostos(): bool
    {
        return ! $this->esTecnico();
    }

    /** Estados que el usuario actual puede asignar a una orden. */
    private function estadosDisponibles(): array
    {
        if (! $this->esTecnico()) {
            return OrdenServicio::ESTADOS;
        }

        return array_intersect_key(
            OrdenServicio::ESTADOS,
            array_flip(OrdenServicio::ESTADOS_TECNICO)
        );
    }

    /* ============ Helpers de archivos ============ */

    /**
     * Guarda un archivo subido dentro del webroot (public/) y devuelve la ruta relativa.
     *
     * Se usa public_path() —igual que el módulo de productos— en vez del disco 'public',
     * que en esta plantilla apunta a ../public_html y no es fiable fuera de producción.
     */
    private function guardarImagen(\Illuminate\Http\UploadedFile $archivo, string $carpetaRelativa): string
    {
        $directorio = public_path($carpetaRelativa);
        if (! is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $extension = strtolower($archivo->getClientOriginalExtension() ?: 'jpg');
        $nombre    = time() . '_' . uniqid() . '.' . $extension;
        $archivo->move($directorio, $nombre);

        return $carpetaRelativa . '/' . $nombre;
    }

    /** Borra del disco un archivo referenciado por su ruta relativa al webroot. */
    private function borrarArchivo(?string $rutaRelativa): void
    {
        if (! $rutaRelativa) {
            return;
        }
        $absoluta = public_path($rutaRelativa);
        if (is_file($absoluta)) {
            @unlink($absoluta);
        }
    }

    /** Decodifica el PNG del canvas de firma y lo deja en el webroot. */
    private function guardarFirmaBase64(string $dataUri, string $carpetaRelativa): string
    {
        $binario = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);

        abort_if($binario === false || strlen($binario) < 100, 422, 'La firma recibida no es válida.');

        $directorio = public_path($carpetaRelativa);
        if (! is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $plano = $this->aplanarSobreBlanco($binario);

        // Nunca archivar una firma vacía: es peor que no tener firma.
        abort_if($this->pixelesDeTrazo($plano) < 20, 422, 'La firma llegó en blanco. Vuelve a dibujarla.');

        $nombre = time() . '_' . uniqid() . '.png';
        file_put_contents($directorio . DIRECTORY_SEPARATOR . $nombre, $plano);

        return $carpetaRelativa . '/' . $nombre;
    }

    /** Cuenta píxeles oscuros para detectar firmas en blanco. */
    private function pixelesDeTrazo(string $png): int
    {
        if (! function_exists('imagecreatefromstring')) {
            return PHP_INT_MAX; // sin GD no podemos comprobar; no bloqueamos
        }

        $im = @imagecreatefromstring($png);
        if ($im === false) {
            return 0;
        }

        $n = 0;
        for ($y = 0; $y < imagesy($im); $y += 2) {
            for ($x = 0; $x < imagesx($im); $x += 2) {
                $p = imagecolorat($im, $x, $y);
                if (((($p >> 24) & 0x7F) < 60) && ((($p >> 16) & 0xFF) < 200)) {
                    $n++;
                }
            }
        }
        imagedestroy($im);

        return $n;
    }

    /**
     * Devuelve el PNG sin canal alfa, compuesto sobre blanco.
     *
     * DomPDF no compone la transparencia: una firma con fondo transparente se
     * imprime invisible en el PDF. Si GD no está disponible se guarda tal cual.
     */
    private function aplanarSobreBlanco(string $png): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $png;
        }

        $origen = @imagecreatefromstring($png);
        if ($origen === false) {
            return $png;
        }

        $ancho = imagesx($origen);
        $alto  = imagesy($origen);
        $lienzo = imagecreatetruecolor($ancho, $alto);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        imagealphablending($lienzo, true);
        imagecopy($lienzo, $origen, 0, 0, 0, 0, $ancho, $alto);

        ob_start();
        imagepng($lienzo);
        $plano = ob_get_clean();

        imagedestroy($origen);
        imagedestroy($lienzo);

        return $plano ?: $png;
    }

    private function scopeVisible($query)
    {
        // El técnico solo ve sus órdenes asignadas; admin/vendedor ven todo.
        $u = Auth::user();
        if ($u->hasRole('tecnico') && ! $u->hasAnyRole(['admin', 'vendedor'])) {
            $query->where('tecnico_id', $u->id);
        }
        return $query;
    }

    /* ============ Listado ============ */

    public function index(Request $request)
    {
        abort_unless($this->puedeGestionar(), 403);

        if ($request->ajax()) {
            $query = $this->scopeVisible(
                // `items` lo usa el accesor `total` (mano de obra + items). Sin
                // precargarlo era una consulta por orden.
                OrdenServicio::with(['cliente', 'tecnico', 'items'])->select('ordenes_servicio.*')
            );

            return DataTables::of($query)
                ->addColumn('cliente', fn ($o) => $o->cliente?->nombre_empresa ?: $o->cliente?->nombre_contacto)
                ->addColumn('tecnico', fn ($o) => $o->tecnico?->name ?? '<span class="text-muted">Sin asignar</span>')
                ->editColumn('estado', fn ($o) => '<span class="badge bg-'.$o->estadoColor().'">'.$o->estadoLabel().'</span>')
                ->editColumn('prioridad', fn ($o) => '<span class="badge bg-'.$o->prioridadColor().'">'.$o->prioridadLabel().'</span>')
                ->editColumn('fecha_ingreso', fn ($o) => optional($o->fecha_ingreso)->format('d/m/Y'))
                ->addColumn('total', fn ($o) => '$ '.number_format($o->total, 0))
                ->addColumn('action', function ($o) {
                    $detalle = route('servicio.detalle', $o->id);
                    $editar  = route('servicio.form', $o->id);
                    $html  = '<div class="d-flex justify-content-center gap-1">';
                    $html .= '<a href="'.$detalle.'" class="btn btn-outline-primary btn-sm" title="Ver detalle"><i class="bi bi-eye"></i></a>';
                    $html .= '<a href="'.$editar.'" class="btn btn-outline-info btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>';
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['tecnico', 'estado', 'prioridad', 'action'])
                ->make(true);
        }

        return view('servicio.index', ['verCostos' => $this->verCostos()]);
    }

    /* ============ Crear / Editar (datos base) ============ */

    public function form(?OrdenServicio $orden = null)
    {
        abort_unless($this->puedeGestionar(), 403);

        $orden    = $orden ?? new OrdenServicio(['fecha_ingreso' => now()]);
        // Las sedes viajan con el cliente para poder filtrar el segundo select
        // sin ir al servidor cada vez que se cambia de cliente.
        $clientes = Cliente::activos()->with('sucursalesActivas')->orderBy('nombre_contacto')->get();
        $tecnicos = User::role('tecnico')->orderBy('name')->pluck('name', 'id');

        return view('servicio.form', [
            'orden'     => $orden,
            'clientes'  => $clientes,
            'tecnicos'  => $tecnicos,
            'verCostos' => $this->verCostos(),
        ]);
    }

    /**
     * Da de alta un prospecto sin salir del formulario de la orden.
     *
     * Pedido 8 de la reunión del 29/07: el cliente temporal debía servir tanto
     * en el cotizador como en las órdenes. Va por AJAX a propósito — un envío
     * normal recargaría la página y se perdería lo que ya se había escrito en
     * la orden.
     */
    public function crearClienteTemporal(Request $request)
    {
        abort_unless($this->puedeGestionar(), 403);

        $datos = $request->validate([
            'nombre_contacto' => 'required|string|max:255',
            'nombre_empresa' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'ciudad' => 'nullable|string|max:255',
        ], [
            'nombre_contacto.required' => 'El nombre del prospecto es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
        ]);

        if (! Cliente::listaPrecioProspectos()) {
            return response()->json([
                'message' => 'No hay ninguna lista de precios configurada, así que no se puede crear el prospecto.',
            ], 422);
        }

        $cliente = Cliente::crearProspecto($datos, Auth::id());

        return response()->json([
            'id' => $cliente->id,
            'etiqueta' => trim(($cliente->nombre_empresa ?: $cliente->nombre_contacto).' · Prospecto'),
        ]);
    }

    public function guardar(Request $request)
    {
        abort_unless($this->puedeGestionar(), 403);

        $orden = $request->id ? OrdenServicio::findOrFail($request->id) : new OrdenServicio();

        $data = $request->validate([
            'cliente_id'           => ['required', 'exists:clientes,id'],
            // La sede tiene que ser de ESE cliente: si no, se podría colar la
            // sede de otro manipulando el formulario.
            'sucursal_id'          => [
                'nullable',
                Rule::exists('cliente_sucursales', 'id')->where('cliente_id', $request->input('cliente_id')),
            ],
            'tecnico_id'           => ['nullable', 'exists:users,id'],
            'titulo'               => ['required', 'string', 'max:255'],
            'descripcion_problema' => ['nullable', 'string'],
            'prioridad'            => ['required', 'in:baja,media,alta,urgente'],
            'fecha_ingreso'        => ['required', 'date'],
            'fecha_estimada'       => ['nullable', 'date'],
            'costo_mano_obra'      => ['nullable', 'numeric', 'min:0'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'exists'   => 'El valor seleccionado no es válido.',
        ]);

        // Sin permiso de costos no se toca el valor: si no, editar una orden
        // como técnico dejaría en cero lo que puso el administrador.
        if ($this->verCostos()) {
            $data['costo_mano_obra'] = $request->input('costo_mano_obra', 0) ?: 0;
        } else {
            unset($data['costo_mano_obra']);
        }

        if (! $orden->exists) {
            $orden->numero     = OrdenServicio::generarNumero();
            $orden->creado_por = Auth::id();
            $orden->estado     = 'recibida';
        }

        $orden->fill($data)->save();

        return redirect()->route('servicio.detalle', $orden->id)
                         ->with('success', 'Orden de servicio guardada correctamente.');
    }

    /* ============ Detalle (centro de trabajo) ============ */

    public function detalle(OrdenServicio $orden)
    {
        abort_unless($this->puedeGestionar(), 403);

        $orden->load([
            'cliente', 'sucursal', 'tecnico', 'creador',
            'items.producto',
            'bitacora.tecnico', 'bitacora.fotos',
            'imagenes',
        ]);

        $productos = Producto::activos()->orderBy('nombre')->get(['id', 'nombre', 'referencia']);

        return view('servicio.detalle', [
            'orden'         => $orden,
            'productos'     => $productos,
            'estados'       => $this->estadosDisponibles(),
            'puedeBitacora' => $this->puedeBitacora(),
            'esAdmin'       => $this->esAdmin(),
            'verCostos'     => $this->verCostos(),
        ]);
    }

    /* ============ Diagnóstico / estado / observaciones ============ */

    public function actualizar(Request $request, OrdenServicio $orden)
    {
        abort_unless($this->puedeGestionar(), 403);

        $data = $request->validate([
            'diagnostico'     => ['nullable', 'string'],
            'estado'          => ['required', 'in:'.implode(',', array_keys($this->estadosDisponibles()))],
            'observaciones'   => ['nullable', 'string'],
            'costo_mano_obra' => ['nullable', 'numeric', 'min:0'],
        ], [
            'estado.in' => 'Ese estado no está disponible para tu rol.',
        ]);

        // El costo lo define facturación: aunque llegue en la petición, el técnico no lo toca.
        if (! $this->verCostos()) {
            unset($data['costo_mano_obra']);
        }

        if (in_array($data['estado'], array_merge(['finalizada'], OrdenServicio::ESTADOS_CIERRE)) && ! $orden->fecha_cierre) {
            $orden->fecha_cierre = now();
        }

        $orden->fill($data)->save();

        return back()->with('success', 'Orden actualizada.');
    }

    /* ============ B5 — Ítems (equipos / repuestos) ============ */

    public function agregarItem(Request $request, OrdenServicio $orden)
    {
        abort_unless($this->puedeGestionar(), 403);

        $data = $request->validate([
            'tipo'            => ['required', 'in:equipo,repuesto'],
            'producto_id'     => ['nullable', 'exists:productos,id'],
            'descripcion'     => ['required', 'string', 'max:255'],
            'cantidad'        => ['required', 'numeric', 'min:0.01'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'notas'           => ['nullable', 'string'],
        ]);
        // El técnico registra qué se instaló, no cuánto cuesta.
        $data['precio_unitario'] = $this->verCostos() ? ($data['precio_unitario'] ?? 0) : 0;

        $orden->items()->create($data);

        return back()->with('success', ucfirst($data['tipo']).' agregado a la orden.');
    }

    public function eliminarItem(OrdenServicio $orden, OrdenServicioItem $item)
    {
        abort_unless($this->puedeGestionar(), 403);
        abort_unless($item->orden_servicio_id === $orden->id, 404);

        $item->delete();
        return back()->with('success', 'Ítem eliminado.');
    }

    /* ============ B4 — Bitácora (solo técnico/admin) ============ */

    public function agregarBitacora(Request $request, OrdenServicio $orden)
    {
        abort_unless($this->puedeBitacora(), 403);

        $data = $request->validate([
            'descripcion'      => ['required', 'string'],
            'horas_trabajadas' => ['nullable', 'numeric', 'min:0'],
            'observaciones'    => ['nullable', 'string'],
            'fotos.*'          => ['nullable', 'image', 'max:5120'],
        ]);

        $entrada = $orden->bitacora()->create([
            'user_id'          => Auth::id(),
            'descripcion'      => $data['descripcion'],
            'horas_trabajadas' => $data['horas_trabajadas'] ?? null,
            'observaciones'    => $data['observaciones'] ?? null,
        ]);

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $ruta = $this->guardarImagen($foto, "imagenes/ordenes/{$orden->id}/bitacora");
                $entrada->fotos()->create(['ruta' => $ruta]);
            }
        }

        return back()->with('success', 'Entrada de bitácora registrada.');
    }

    public function eliminarBitacora(OrdenServicio $orden, OrdenServicioBitacora $entrada)
    {
        abort_unless($this->puedeBitacora(), 403);
        abort_unless($entrada->orden_servicio_id === $orden->id, 404);

        $entrada->delete();
        return back()->with('success', 'Entrada de bitácora eliminada.');
    }

    // Admin habilita/oculta la bitácora para el cliente (B4)
    public function toggleBitacoraVisible(OrdenServicio $orden)
    {
        abort_unless($this->esAdmin(), 403);

        $orden->update(['bitacora_visible_cliente' => ! $orden->bitacora_visible_cliente]);
        $msg = $orden->bitacora_visible_cliente
             ? 'Bitácora habilitada para el cliente (solo lectura).'
             : 'Bitácora oculta para el cliente.';

        return back()->with('success', $msg);
    }

    /* ============ B3 — Imágenes de la orden ============ */

    public function subirImagen(Request $request, OrdenServicio $orden)
    {
        abort_unless($this->puedeGestionar(), 403);

        $request->validate([
            'imagen'      => ['required', 'image', 'max:5120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $ruta = $this->guardarImagen($request->file('imagen'), "imagenes/ordenes/{$orden->id}/evidencia");
        $orden->imagenes()->create(['ruta' => $ruta, 'descripcion' => $request->descripcion]);

        return back()->with('success', 'Imagen agregada.');
    }

    public function eliminarImagen(OrdenServicio $orden, OrdenServicioImagen $imagen)
    {
        abort_unless($this->puedeGestionar(), 403);
        abort_unless($imagen->orden_servicio_id === $orden->id, 404);

        $this->borrarArchivo($imagen->ruta);
        $imagen->delete();
        return back()->with('success', 'Imagen eliminada.');
    }

    /* ============ Colector de firmas del formato técnico ============ */

    public function firmar(Request $request, OrdenServicio $orden, string $tipo)
    {
        abort_unless($this->puedeGestionar(), 403);
        abort_unless(in_array($tipo, OrdenServicio::TIPOS_FIRMA, true), 404);

        $this->registrarFirma($request, $orden, $tipo);

        return back()->with('success', $tipo === 'tecnico'
            ? 'Firma del técnico registrada.'
            : 'Firma del cliente registrada.');
    }

    public function quitarFirma(OrdenServicio $orden, string $tipo)
    {
        abort_unless($this->esAdmin(), 403);
        abort_unless(in_array($tipo, OrdenServicio::TIPOS_FIRMA, true), 404);

        $this->borrarArchivo($orden->{"firma_{$tipo}_ruta"});

        $orden->update([
            "firma_{$tipo}_ruta"   => null,
            "firma_{$tipo}_nombre" => null,
            "firma_{$tipo}_cc"     => null,
            "firma_{$tipo}_at"     => null,
        ]);

        return back()->with('success', 'Firma eliminada.');
    }

    /**
     * Firma del cliente desde el enlace público de seguimiento (sin autenticación).
     * Solo se habilita con el trabajo finalizado y no permite sobrescribir una firma existente.
     */
    public function firmarPublico(Request $request, string $token)
    {
        $orden = OrdenServicio::where('token_publico', $token)->firstOrFail();

        abort_if($orden->tieneFirma('cliente'), 403, 'Esta orden ya fue firmada por el cliente.');
        abort_unless(
            in_array($orden->estado, array_merge(['finalizada'], OrdenServicio::ESTADOS_CIERRE), true),
            403, 'La orden aún no está finalizada.'
        );

        $this->registrarFirma($request, $orden, 'cliente');

        return back()->with('success', 'Firma registrada. ¡Gracias!');
    }

    /** Valida el payload del canvas y persiste la firma. */
    private function registrarFirma(Request $request, OrdenServicio $orden, string $tipo): void
    {
        $data = $request->validate([
            'firma'  => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:600000'],
            'nombre' => ['required', 'string', 'max:255'],
            'cc'     => ['nullable', 'string', 'max:60'],
        ], [
            'firma.required'    => 'Debes dibujar la firma antes de guardar.',
            'firma.starts_with' => 'El formato de la firma no es válido.',
            'firma.max'         => 'La firma es demasiado grande.',
            'nombre.required'   => 'Indica el nombre de quien firma.',
        ]);

        // Una firma nueva reemplaza a la anterior: no dejamos huérfano el PNG viejo.
        $this->borrarArchivo($orden->{"firma_{$tipo}_ruta"});

        $orden->update([
            "firma_{$tipo}_ruta"   => $this->guardarFirmaBase64($data['firma'], "imagenes/ordenes/{$orden->id}/firmas"),
            "firma_{$tipo}_nombre" => $data['nombre'],
            "firma_{$tipo}_cc"     => $data['cc'] ?? null,
            "firma_{$tipo}_at"     => now(),
        ]);
    }

    /* ============ Seguimiento público del cliente (solo lectura) ============ */

    public function seguimientoPublico(string $token)
    {
        $orden = OrdenServicio::where('token_publico', $token)->firstOrFail();
        $orden->load(['cliente', 'sucursal', 'tecnico', 'bitacora.tecnico', 'bitacora.fotos', 'equipos']);

        return view('servicio.seguimiento', [
            'orden'   => $orden,
            'estados' => OrdenServicio::ESTADOS,
        ]);
    }

    /* ============ PDF de la orden (B3: logo + firmas + bitácora + equipos) ============ */

    public function pdf(OrdenServicio $orden)
    {
        abort_unless($this->puedeGestionar(), 403);

        $orden->load(['cliente', 'sucursal', 'tecnico', 'items.producto', 'bitacora.tecnico']);

        $pdf = Pdf::loadView('pdf.orden-servicio', ['orden' => $orden]);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download('Orden_'.$orden->numero.'.pdf');
    }
}
