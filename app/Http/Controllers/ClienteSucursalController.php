<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteSucursal;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\StockProducto;
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

    /**
     * Equipos recibidos en una sede (pedido 11 de la reunión).
     *
     * Innpro no vende de bodega: recibe equipos de terceros para el proyecto de
     * un cliente, así que lo que interesa es cuánto se recibió para ESTA sede.
     */
    public function stock(Cliente $cliente, ClienteSucursal $sucursal)
    {
        $this->autorizar($cliente);
        abort_if($sucursal->cliente_id !== $cliente->id, 404);

        $existencias = StockProducto::with('producto:id,referencia,nombre')
            ->deSucursal($sucursal->id)
            ->get()
            ->sortBy(fn ($s) => $s->producto?->nombre ?? '');

        $movimientos = MovimientoStock::with(['producto:id,nombre', 'usuario:id,name'])
            ->where('sucursal_id', $sucursal->id)
            ->latest('id')
            ->limit(30)
            ->get();

        $productos = Producto::activos()->orderBy('nombre')->get(['id', 'referencia', 'nombre']);

        return view('clientes.sucursal_stock', compact('cliente', 'sucursal', 'existencias', 'movimientos', 'productos'));
    }

    /**
     * Registra una recepción (o una salida) de equipos en la sede.
     *
     * Se guarda como movimiento además de actualizar la existencia: sin el
     * historial no hay forma de saber qué llegó cuándo ni quién lo registró.
     */
    public function guardarStock(Request $request, Cliente $cliente, ClienteSucursal $sucursal)
    {
        $this->autorizar($cliente);
        abort_if($sucursal->cliente_id !== $cliente->id, 404);

        $datos = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'modo' => ['required', 'in:sumar,restar,set'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ], [
            'producto_id.required' => 'Elige el equipo.',
            'cantidad.min' => 'La cantidad debe ser mayor que cero.',
        ]);

        $stock = StockProducto::firstOrNew([
            'producto_id' => $datos['producto_id'],
            'variante_producto_id' => null,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
        ]);

        $anterior = (int) ($stock->cantidad_disponible ?? 0);
        $nuevo = match ($datos['modo']) {
            'sumar' => $anterior + $datos['cantidad'],
            'restar' => max(0, $anterior - $datos['cantidad']),
            default => $datos['cantidad'],
        };

        $stock->cliente_id = $cliente->id;
        $stock->sucursal_id = $sucursal->id;
        $stock->cantidad_disponible = $nuevo;
        $stock->save();

        $diferencia = $nuevo - $anterior;
        if ($diferencia !== 0) {
            MovimientoStock::create([
                'producto_id' => $datos['producto_id'],
                'variante_producto_id' => null,
                'cliente_id' => $cliente->id,
                'sucursal_id' => $sucursal->id,
                'tipo_movimiento' => $diferencia > 0 ? 'entrada' : 'salida',
                'cantidad' => abs($diferencia),
                'stock_anterior' => $anterior,
                'stock_nuevo' => $nuevo,
                'origen' => 'otro',
                'motivo' => $datos['motivo'] ?: 'Movimiento registrado en la sede '.$sucursal->etiqueta,
                'usuario_id' => Auth::id(),
            ]);
        }

        return redirect()->route('clientes.sucursales.stock', [$cliente->id, $sucursal->id])
            ->with('success', $diferencia === 0
                ? 'La cantidad no cambió.'
                : 'Movimiento registrado: '.($diferencia > 0 ? '+' : '').$diferencia.' unidades.');
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
