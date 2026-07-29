<?php

namespace App\Http\Controllers;

use App\Exports\MetricasExport;
use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Producto;
use App\Models\SolicitudCotizacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard', $this->calcular($request));
    }

    public function exportar(Request $request)
    {
        $datos  = $this->calcular($request);
        $nombre = 'metricas_' . $datos['desde']->format('Ymd') . '_' . $datos['hasta']->format('Ymd') . '.xlsx';

        return Excel::download(new MetricasExport($datos), $nombre);
    }

    /**
     * Calcula todos los indicadores del tablero para el rango y el rol actuales.
     *
     * @return array<string,mixed>
     */
    private function calcular(Request $request): array
    {
        // Etiquetas de meses en español (el locale de la app sigue en inglés).
        Carbon::setLocale('es');

        $user = Auth::user();

        $esAdmin    = $user && $user->hasRole('admin');
        $esVendedor = $user && $user->hasRole('vendedor') && ! $esAdmin;
        $esTecnico  = $user && $user->hasRole('tecnico') && ! $esAdmin && ! $esVendedor;

        // El técnico solo ve operación; comercial queda para admin y vendedor.
        $verComercial = ! $esTecnico;
        $verServicio  = $esAdmin || $esVendedor || $esTecnico;

        [$desde, $hasta] = $this->rango($request);

        /* ===================== Comercial ===================== */

        $solicitudesQuery = SolicitudCotizacion::query();
        if ($esVendedor) {
            $solicitudesQuery->whereHas('cliente', fn ($c) => $c->where('vendedor_id', $user->id));
        }

        $solicitudesRango = (clone $solicitudesQuery)->whereBetween('created_at', [$desde, $hasta]);

        $solicitudesTotal      = (clone $solicitudesRango)->count();
        $solicitudesUltimos7   = (clone $solicitudesQuery)->where('created_at', '>=', now()->subDays(7))->count();
        $solicitudesPendientes = (clone $solicitudesQuery)->where('estado', 'pendiente')->count();
        $montoSolicitado       = (float) (clone $solicitudesRango)->sum('monto_total');

        $clientesQuery = Cliente::query()->where('activo', true);
        if ($esVendedor) {
            $clientesQuery->where('vendedor_id', $user->id);
        }
        $clientesActivos = $clientesQuery->count();

        // Productos y stock (catálogo compartido, no se filtra por vendedor).
        $productosActivos = Producto::activos()->count();

        $productosConStock = Producto::activos()->where('controlar_stock', false)->count()
            + Producto::activos()
                ->where('controlar_stock', true)
                ->whereHas('stock', fn ($q) => $q->whereRaw('(cantidad_disponible - cantidad_reservada) > 0'))
                ->count();

        $productosSinStock = max(0, $productosActivos - $productosConStock);

        // Serie mensual de solicitudes dentro del rango
        [$labels, $valores] = $this->serieMensual(
            (clone $solicitudesRango)
                ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('COUNT(*) as conteo'))
                ->groupBy('ym')
                ->pluck('conteo', 'ym'),
            $desde,
            $hasta
        );

        $idsSolicitudes = (clone $solicitudesRango)->select('id');

        $topProductos = DB::table('items_solicitud_cotizacion')
            ->whereIn('solicitud_cotizacion_id', $idsSolicitudes)
            ->select('nombre_producto', 'referencia_producto', DB::raw('SUM(cantidad) as unidades'), DB::raw('COUNT(*) as veces'))
            ->groupBy('nombre_producto', 'referencia_producto')
            ->orderByDesc('unidades')
            ->limit(8)
            ->get();

        $topClientes = (clone $solicitudesRango)
            ->select('cliente_id', DB::raw('COUNT(*) as solicitudes'), DB::raw('SUM(monto_total) as monto'))
            ->groupBy('cliente_id')
            ->orderByDesc('solicitudes')
            ->limit(8)
            ->with('cliente')
            ->get();

        /* ===================== Servicio técnico ===================== */

        $servicio = $verServicio
            ? $this->metricasServicio($user, $esTecnico, $esVendedor, $desde, $hasta)
            : [];

        return array_merge([
            'esAdmin'               => $esAdmin,
            'esVendedor'            => $esVendedor,
            'esTecnico'             => $esTecnico,
            'verComercial'          => $verComercial,
            'verServicio'           => $verServicio,
            'desde'                 => $desde,
            'hasta'                 => $hasta,
            'solicitudesTotal'      => $solicitudesTotal,
            'solicitudesUltimos7'   => $solicitudesUltimos7,
            'solicitudesPendientes' => $solicitudesPendientes,
            'montoSolicitado'       => $montoSolicitado,
            'clientesActivos'       => $clientesActivos,
            'productosActivos'      => $productosActivos,
            'productosConStock'     => $productosConStock,
            'productosSinStock'     => $productosSinStock,
            'chartLabels'           => $labels,
            'chartValores'          => $valores,
            'topProductos'          => $topProductos,
            'topClientes'           => $topClientes,
        ], $servicio);
    }

    /** Indicadores del módulo de Servicio Técnico. */
    private function metricasServicio($user, bool $esTecnico, bool $esVendedor, Carbon $desde, Carbon $hasta): array
    {
        $base = function () use ($user, $esTecnico, $esVendedor) {
            $q = OrdenServicio::query();
            if ($esTecnico) {
                $q->where('tecnico_id', $user->id);
            } elseif ($esVendedor) {
                $q->whereHas('cliente', fn ($c) => $c->where('vendedor_id', $user->id));
            }
            return $q;
        };

        $enRango = fn () => $base()->whereBetween('fecha_ingreso', [$desde->copy()->startOfDay(), $hasta]);

        $ordenesTotal     = (clone $enRango())->count();
        $ordenesAbiertas  = $base()->abiertas()->count();
        $ordenesVencidas  = $base()->abiertas()
                                ->whereNotNull('fecha_estimada')
                                ->whereDate('fecha_estimada', '<', now())
                                ->count();
        $ordenesCerradas  = (clone $enRango())
                                ->whereIn('estado', array_merge(['finalizada'], OrdenServicio::ESTADOS_CIERRE))
                                ->count();

        // Tiempo medio de atención: de ingreso a cierre, sobre las órdenes ya cerradas del rango.
        $diasPromedio = (clone $enRango())
            ->whereNotNull('fecha_cierre')
            ->avg(DB::raw('DATEDIFF(fecha_cierre, fecha_ingreso)'));

        $idsOrdenes = (clone $enRango())->select('id');

        $horasRegistradas = (float) DB::table('orden_servicio_bitacora')
            ->whereIn('orden_servicio_id', $idsOrdenes)
            ->sum('horas_trabajadas');

        // Distribución por estado (donut).
        // OJO: el alias NO puede llamarse "total" — chocaría con el accesor
        // OrdenServicio::getTotalAttribute() y Eloquent devolvería el costo, no el conteo.
        $porEstadoRaw = (clone $enRango())
            ->select('estado', DB::raw('COUNT(*) as conteo'))
            ->groupBy('estado')
            ->pluck('conteo', 'estado');

        $estadoLabels = [];
        $estadoValores = [];
        $estadoColores = [];
        $paleta = [
            'recibida' => '#8a8f9c', 'en_diagnostico' => '#3488BD', 'en_proceso' => '#241D5E',
            'espera_repuestos' => '#E4A32E', 'finalizada' => '#2AA995',
            'garantia' => '#E4572E', 'facturado' => '#12669B',
        ];
        foreach (OrdenServicio::ESTADOS as $clave => $info) {
            if (($porEstadoRaw[$clave] ?? 0) > 0) {
                $estadoLabels[]  = $info['label'];
                $estadoValores[] = (int) $porEstadoRaw[$clave];
                $estadoColores[] = $paleta[$clave] ?? '#6b7185';
            }
        }

        // Serie mensual de órdenes
        [$osLabels, $osValores] = $this->serieMensual(
            (clone $enRango())
                ->select(DB::raw("DATE_FORMAT(fecha_ingreso, '%Y-%m') as ym"), DB::raw('COUNT(*) as conteo'))
                ->groupBy('ym')
                ->pluck('conteo', 'ym'),
            $desde,
            $hasta
        );

        // Productividad por técnico
        $porTecnico = (clone $enRango())
            ->select(
                'tecnico_id',
                DB::raw('COUNT(*) as ordenes'),
                DB::raw("SUM(CASE WHEN estado IN ('finalizada','garantia','facturado') THEN 1 ELSE 0 END) as cerradas"),
                DB::raw('AVG(DATEDIFF(fecha_cierre, fecha_ingreso)) as dias_promedio')
            )
            ->groupBy('tecnico_id')
            ->orderByDesc('ordenes')
            ->with('tecnico')
            ->get();

        $horasPorTecnico = DB::table('orden_servicio_bitacora as b')
            ->join('ordenes_servicio as o', 'o.id', '=', 'b.orden_servicio_id')
            ->whereIn('b.orden_servicio_id', $idsOrdenes)
            ->select('o.tecnico_id', DB::raw('SUM(b.horas_trabajadas) as horas'))
            ->groupBy('o.tecnico_id')
            ->pluck('horas', 'o.tecnico_id');

        // Equipos de seguridad más atendidos
        $topEquipos = DB::table('orden_servicio_items')
            ->whereIn('orden_servicio_id', $idsOrdenes)
            ->where('tipo', 'equipo')
            ->select('descripcion', DB::raw('SUM(cantidad) as unidades'), DB::raw('COUNT(*) as ordenes'))
            ->groupBy('descripcion')
            ->orderByDesc('unidades')
            ->limit(8)
            ->get();

        return [
            'ordenesTotal'      => $ordenesTotal,
            'ordenesAbiertas'   => $ordenesAbiertas,
            'ordenesVencidas'   => $ordenesVencidas,
            'ordenesCerradas'   => $ordenesCerradas,
            'diasPromedio'      => $diasPromedio !== null ? round((float) $diasPromedio, 1) : null,
            'horasRegistradas'  => $horasRegistradas,
            'osEstadoLabels'    => $estadoLabels,
            'osEstadoValores'   => $estadoValores,
            'osEstadoColores'   => $estadoColores,
            'osLabels'          => $osLabels,
            'osValores'         => $osValores,
            'porTecnico'        => $porTecnico,
            'horasPorTecnico'   => $horasPorTecnico,
            'topEquipos'        => $topEquipos,
        ];
    }

    /** Rango de fechas del tablero: por defecto los últimos 12 meses. */
    private function rango(Request $request): array
    {
        $desde = $this->fecha($request->input('desde')) ?? now()->startOfMonth()->subMonths(11);
        $hasta = $this->fecha($request->input('hasta')) ?? now();

        if ($desde->greaterThan($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde->startOfDay(), $hasta->endOfDay()];
    }

    private function fecha(?string $valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $valor);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Convierte una colección ['2026-07' => 3] en etiquetas y valores continuos,
     * rellenando con cero los meses sin datos para que la gráfica no tenga huecos.
     */
    private function serieMensual($agrupado, Carbon $desde, Carbon $hasta): array
    {
        $labels  = [];
        $valores = [];

        $cursor = $desde->copy()->startOfMonth();
        $fin    = $hasta->copy()->startOfMonth();

        // Tope de seguridad por si alguien pide un rango absurdo.
        $meses = min($cursor->diffInMonths($fin) + 1, 36);

        for ($i = 0; $i < $meses; $i++) {
            $clave     = $cursor->format('Y-m');
            $labels[]  = $cursor->translatedFormat('M Y');
            $valores[] = (int) ($agrupado[$clave] ?? 0);
            $cursor->addMonth();
        }

        return [$labels, $valores];
    }
}
