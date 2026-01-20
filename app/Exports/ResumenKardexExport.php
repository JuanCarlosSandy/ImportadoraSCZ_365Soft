<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ResumenKardexExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles, WithEvents
{
    protected $resultados;
    protected $filtros;

    // Recibimos los datos Y un array con toda la info de los filtros
    public function __construct(array $resultados, array $filtros)
    {
        $this->resultados = $resultados;
        $this->filtros = $filtros;
    }

    public function array(): array
    {
        return array_map(function ($item) {
            // Lógica Unidad/Unidades (Igual que antes)
            $valEntrada = $item['ajuste_entrada'];
            $txtEntrada = ($valEntrada == 0) ? '0' : $valEntrada . ' ' . (abs($valEntrada) == 1 ? 'Unidad' : 'Unidades');

            $valSalida = $item['ajuste_salida'];
            $txtSalida = ($valSalida == 0) ? '0' : $valSalida . ' ' . (abs($valSalida) == 1 ? 'Unidad' : 'Unidades');

            return [
                $item['codigo'],
                $item['nombre_producto'],
                $item['categoria'],
                $item['total_ventas_texto'],
                $item['total_ingresos_texto'],
                $txtEntrada,
                $txtSalida,
                $item['saldo_stock_actual_texto'],
            ];
        }, $this->resultados);
    }

    public function headings(): array
    {
        // Validamos si hay texto en los filtros opcionales, si no, ponemos "TODOS"
        $txtArticulo = !empty($this->filtros['articulo']) ? $this->filtros['articulo'] : 'TODOS';
        $txtCategoria = !empty($this->filtros['categoria']) ? $this->filtros['categoria'] : 'TODOS';
        
        // Sucursal y Fechas son obligatorios según tu imagen, así que los mostramos directos
        $txtSucursal = $this->filtros['sucursal']; 
        $txtFechas = 'Del ' . $this->filtros['fechaInicio'] . ' al ' . $this->filtros['fechaFin'];

        return [
            ['REPORTE GENERAL DE KARDEX FÍSICO'],       // Fila 1: Título
            ['Generado el: ' . date('d/m/Y H:i:s')],    // Fila 2: Fecha gen
            ['Sucursal: ' . $txtSucursal],              // Fila 3: Sucursal
            ['Artículo: ' . $txtArticulo],              // Fila 4: Artículo
            ['Categoría: ' . $txtCategoria],            // Fila 5: Categoría
            ['Filtro Fecha: ' . $txtFechas],            // Fila 6: Rango Fechas
            [''],                                       // Fila 7: Espacio vacío
            [                                           // Fila 8: Encabezados de tabla
                'CODIGO', 'PRODUCTO', 'CATEGORIA', 'VENTAS', 'COMPRAS', 'A. ENTRADA', 'A. SALIDA', 'STOCK'
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, 'B' => 45, 'C' => 20, 'D' => 15, 
            'E' => 15, 'F' => 18, 'G' => 18, 'H' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para los ENCABEZADOS DE TABLA (Fila 8)
            8 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Estilo Título (Fila 1)
            1 => ['font' => ['bold' => true, 'size' => 16]],
            // Estilos para filtros (Filas 3, 4, 5, 6)
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
            6 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Combinar celdas para todas las filas del encabezado (A hasta H)
                // Fila 1 a 6
                $sheet->mergeCells('A1:H1'); // Título
                $sheet->mergeCells('A2:H2'); // Fecha Gen
                $sheet->mergeCells('A3:H3'); // Sucursal
                $sheet->mergeCells('A4:H4'); // Artículo
                $sheet->mergeCells('A5:H5'); // Categoría
                $sheet->mergeCells('A6:H6'); // Rango Fechas

                // 2. Centrar el texto de esas filas
                $sheet->getStyle('A1:A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 3. Poner bordes a la tabla de datos (desde la fila 8 hasta el final)
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle('A8:H'.$highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}