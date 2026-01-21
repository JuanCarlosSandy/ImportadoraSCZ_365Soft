<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KardexDetalladoExport implements FromArray, WithColumnWidths, WithStyles, WithEvents
{
    protected $data;     
    protected $articulo; 
    protected $rango;    

    protected $rowVentas;
    protected $rowCompras;
    protected $rowTraspasos; 
    protected $rowAjustes;

    public function __construct($data, $articulo, $inicio, $fin)
    {
        $this->data = $data;
        $this->articulo = $articulo;
        $this->rango = "Rango: $inicio al $fin";
    }

    public function array(): array
    {
        $rows = [];
        
        $traspasos = isset($this->data->traspasos) ? $this->data->traspasos : [];

        
        $rows[] = ['KARDEX DETALLADO DE PRODUCTO']; 
        $rows[] = [$this->rango];                   
        $rows[] = [''];                             
        
        $rows[] = ['Código:', $this->articulo->codigo]; 
        $rows[] = ['Producto:', $this->articulo->nombre]; 
        $rows[] = ['']; 

        
        $this->rowVentas = count($rows) + 1; 
        $rows[] = ['1. VENTAS']; 
        
        if (count($this->data->ventas) > 0) {
            $rows[] = ['FECHA', 'DOC', 'CLIENTE', 'MODO', 'CANT.', 'TOTAL UNID.'];
            foreach ($this->data->ventas as $v) {
                $rows[] = [
                    $v->fecha_hora,
                    $v->num_comprobante,
                    $v->nombre_cliente,
                    $v->modo_venta,
                    $this->formatCantidad($v->cantidad),
                    $this->formatCantidad($v->cantidad_en_unidades) 
                ];
            }
        } else {
            $rows[] = ['No hay ventas en este periodo.'];
        }
        $rows[] = ['']; 

        
        $this->rowCompras = count($rows) + 1;
        $rows[] = ['2. COMPRAS / INGRESOS'];

        if (count($this->data->ingresos) > 0) {
            $rows[] = ['FECHA', 'DOC', 'REGISTRADO POR', '', '', 'CANT.'];
            foreach ($this->data->ingresos as $i) {
                $rows[] = [
                    $i->fecha_hora,
                    $i->num_comprobante,
                    $i->responsable_compra,
                    '', '', 
                    $this->formatCantidad($i->cantidad)
                ];
            }
        } else {
            $rows[] = ['No hay compras en este periodo.'];
        }
        $rows[] = [''];

        
        $this->rowTraspasos = count($rows) + 1;
        $rows[] = ['3. TRASPASOS ENTRE ALMACENES'];

        if (count($traspasos) > 0) {
            
            $rows[] = ['FECHA', 'MOVIMIENTO', 'ORIGEN', 'DESTINO', 'RESPONSABLE', 'CANT.'];

            foreach ($traspasos as $t) {
                
                $signo = ($t->tipo_movimiento == 'Entrada' || $t->tipo_movimiento == 'ENTRADA') ? '+' : '-';
                $cantTexto = $signo . $t->cantidad;

                $rows[] = [
                    $t->fecha_hora,
                    strtoupper($t->tipo_movimiento),
                    $t->almacen_origen,
                    $t->almacen_destino,
                    $t->responsable,
                    $this->formatCantidad($cantTexto) 
                ];
            }
        } else {
            $rows[] = ['No hay traspasos en este periodo.'];
        }
        $rows[] = [''];

        
        $this->rowAjustes = count($rows) + 1;
        $rows[] = ['4. AJUSTES'];

        if (count($this->data->ajustes) > 0) {
            $rows[] = ['FECHA', 'MOTIVO', '', '', '', 'CANT.'];
            foreach ($this->data->ajustes as $a) {
                
                $cantAbs = abs($a->cantidad);
                $signo = ($a->cantidad > 0) ? '+' : '-';
                $cantTexto = $signo . $cantAbs;

                $rows[] = [
                    $a->fecha_hora,
                    $a->motivo, 
                    '', '', '', 
                    $this->formatCantidad($cantTexto) 
                ];
            }
        } else {
            $rows[] = ['No hay ajustes en este periodo.'];
        }

        return $rows;
    }

    private function formatCantidad($cant)
    {
        
        
        
        if (is_numeric(str_replace(['+', '-'], '', $cant))) {
             return $cant . ' ' . (abs((float)$cant) == 1 ? 'unidad' : 'unidades');
        }
        return $cant;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, 
            'B' => 15, 
            'C' => 30, 
            'D' => 30, 
            'E' => 20, 
            'F' => 20, 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]], 
            2 => ['font' => ['italic' => true, 'size' => 10]], 
            4 => ['font' => ['bold' => true]], 
            5 => ['font' => ['bold' => true]], 
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
                
                $sheet->mergeCells('A1:F1'); 
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                
                $this->estilarSeccion($sheet, $this->rowVentas, 'C8DCFF'); 
                $this->estilarSeccion($sheet, $this->rowCompras, 'DCFFDC'); 
                $this->estilarSeccion($sheet, $this->rowTraspasos, 'E6E6FA'); 
                $this->estilarSeccion($sheet, $this->rowAjustes, 'FFF0C8'); 
            },
        ];
    }

    private function estilarSeccion($sheet, $rowNumber, $colorHex)
    {
        
        $sheet->mergeCells("A{$rowNumber}:F{$rowNumber}");
        $sheet->getStyle("A{$rowNumber}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colorHex]
            ]
        ]);

        $headerRow = $rowNumber + 1;
        $valorCelda = $sheet->getCell("A{$headerRow}")->getValue();
        
        
        $mensajesVacios = [
            'No hay ventas en este periodo.',
            'No hay compras en este periodo.',
            'No hay traspasos en este periodo.',
            'No hay ajustes en este periodo.'
        ];

        if (!in_array($valorCelda, $mensajesVacios)) {
            
             $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            
            
            
            $sheet->getStyle("F{$headerRow}:F1000")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        } else {
            
            $sheet->mergeCells("A{$headerRow}:F{$headerRow}");
            $sheet->getStyle("A{$headerRow}")->applyFromArray([
                'font' => ['italic' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
        }
    }
}