<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\User;
use App\Models\ListaPrecio;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ClientesController extends Controller
{
    use \App\Http\Controllers\Concerns\AccionesEnLote;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // OJO con el orden: `select()` REEMPLAZA la lista de columnas, así que
            // si va después de `withCount()` borra la subconsulta del conteo y
            // `sucursales_count` llega nulo. Por eso el badge de sedes no se veía
            // nunca, ni con clientes que sí tenían sedes.
            $query = Cliente::select('clientes.*')
                ->with(['vendedor', 'listaPrecio'])
                ->withCount('sucursales');

            if ($request->boolean('solo_ids')) {
                return $this->idsDelFiltro($request, $query, 'clientes.id');
            }

            return DataTables::of($query)
                ->addColumn('seleccion', fn($c) =>
                    '<input type="checkbox" class="fila-lote" value="'.$c->id.'" aria-label="Seleccionar cliente">')
                ->addColumn('vendedor', fn($c) => $c->vendedor?->name)
                ->addColumn('lista_precio', fn($c) => $c->listaPrecio?->nombre)
                ->addColumn('estado', function($c) {
                    $estado = $c->activo
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-secondary">Inactivo</span>';

                    // Los prospectos creados desde el cotizador vienen sin NIT,
                    // correo ni ciudad: hay que poder distinguirlos de un
                    // cliente de verdad al que le falten datos.
                    if ($c->es_temporal) {
                        $estado .= ' <span class="badge bg-warning text-dark">Prospecto</span>';
                    }

                    return $estado;
                })
                ->addColumn('action', function($c) {
                    $editUrl   = route('clientes.form', $c->id);
                    $toggleUrl = route('clientes.toggle-activo', $c->id);
                    $deleteUrl = route('clientes.eliminar', $c->id);
                    $csrf      = csrf_token();

                    $toggleIcon  = $c->activo ? 'bi-toggle-on' : 'bi-toggle-off';
                    $toggleClass = $c->activo ? 'btn-outline-warning' : 'btn-outline-success';
                    $toggleTitle = $c->activo ? 'Inactivar' : 'Activar';

                    $sedesUrl = route('clientes.sucursales', $c->id);
                    $sedes    = $c->sucursales_count ?? 0;

                    $html  = '<div class="d-flex justify-content-center align-items-center gap-1">';
                    $html .= '<a href="'.$editUrl.'" class="btn btn-outline-info btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>';
                    $html .= '<a href="'.$sedesUrl.'" class="btn btn-outline-primary btn-sm" title="Sedes del cliente"><i class="bi bi-geo-alt"></i>'
                          .($sedes ? ' <span class="badge bg-primary">'.$sedes.'</span>' : '').'</a>';

                    $html .= '<form method="POST" action="'.$toggleUrl.'" style="display:inline">';
                    $html .= '<input type="hidden" name="_token" value="'.$csrf.'">';
                    $html .= '<button type="submit" class="btn '.$toggleClass.' btn-sm" title="'.$toggleTitle.'"><i class="bi '.$toggleIcon.'"></i></button>';
                    $html .= '</form>';

                    $html .= '<form method="POST" action="'.$deleteUrl.'" style="display:inline" onsubmit="return confirm(\'¿Eliminar este cliente? Sus cotizaciones y datos relacionados se conservarán.\');">';
                    $html .= '<input type="hidden" name="_token" value="'.$csrf.'">';
                    $html .= '<input type="hidden" name="_method" value="DELETE">';
                    $html .= '<button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>';
                    $html .= '</form>';

                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['seleccion', 'action', 'estado'])
                ->make(true);
        }

        return view('clientes.clientes_index');
    }

    public function form(?Cliente $cliente = null)
    {
        $cliente    = $cliente ?? new Cliente();
        $vendedores = User::role('vendedor')->pluck('name', 'id');
        $listas     = ListaPrecio::activas()->pluck('nombre', 'id');

        return view('clientes.clientes_form', compact('cliente', 'vendedores', 'listas'));
    }

    public function guardar(Request $request)
    {
        $cliente = $request->id
                 ? Cliente::findOrFail($request->id)
                 : new Cliente();

        $rules = [
            'numero_identificacion' => [
                'required', 'string', 'max:255',
                Rule::unique('clientes')->ignore($cliente->id)
            ],
            'nombre_contacto'  => ['required', 'string', 'max:255'],
            'nombre_empresa'   => ['nullable', 'string', 'max:255'],
            'email'            => [
                'required', 'email', 'max:255',
                Rule::unique('clientes')->ignore($cliente->id)
            ],
            'telefono'         => ['nullable', 'string', 'max:100'],
            'pais'             => ['required', 'string', 'max:255'],
            'ciudad'           => ['required', 'string', 'max:255'],
            'vendedor_id'      => ['required', 'exists:users,id'],
            'lista_precio_id'  => ['required', 'exists:listas_precios,id'],
            'activo'           => ['nullable', 'boolean'],
        ];

        $messages = [
            'required' => 'Este campo es obligatorio.',
            'email'    => 'Debe ser un correo válido.',
            'max'      => 'No debe superar los :max caracteres.',
            'unique'   => 'Ya existe un registro con este valor.',
            'exists'   => 'El valor seleccionado no es válido.',
        ];

        $data = $request->validate($rules, $messages);
        $data['activo'] = $request->boolean('activo', true);

        // Guardar desde aquí exige NIT, correo, país y ciudad, que es justo lo
        // que le falta a un prospecto: si pasó por este formulario, ya es un
        // cliente de verdad y deja de mostrarse como temporal.
        $data['es_temporal'] = false;

        $cliente->fill($data)->save();

        return redirect()->route('clientes')
                         ->with('success', 'Cliente guardado correctamente.');
    }

    public function toggleActivo(Cliente $cliente)
    {
        $cliente->update(['activo' => ! $cliente->activo]);

        $msg = $cliente->activo ? 'Cliente activado.' : 'Cliente inactivado.';
        return back()->with('success', $msg);
    }

    public function eliminar(Cliente $cliente)
    {
        $cliente->delete();
        return redirect()->route('clientes')->with('success', 'Cliente eliminado.');
    }

    /**
     * Activar, desactivar o eliminar varios clientes de una vez.
     * El borrado es suave (SoftDeletes), así que se puede revertir.
     */
    public function accionEnLote(Request $request)
    {
        [$accion, $ids] = $this->datosDelLote($request, ['activar', 'desactivar', 'eliminar']);

        if ($accion === 'eliminar') {
            $aplicados = 0;
            foreach (Cliente::whereIn('id', $ids)->get() as $cliente) {
                $cliente->delete();
                $aplicados++;
            }

            return $this->respuestaDelLote($accion, $aplicados);
        }

        $aplicados = Cliente::whereIn('id', $ids)->update(['activo' => $accion === 'activar']);

        return $this->respuestaDelLote($accion, $aplicados);
    }
}
