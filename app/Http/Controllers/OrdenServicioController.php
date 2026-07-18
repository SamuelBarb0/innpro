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
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class OrdenServicioController extends Controller
{
    /* ============ Helpers de autorización ============ */

    private function puedeGestionar(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'tecnico', 'vendedor']);
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
                OrdenServicio::with(['cliente', 'tecnico'])->select('ordenes_servicio.*')
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

        return view('servicio.index');
    }

    /* ============ Crear / Editar (datos base) ============ */

    public function form(?OrdenServicio $orden = null)
    {
        abort_unless($this->puedeGestionar(), 403);

        $orden    = $orden ?? new OrdenServicio(['fecha_ingreso' => now()]);
        $clientes = Cliente::activos()->orderBy('nombre_contacto')->get();
        $tecnicos = User::role('tecnico')->orderBy('name')->pluck('name', 'id');

        return view('servicio.form', compact('orden', 'clientes', 'tecnicos'));
    }

    public function guardar(Request $request)
    {
        abort_unless($this->puedeGestionar(), 403);

        $orden = $request->id ? OrdenServicio::findOrFail($request->id) : new OrdenServicio();

        $data = $request->validate([
            'cliente_id'           => ['required', 'exists:clientes,id'],
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

        $data['costo_mano_obra'] = $request->input('costo_mano_obra', 0) ?: 0;

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
            'cliente', 'tecnico', 'creador',
            'items.producto',
            'bitacora.tecnico', 'bitacora.fotos',
            'imagenes',
        ]);

        $productos = Producto::activos()->orderBy('nombre')->get(['id', 'nombre', 'referencia']);

        return view('servicio.detalle', [
            'orden'         => $orden,
            'productos'     => $productos,
            'estados'       => OrdenServicio::ESTADOS,
            'puedeBitacora' => $this->puedeBitacora(),
            'esAdmin'       => $this->esAdmin(),
        ]);
    }

    /* ============ Diagnóstico / estado / observaciones ============ */

    public function actualizar(Request $request, OrdenServicio $orden)
    {
        abort_unless($this->puedeGestionar(), 403);

        $data = $request->validate([
            'diagnostico'     => ['nullable', 'string'],
            'estado'          => ['required', 'in:'.implode(',', array_keys(OrdenServicio::ESTADOS))],
            'observaciones'   => ['nullable', 'string'],
            'costo_mano_obra' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (in_array($data['estado'], ['finalizada', 'entregada']) && ! $orden->fecha_cierre) {
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
        $data['precio_unitario'] = $data['precio_unitario'] ?? 0;

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
                $ruta = $foto->store("ordenes_servicio/{$orden->id}/bitacora", 'public');
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

        $ruta = $request->file('imagen')->store("ordenes_servicio/{$orden->id}/evidencia", 'public');
        $orden->imagenes()->create(['ruta' => $ruta, 'descripcion' => $request->descripcion]);

        return back()->with('success', 'Imagen agregada.');
    }

    public function eliminarImagen(OrdenServicio $orden, OrdenServicioImagen $imagen)
    {
        abort_unless($this->puedeGestionar(), 403);
        abort_unless($imagen->orden_servicio_id === $orden->id, 404);

        $imagen->delete();
        return back()->with('success', 'Imagen eliminada.');
    }

    /* ============ Seguimiento público del cliente (solo lectura) ============ */

    public function seguimientoPublico(string $token)
    {
        $orden = OrdenServicio::where('token_publico', $token)->firstOrFail();
        $orden->load(['cliente', 'tecnico', 'bitacora.tecnico', 'bitacora.fotos', 'equipos']);

        return view('servicio.seguimiento', [
            'orden'   => $orden,
            'estados' => OrdenServicio::ESTADOS,
        ]);
    }

    /* ============ PDF de la orden (B3: logo + firmas + bitácora + equipos) ============ */

    public function pdf(OrdenServicio $orden)
    {
        abort_unless($this->puedeGestionar(), 403);

        $orden->load(['cliente', 'tecnico', 'items.producto', 'bitacora.tecnico']);

        $pdf = Pdf::loadView('pdf.orden-servicio', ['orden' => $orden]);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download('Orden_'.$orden->numero.'.pdf');
    }
}
