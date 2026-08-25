<?php

namespace App\Exports;

use App\Models\ListaPrecio;
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

/**
 * Genera la plantilla Excel para importar productos y precios.
 *
 * Las columnas de precios se construyen dinámicamente desde las listas
 * de precios activas en BD (por su código). Si mañana se agrega una nueva
 * lista, la plantilla automáticamente la incluye en la siguiente descarga.
 */
class PlantillaProductosExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PlantillaProductosHoja(),
            new PlantillaProductosInstrucciones(),
        ];
    }
}

class PlantillaProductosHoja implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    /** @var array<string>  códigos de lista de precios activos */
    protected array $codigosListas;

    public function __construct()
    {
        $this->codigosListas = ListaPrecio::activas()->pluck('codigo')->toArray();
    }

    public function headings(): array
    {
        return array_merge(
            ['referencia', 'nombre', 'descripcion', 'unidad_venta', 'unidad_empaque', 'peso_paca', 'cubicaje_paca', 'unidades_por_paca', 'codigo_barras', 'color_o_motivo'],
            $this->codigosListas
        );
    }

    public function array(): array
    {
        // Filas de ejemplo: producto simple + producto con variantes (mismo ref, distintos colores)
        $precios = function (float $base) {
            $row = [];
            foreach ($this->codigosListas as $i => $_) {
                $row[] = round($base + ($i * 0.10), 2);
            }
            return $row;
        };

        return [
            array_merge(['EJEMPLO-001', 'Cámara de prueba',   'Cámara con visión nocturna 1080p', 'UND', 'UND',  12.5, 0.045, 12, '7701234567890', ''],      $precios(1.00)),
            array_merge(['EJEMPLO-002', 'Caja con variantes', 'Caja en distintos colores',        'UND', 'CAJA', 8.0,  0.030, 24, '7709876543210', 'Rojo'],  $precios(2.50)),
            array_merge(['EJEMPLO-002', '',                    '',                                 '',    '',     '',   '',    '', '',              'Azul'],  $precios(2.50)),
            array_merge(['EJEMPLO-002', '',                    '',                                 '',    '',     '',   '',    '', '',              'Verde'], $precios(2.80)),
        ];
    }

    public function title(): string
    {
        return 'Productos_Precios';
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 18, // referencia
            'B' => 26, // nombre
            'C' => 36, // descripcion
            'D' => 14, // unidad_venta
            'E' => 14, // unidad_empaque
            'F' => 14, // peso_paca
            'G' => 14, // cubicaje_paca
            'H' => 16, // unidades_por_paca
            'I' => 18, // codigo_barras
            'J' => 18, // color_o_motivo
        ];
        // Ancho 14 para cada columna de precio (a partir de L)
        $letras = ['K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U'];
        foreach ($this->codigosListas as $i => $_) {
            if (isset($letras[$i])) $widths[$letras[$i]] = 14;
        }
        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $totalCols = 11 + count($this->codigosListas);
        $ultimaCol = $this->letraColumna($totalCols);

        // Encabezado general
        $sheet->getStyle("A1:{$ultimaCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
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

        // Resaltar la única columna obligatoria (referencia) en rojo
        $sheet->getStyle('A1')->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C00000'],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Bordes en datos
        $highestRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle("A2:{$ultimaCol}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D9D9D9'],
                ],
            ],
        ]);

        return [];
    }

    private function letraColumna(int $n): string
    {
        $letra = '';
        while ($n > 0) {
            $mod = ($n - 1) % 26;
            $letra = chr(65 + $mod) . $letra;
            $n = intdiv($n - $mod, 26);
        }
        return $letra;
    }
}

class PlantillaProductosInstrucciones implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct()
    {
        $this->listas = ListaPrecio::activas()->get(['codigo', 'nombre']);
    }

    /** @var \Illuminate\Support\Collection */
    public $listas;

    public function headings(): array
    {
        return ['Columna', 'Obligatorio', 'Descripción'];
    }

    public function array(): array
    {
        $filas = [
            ['referencia',     'Sí', 'Código único del producto.'],
            ['nombre',         'No', 'Nombre corto del producto. Si la referencia es nueva y esta columna viene vacía, se usa la descripción o, en último caso, la referencia.'],
            ['descripcion',    'No', 'Descripción larga del producto.'],
            ['unidad_venta',   'No', 'Unidad de venta (UND, CAJA, etc.). Default: UND.'],
            ['unidad_empaque', 'No', 'Unidad de empaque. Default: UND.'],
            ['peso_paca',         'No', 'Peso por paca en kilogramos (kg). Acepta decimales (ej. 12.5). Se usa para el cálculo de peso en la cotización.'],
            ['cubicaje_paca',     'No', 'Cubicaje (volumen) por paca en metros cúbicos (m³). Acepta decimales (ej. 0.045). Se usa para el cálculo de volumen en la cotización.'],
            ['unidades_por_paca', 'No', 'Cuántas unidades de venta trae una paca (ej. 12). Necesario para calcular el peso/volumen total según la cantidad pedida.'],
            ['codigo_barras',     'No', 'Código de barras del producto (útil para exportaciones/aduanas).'],
            ['color_o_motivo', 'No', 'Si trae valor, esa fila es una variante. Si repites la misma referencia con distinto color, todas son variantes del mismo producto.'],
        ];
        foreach ($this->listas as $lista) {
            $filas[] = [
                $lista->codigo,
                'No',
                'Precio para la lista "' . $lista->nombre . '". Vacío = no se actualiza.',
            ];
        }
        $filas[] = ['', '', ''];
        $filas[] = ['REGLAS', '', ''];
        $filas[] = ['Si la referencia existe', '', 'Se actualizan solo los campos llenos. Vacíos = no se tocan.'];
        $filas[] = ['Si la referencia no existe', '', 'Se crea el producto. Si descripcion está vacía, se usa la referencia como nombre. El producto nace sin categoría: Innpro no clasifica por categoría.'];
        $filas[] = ['Variantes', '', 'Misma referencia repetida con distinto color/motivo → variantes del mismo producto. La primera fila define los precios base; las filas siguientes guardan SOLO la diferencia (ajuste).'];

        return $filas;
    }

    public function title(): string
    {
        return 'Instrucciones';
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 14, 'C' => 90];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        // Fila de referencia (única obligatoria) en rojo
        $sheet->getStyle('A2:C2')->applyFromArray([
            'font' => ['color' => ['rgb' => 'C00000'], 'bold' => true],
        ]);
        // Wrap text en columna C
        $sheet->getStyle('C2:C100')->getAlignment()->setWrapText(true);
        return [];
    }
}
