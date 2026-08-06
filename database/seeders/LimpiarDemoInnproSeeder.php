<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\ClienteSucursal;
use App\Models\MovimientoStock;
use App\Models\OrdenServicio;
use App\Models\Producto;
use App\Models\SolicitudCotizacion;
use App\Models\StockProducto;
use Illuminate\Database\Seeder;

/**
 * Quita los datos de demostración y deja la base como estaba.
 *
 *   php artisan db:seed --class=LimpiarDemoInnproSeeder
 *
 * Borra por los clientes sembrados y no por un marcador en los textos: los
 * textos se muestran en pantalla durante la demostración y ensuciarlos para
 * poder limpiarlos después sería pagar el precio en el sitio equivocado.
 *
 * NO toca productos ni listas de precios a propósito: es lo único que el
 * cliente podría querer conservar —o haber editado— después de ver la demo, y
 * borrar un producto que ya se usó en una orden real arrastraría cosas por
 * delante. Se avisa cuáles son para que se decida a mano.
 */
class LimpiarDemoInnproSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && env('DEMO_FORZAR') !== '1') {
            $this->command?->error('ABORTADO: no se ejecuta en producción sin DEMO_FORZAR=1.');

            return;
        }

        $clientes = Cliente::query()
            ->whereIn('numero_identificacion', DemoInnproSeeder::NITS)
            ->orWhere(fn ($q) => $q->whereIn('nombre_empresa', DemoInnproSeeder::PROSPECTOS)->where('es_temporal', true))
            ->get();

        if ($clientes->isEmpty()) {
            $this->command?->info('No hay datos de demostración que quitar.');

            return;
        }

        $ids = $clientes->pluck('id');

        // Orden: primero lo que cuelga de los clientes, después los clientes.
        // Al revés, las claves foráneas con `nullOnDelete` dejarían huérfanos
        // silenciosos en vez de un error visible.
        $ordenes = OrdenServicio::whereIn('cliente_id', $ids)->get();
        foreach ($ordenes as $orden) {
            $orden->items()->delete();
            $orden->bitacora()->delete();
            $orden->delete();
        }

        $solicitudes = SolicitudCotizacion::whereIn('cliente_id', $ids)->delete();
        $movimientos = MovimientoStock::whereIn('cliente_id', $ids)->delete();
        $existencias = StockProducto::whereIn('cliente_id', $ids)->delete();
        $sedes = ClienteSucursal::whereIn('cliente_id', $ids)->delete();

        $cuantos = $clientes->count();
        Cliente::whereIn('id', $ids)->forceDelete();

        // El stock de bodega general (cliente y sucursal en NULL) también es de
        // la demostración. Dejarlo puesto no era solo suciedad: al volver a
        // sembrar, las entradas se sumaban ENCIMA de las existentes y tanto las
        // cantidades como el histórico salían duplicados.
        $referencias = array_column(DemoInnproSeeder::catalogo(), 0);
        $productosDemo = Producto::whereIn('referencia', $referencias)->pluck('id');

        $movimientos += MovimientoStock::whereIn('producto_id', $productosDemo)->whereNull('cliente_id')->delete();
        $existencias += StockProducto::whereIn('producto_id', $productosDemo)->whereNull('cliente_id')->delete();

        $this->command?->newLine();
        $this->command?->info('Datos de demostración retirados.');
        $this->command?->line("  Clientes: {$cuantos}  ·  Sedes: {$sedes}  ·  Órdenes: {$ordenes->count()}");
        $this->command?->line("  Solicitudes: {$solicitudes}  ·  Existencias: {$existencias}  ·  Movimientos: {$movimientos}");
        $this->command?->newLine();
        $this->command?->warn('  Quedan a propósito: los 14 productos (sin existencias), las listas');
        $this->command?->warn('  «Precio general» y «Proyecto Andina», y los usuarios de prueba.');
        $this->command?->line('  Bórralos a mano si no los quieres: pueden haberse usado ya para otra cosa.');
    }
}
