<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaPreciosListaExport;
use App\Imports\PreciosListaImport;
use App\Models\ListaPrecio;
use App\Models\PrecioProducto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Gestión de listas de precios (pedido 10 de la reunión del 29 de julio).
 *
 * Jorge dijo que la lista "PRECIO LOCAL 4" no se entiende ni sabe editarla, y
 * tenía razón por partida doble: el nombre viene de la plantilla base (otro
 * cliente) y **no existía ninguna pantalla** para tocar las listas. Aquí se
 * crean, se renombran y se les cargan los precios desde un Excel, que es la
 * otra mitad del pedido: una lista propia por cliente.
 */
class ListaPrecioController extends Controller
{
    private function autorizar(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function index()
    {
        $this->autorizar();

        $listas = ListaPrecio::withCount(['preciosProductos', 'clientes'])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('listas_precios.index', compact('listas'));
    }

    public function guardar(Request $request)
    {
        $this->autorizar();

        $lista = $request->id ? ListaPrecio::findOrFail($request->id) : new ListaPrecio();

        $datos = $request->validate([
            'id' => ['nullable', 'integer'],
            'nombre' => ['required', 'string', 'max:255'],
            // El código es la llave que usan las importaciones por columnas, así
            // que no puede repetirse.
            'codigo' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('listas_precios', 'codigo')->ignore($lista->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'orden' => ['nullable', 'integer', 'min:0'],
        ], [
            'nombre.required' => 'El nombre de la lista es obligatorio.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.regex' => 'El código solo admite letras, números, guiones y guiones bajos.',
            'codigo.unique' => 'Ya existe otra lista con ese código.',
        ]);

        unset($datos['id']);
        $datos['orden'] = $datos['orden'] ?? 0;

        if (! $lista->exists) {
            $datos['activo'] = true;
        }

        $lista->fill($datos)->save();

        return redirect()->route('listas-precios')
            ->with('success', 'Lista de precios guardada.');
    }

    public function toggleActivo(ListaPrecio $lista)
    {
        $this->autorizar();

        // Dejar sin lista activa a un cliente le rompe el cotizador.
        if ($lista->activo && $lista->clientes()->count() > 0) {
            return redirect()->route('listas-precios')
                ->with('error', 'No se puede desactivar: hay '.$lista->clientes()->count()
                    .' cliente(s) usando esta lista. Cámbiales la lista primero.');
        }

        $lista->update(['activo' => ! $lista->activo]);

        return redirect()->route('listas-precios')
            ->with('success', $lista->activo ? 'Lista activada.' : 'Lista desactivada.');
    }

    /** Precios de una lista + cargue por Excel. */
    public function precios(ListaPrecio $lista)
    {
        $this->autorizar();

        $precios = PrecioProducto::with('producto:id,referencia,nombre')
            ->where('lista_precio_id', $lista->id)
            ->get()
            ->sortBy(fn ($p) => $p->producto?->nombre ?? '');

        $sinPrecio = \App\Models\Producto::activos()
            ->whereDoesntHave('precios', fn ($q) => $q->where('lista_precio_id', $lista->id))
            ->count();

        return view('listas_precios.precios', compact('lista', 'precios', 'sinPrecio'));
    }

    public function plantilla(ListaPrecio $lista)
    {
        $this->autorizar();

        return Excel::download(
            new PlantillaPreciosListaExport($lista),
            'plantilla_precios_'.$lista->codigo.'.xlsx'
        );
    }

    public function importar(Request $request, ListaPrecio $lista)
    {
        $this->autorizar();

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
        ], [
            'archivo.required' => 'Elige el archivo con los precios.',
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
        ]);

        $import = new PreciosListaImport($lista);
        Excel::import($import, $request->file('archivo'));

        $mensaje = "Precios actualizados: {$import->exito}.";
        if ($import->fallo > 0) {
            $mensaje .= " Con problemas: {$import->fallo}.";
        }

        return redirect()->route('listas-precios.precios', $lista->id)
            ->with($import->fallo > 0 ? 'warning' : 'success', $mensaje)
            ->with('erroresImportacion', array_slice($import->errores, 0, 25));
    }
}
