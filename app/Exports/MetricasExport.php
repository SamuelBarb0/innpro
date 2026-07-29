<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta a Excel las métricas que se muestran en el tablero,
 * respetando el rango de fechas y el alcance del rol.
 */
class MetricasExport implements WithMultipleSheets
{
    public function __construct(private array $datos)
    {
    }

    public function sheets(): array
    {
        $hojas = [new HojaMetricas('Resumen', ['Indicador', 'Valor'], $this->filasResumen())];

        if (! empty($this->datos['verServicio'])) {
            $hojas[] = new HojaMetricas(
                'Por técnico',
                ['Técnico', 'Órdenes', 'Cerradas', 'Días promedio', 'Horas registradas'],
                $this->filasTecnicos()
            );

            $hojas[] = new HojaMetricas(
                'Equipos atendidos',
                ['Equipo', 'Unidades', 'Órdenes'],
                collect($this->datos['topEquipos'] ?? [])
                    ->map(fn ($e) => [$e->descripcion, (float) $e->unidades, (int) $e->ordenes])
                    ->all()
            );
        }

        if (! empty($this->datos['verComercial'])) {
            $hojas[] = new HojaMetricas(
                'Productos solicitados',
                ['Producto', 'Referencia', 'Unidades', 'Veces solicitado'],
                collect($this->datos['topProductos'] ?? [])
                    ->map(fn ($p) => [$p->nombre_producto, $p->referencia_producto, (float) $p->unidades, (int) $p->veces])
                    ->all()
            );

            $hojas[] = new HojaMetricas(
                'Clientes',
                ['Cliente', 'Solicitudes', 'Monto'],
                collect($this->datos['topClientes'] ?? [])
                    ->map(fn ($c) => [
                        $c->cliente?->nombre_empresa ?: $c->cliente?->nombre_contacto ?: 'Sin cliente',
                        (int) $c->solicitudes,
                        (float) $c->monto,
                    ])
                    ->all()
            );
        }

        return $hojas;
    }

    private function filasResumen(): array
    {
        $d = $this->datos;

        $filas = [
            ['Rango', $d['desde']->format('d/m/Y') . ' — ' . $d['hasta']->format('d/m/Y')],
        ];

        if (! empty($d['verServicio'])) {
            $filas[] = ['— SERVICIO TÉCNICO —', ''];
            $filas[] = ['Órdenes en el rango', (int) ($d['ordenesTotal'] ?? 0)];
            $filas[] = ['Órdenes abiertas (hoy)', (int) ($d['ordenesAbiertas'] ?? 0)];
            $filas[] = ['Órdenes vencidas (hoy)', (int) ($d['ordenesVencidas'] ?? 0)];
            $filas[] = ['Órdenes cerradas (finalizada, garantía o facturado)', (int) ($d['ordenesCerradas'] ?? 0)];
            $filas[] = ['Días promedio de atención', $d['diasPromedio'] ?? 'Sin datos'];
            $filas[] = ['Horas registradas en bitácora', (float) ($d['horasRegistradas'] ?? 0)];
        }

        if (! empty($d['verComercial'])) {
            $filas[] = ['— COMERCIAL —', ''];
            $filas[] = ['Solicitudes de cotización', (int) $d['solicitudesTotal']];
            $filas[] = ['Solicitudes pendientes', (int) $d['solicitudesPendientes']];
            $filas[] = ['Monto solicitado', (float) $d['montoSolicitado']];
            $filas[] = ['Clientes activos', (int) $d['clientesActivos']];
            $filas[] = ['Productos activos', (int) $d['productosActivos']];
            $filas[] = ['Productos con stock', (int) $d['productosConStock']];
            $filas[] = ['Productos sin stock', (int) $d['productosSinStock']];
        }

        return $filas;
    }

    private function filasTecnicos(): array
    {
        $horas = $this->datos['horasPorTecnico'] ?? collect();

        return collect($this->datos['porTecnico'] ?? [])
            ->map(fn ($t) => [
                $t->tecnico?->name ?? 'Sin asignar',
                (int) $t->ordenes,
                (int) $t->cerradas,
                $t->dias_promedio !== null ? round((float) $t->dias_promedio, 1) : '',
                (float) ($horas[$t->tecnico_id] ?? 0),
            ])
            ->all();
    }
}

class HojaMetricas implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private string $titulo,
        private array $encabezados,
        private array $filas
    ) {
    }

    public function array(): array
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return $this->encabezados;
    }

    public function title(): string
    {
        return $this->titulo;
    }

    public function styles(Worksheet $sheet)
    {
        $ultima = chr(64 + max(1, count($this->encabezados)));

        $sheet->getStyle("A1:{$ultima}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '241D5E']],
        ]);

        return [];
    }
}
