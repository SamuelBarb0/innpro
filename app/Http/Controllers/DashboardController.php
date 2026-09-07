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
        //
        // Va en positivo a propósito: con `! $esTecnico`, cualquier rol nuevo
        // (o un usuario sin rol) caía en el else y veía las cifras comerciales
        // SIN filtrar, es decir el mismo tablero que el administrador.
        $verComercial = $esAdmin || $esVendedor;
        // Servicio Técnico ya no es del vendedor, así que tampoco su bloque.
        $verServicio  = $esAdmin || $esTecnico;

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

        /* --- Ingreso de clientes nuevos y proyección (pedido 15) --- */

        $clientesNuevosQuery = Cliente::query();
        if ($esVendedor) {
            $clientesNuevosQuery->where('vendedor_id', $user->id);
        }

        $nuevosRango = (clone $clientesNuevosQuery)->whereBetween('created_at', [$desde, $hasta]);

        // Un prospecto todavía no es un cliente: se cuentan aparte para no
        // inflar la cifra que mira la gerencia.
        $clientesNuevos = (clone $nuevosRango)->where('es_temporal', false)->count();
        $prospectosNuevos = (clone $nuevosRango)->where('es_temporal', true)->count();

        // Mismo número de días justo antes del rango, para saber si se va mejor
        // o peor que en el periodo equivalente.
        $diasRango = max(1, $desde->diffInDays($hasta) + 1);
        $previoHasta = $desde->copy()->subSecond();
        $previoDesde = $previoHasta->copy()->subDays($diasRango)->startOfDay();

        $clientesNuevosPrevio = (clone $clientesNuevosQuery)
            ->where('es_temporal', false)
            ->whereBetween('created_at', [$previoDesde, $previoHasta])
            ->count();

        $variacionClientes = $clientesNuevosPrevio > 0
            ? (int) round((($clientesNuevos - $clientesNuevosPrevio) / $clientesNuevosPrevio) * 100)
            : null;

        [$labelsClientes, $valoresClientes] = $this->serieMensual(
            (clone $nuevosRango)
                ->where('es_temporal', false)
                ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('COUNT(*) as conteo'))
                ->groupBy('ym')
                ->pluck('conteo', 'ym'),
            $desde,
            $hasta
        );

        $proyeccion = $this->proyeccionMes($clientesNuevosQuery, $solicitudesQuery, $hasta);

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
            'clientesNuevos'        => $clientesNuevos,
            'prospectosNuevos'      => $prospectosNuevos,
            'clientesNuevosPrevio'  => $clientesNuevosPrevio,
            'variacionClientes'     => $variacionClientes,
            'chartClientesLabels'   => $labelsClientes,
            'chartClientesValores'  => $valoresClientes,
            'proyeccion'            => $proyeccion,
        ], $servicio);
    }

    /**
     * Proyección a fin de mes: lo que va del mes extrapolado por días.
     *
     * Es una regla de tres sobre los días transcurridos, a propósito. Con este
     * volumen de datos cualquier modelo más elaborado daría una falsa
     * sensación de precisión; en pantalla va etiquetada como proyección y
     * acompañada de lo que va real.
     *
     * Devuelve null si el rango no llega al mes en curso (no tiene sentido
     * proyectar un mes ya cerrado) o si el mes acaba de empezar.
     *
     * @return array{dias_transcurridos:int,dias_mes:int,clientes_real:int,clientes_proyectado:int,monto_real:float,monto_proyectado:float}|null
     */
    private function proyeccionMes($clientesQuery, $solicitudesQuery, Carbon $hasta): ?array
    {
        $hoy = now();

        if (! $hasta->isSameMonth($hoy) || ! $hasta->isSameYear($hoy)) {
            return null;
        }

        $diasTranscurridos = $hoy->day;
        $diasMes = $hoy->daysInMonth;

        // Con uno o dos días la extrapolación es ruido, no información.
        if ($diasTranscurridos < 3) {
            return null;
        }

        $inicioMes = $hoy->copy()->startOfMonth();

        $clientesReal = (clone $clientesQuery)
            ->where('es_temporal', false)
            ->whereBetween('created_at', [$inicioMes, $hoy])
            ->count();

        $montoReal = (float) (clone $solicitudesQuery)
            ->whereBetween('created_at', [$inicioMes, $hoy])
            ->sum('monto_total');

        $factor = $diasMes / $diasTranscurridos;

        return [
            'dias_transcurridos'  => $diasTranscurridos,
            'dias_mes'            => $diasMes,
            'clientes_real'       => $clientesReal,
            'clientes_proyectado' => (int) round($clientesReal * $factor),
            'monto_real'          => $montoReal,
            'monto_proyectado'    => round($montoReal * $factor, 2),
        ];
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
