<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteSucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sedes/proyectos de un cliente (pedido 9 de la reunión del 29 de julio).
 *
 * Vive en su propia pantalla, no dentro del formulario de cliente, para que
 * agregar una sede no obligue a guardar —ni haga perder— lo que se estuviera
 * editando del cliente.
 */
class ClienteSucursalController extends Controller
{
    private function autorizar(Cliente $cliente): void
    {
        $user = Auth::user();

        abort_unless($user->hasRole('admin') || $user->hasRole('vendedor'), 403);

        // Un vendedor solo toca las sedes de sus propios clientes.
        abort_if(
            $user->hasRole('vendedor') && ! $user->hasRole('admin') && $cliente->vendedor_id !== $user->id,
            403,
            'Este cliente no está a su cargo.'
        );
    }

    public function index(Cliente $cliente)
    {
        $this->autorizar($cliente);

        $sucursales = $cliente->sucursales()->orderBy('nombre')->get();

        return view('clientes.sucursales', compact('cliente', 'sucursales'));
    }

    public function guardar(Request $request, Cliente $cliente)
    {
        $this->autorizar($cliente);

        $datos = $request->validate([
            'id' => ['nullable', 'integer'],
            'nombre' => ['required', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:100'],
        ], [
            'nombre.required' => 'El nombre de la sede es obligatorio.',
        ]);

        // El id llega del formulario, así que hay que confirmar que la sede sea
        // de ESTE cliente antes de tocarla.
        $sucursal = filled($datos['id'] ?? null)
            ? $cliente->sucursales()->findOrFail($datos['id'])
            : new ClienteSucursal(['cliente_id' => $cliente->id, 'activo' => true]);

        unset($datos['id']);

        $sucursal->fill($datos);
        $sucursal->cliente_id = $cliente->id;
        $sucursal->save();

        return redirect()->route('clientes.sucursales', $cliente->id)
            ->with('success', 'Sede guardada correctamente.');
    }

    public function toggleActivo(Cliente $cliente, ClienteSucursal $sucursal)
    {
        $this->autorizar($cliente);
        abort_if($sucursal->cliente_id !== $cliente->id, 404);

        $sucursal->update(['activo' => ! $sucursal->activo]);

        return redirect()->route('clientes.sucursales', $cliente->id)
            ->with('success', $sucursal->activo ? 'Sede activada.' : 'Sede desactivada.');
    }

    public function eliminar(Cliente $cliente, ClienteSucursal $sucursal)
    {
        $this->autorizar($cliente);
        abort_if($sucursal->cliente_id !== $cliente->id, 404);

        $sucursal->delete();

        return redirect()->route('clientes.sucursales', $cliente->id)
            ->with('success', 'Sede eliminada.');
    }
}
