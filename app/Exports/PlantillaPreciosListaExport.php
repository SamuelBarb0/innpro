<?php

namespace App\Exports;

use App\Models\ListaPrecio;
use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Plantilla para cargar los precios de UNA lista.
 *
 * A diferencia de la de stock, esta sí viene con filas: trae el catálogo
 * completo con el precio que cada producto tiene hoy en la lista, para que
 * Jorge solo tenga que sobrescribir la columna del precio y volver a subirla.
 * No hay riesgo de "importar el ejemplo sin querer" porque no hay ejemplos:
 * son sus propios datos.
 */
class PlantillaPreciosListaExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly ListaPrecio $lista)
    {
    }

    public function headings(): array
    {
        return ['referencia', 'producto', 'precio'];
    }

    public function array(): array
    {
        return Producto::activos()
            ->with(['precios' => fn ($q) => $q->where('lista_precio_id', $this->lista->id)])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $p) => [
                $p->referencia,
                $p->nombre,
                optional($p->precios->first())->precio,
            ])
            ->all();
    }

    public function title(): string
    {
        return 'Precios';
    }

    public function columnWidths(): array
    {
        return ['A' => 45, 'B' => 45, 'C' => 16];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '241D5E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // La columna que hay que editar, resaltada.
        $sheet->getStyle('C1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3488BD']],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(26);

        // "producto" es solo de referencia visual: el importador usa la columna
        // "referencia", así que se deja en gris para que no inviten a editarla.
        $ultima = max(2, $sheet->getHighestRow());
        $sheet->getStyle("B2:B{$ultima}")->applyFromArray([
            'font' => ['color' => ['rgb' => '888888']],
        ]);

        return [];
    }
}
