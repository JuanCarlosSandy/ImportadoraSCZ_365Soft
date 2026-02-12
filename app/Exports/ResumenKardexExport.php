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

    public function __construct(array $resultados, array $filtros)
    {
        $this->resultados = $resultados;
        $this->filtros = $filtros;
    }

    public function array(): array
    {
        return array_map(function ($item) {
            
            $valEntrada = $item['ajuste_entrada'];
            $txtEntrada = ($valEntrada == 0) ? '0' : $valEntrada . ' ' . (abs($valEntrada) == 1 ? 'Unidad' : 'Unidades');

            $valSalida = $item['ajuste_salida'];
            $txtSalida = ($valSalida == 0) ? '0' : $valSalida . ' ' . (abs($valSalida) == 1 ? 'Unidad' : 'Unidades');

            
            $valTrasEnt = $item['total_traspasos_entrada'];
            $txtTrasEnt = ($valTrasEnt == 0) ? '0' : $valTrasEnt;

            $valTrasSal = $item['total_traspasos_salida'];
            $txtTrasSal = ($valTrasSal == 0) ? '0' : $valTrasSal;

            return [
                $item['codigo'],
                $item['nombre_producto'],
                $item['categoria'],
                $item['total_ventas_texto'],
                $item['total_ingresos_texto'],
                
                
                $txtTrasEnt, 
                $txtTrasSal,
                

                $txtEntrada,
                $txtSalida,
                $item['saldo_stock_actual_texto'],
            ];
        }, $this->resultados);
    }

    public function headings(): array
    {
        $txtArticulo = !empty($this->filtros['articulo']) ? $this->filtros['articulo'] : 'TODOS';
        $txtCategoria = !empty($this->filtros['categoria']) ? $this->filtros['categoria'] : 'TODOS';
        
        $txtSucursal = $this->filtros['sucursal']; 
        $txtFechas = 'Del ' . $this->filtros['fechaInicio'] . ' al ' . $this->filtros['fechaFin'];

        return [
            ['REPORTE GENERAL DE KARDEX FÍSICO'],
            ['Generado el: ' . date('d/m/Y H:i:s')],
            ['Sucursal: ' . $txtSucursal],
            ['Artículo: ' . $txtArticulo],
            ['Categoría: ' . $txtCategoria],
            ['Filtro Fecha: ' . $txtFechas],
            [''],
            [
                
                'CODIGO', 'PRODUCTO', 'CATEGORIA', 'VENTAS', 'COMPRAS', 'TRAS. ENT', 'TRAS. SAL', 'A. ENTRADA', 'A. SALIDA', 'STOCK'
            ]
        ];
    }

    public function columnWidths(): array
    {
        
        return [
            'A' => 15, 
            'B' => 40, 
            'C' => 20, 
            'D' => 15, 
            'E' => 15, 
            'F' => 15, 
            'G' => 15, 
            'H' => 18, 
            'I' => 18, 
            'J' => 18, 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            
            8 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            1 => ['font' => ['bold' => true, 'size' => 16]],
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

                
                
                
                $sheet->mergeCells('A1:J1'); 
                $sheet->mergeCells('A2:J2'); 
                $sheet->mergeCells('A3:J3'); 
                $sheet->mergeCells('A4:J4'); 
                $sheet->mergeCells('A5:J5'); 
                $sheet->mergeCells('A6:J6'); 

                
                $sheet->getStyle('A1:A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle('A8:J'.$highestRow)->applyFromArray([
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