<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PlantillaStockExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PlantillaStockHoja(),
            new PlantillaStockInstrucciones(),
        ];
    }
}

class PlantillaStockHoja implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'referencia',
            'cantidad',
            'modo',
            'stock_minimo',
            'stock_maximo',
            'ubicacion',
            'cliente',
            'sucursal',
        ];
    }

    /**
     * La hoja de datos va VACÍA a propósito.
     *
     * Antes traía filas de ejemplo armadas con referencias reales de la base.
     * Quien descargaba la plantilla, escribía debajo sus filas y la subía tal
     * cual, terminaba importando también el ejemplo: en el mejor caso un error
     * ("referencia no encontrada"), y en el peor un movimiento de stock real
     * sobre un producto que nadie quiso tocar.
     *
     * Los ejemplos viven ahora en la hoja "Instrucciones", donde se leen pero
     * no se importan.
     */
    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Stock';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, // referencia
            'B' => 12, // cantidad
            'C' => 12, // modo
            'D' => 14, // stock_minimo
            'E' => 14, // stock_maximo
            'F' => 22, // ubicacion
            'G' => 26, // cliente
            'H' => 22, // sucursal
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado general
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 12,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // Resaltar columnas obligatorias en rojo (referencia, cantidad)
        $sheet->getStyle('A1:B1')->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C00000'],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        $highestRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle("A2:H{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D9D9D9'],
                ],
            ],
        ]);

        return [];
    }
}

class PlantillaStockInstrucciones implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function headings(): array
    {
        return ['Columna', 'Obligatorio', 'Descripción'];
    }

    public function array(): array
    {
        return [
            ['referencia',   'Sí', 'Referencia del producto o SKU de la variante.'],
            ['cantidad',     'Sí', 'Entero positivo. Se interpreta según la columna "modo".'],
            ['modo',         'No', 'set (reemplaza el stock actual, valor por defecto), sumar (entrada) o restar (salida).'],
            ['stock_minimo', 'No', 'Umbral para la alerta de stock bajo.'],
            ['stock_maximo', 'No', 'Capacidad máxima de referencia.'],
            ['ubicacion',    'No', 'Ubicación física (bodega, estante, etc).'],
            ['cliente',      'No', 'Empresa, contacto o NIT del cliente. Déjalo vacío para cargar existencia general de bodega.'],
            ['sucursal',     'No', 'Sede del cliente (su nombre o su ciudad). Requiere que también venga "cliente".'],
            ['',             '',   ''],
            ['Notas',        '',   'Encabezados en la fila 1, exactamente como en la hoja "Stock". Una fila por referencia. La hoja "Stock" viene vacía: escribe tus filas a partir de la fila 2.'],
            ['',             '',   'En "referencia" puedes pegar la referencia del producto o su nombre. No importan las mayúsculas, las tildes ni los espacios de más. Lo mismo vale para "cliente" y "sucursal".'],
            ['',             '',   'Sin cliente ni sucursal la cantidad va a la bodega general. Con los dos, va a esa sede. Con solo el cliente, queda a su nombre sin sede concreta.'],
            ['',             '',   ''],
            ['Ejemplos',     '',   'referencia | cantidad | modo | stock_minimo | stock_maximo | ubicacion | cliente | sucursal'],
            ['',             '',   'SKU-001 | 25 | set | 5 | 100 | Bodega A | | (existencia general)'],
            ['',             '',   'REF-001 | 10 | sumar | 2 | | Estante 3 | Comercial Andina | Sede Cali'],
        ];
    }

    public function title(): string
    {
        return 'Instrucciones';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 14,
            'C' => 80,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Filas obligatorias resaltadas
        $sheet->getStyle('A2:C3')->applyFromArray([
            'font' => ['color' => ['rgb' => 'C00000'], 'bold' => true],
        ]);

        // Hasta la 20: las notas y ejemplos crecieron con cliente/sucursal.
        $sheet->getStyle('A1:C20')->applyFromArray([
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
        ]);

        return [];
    }
}
