<?php

namespace App\Exports;

use App\AjusteInvetario;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AjusteInventarioExport implements FromQuery, WithHeadings, WithColumnWidths, WithStyles, WithMapping
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $idAlmacen;
    protected $buscar;

    public function __construct($fechaInicio, $fechaFin, $idAlmacen, $buscar)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->idAlmacen = $idAlmacen;
        $this->buscar = $buscar;
    }

    public function query()
    {
        $query = AjusteInvetario::join('articulos', 'ajuste_invetarios.producto', '=', 'articulos.id')
            ->join('tipo_bajas', 'ajuste_invetarios.idtipobajas', '=', 'tipo_bajas.id')
            ->join('almacens', 'ajuste_invetarios.almacen', '=', 'almacens.id')
            ->select(
                'ajuste_invetarios.created_at',
                'almacens.nombre_almacen',
                'ajuste_invetarios.tipo_movimiento', 
                'articulos.codigo',
                'articulos.nombre as nombre_articulo',
                'ajuste_invetarios.cantidad',
                'tipo_bajas.nombre as justificacion'
            );

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('ajuste_invetarios.created_at', [
                $this->fechaInicio . ' 00:00:00', 
                $this->fechaFin . ' 23:59:59'
            ]);
        }

        if ($this->idAlmacen) {
            $query->where('ajuste_invetarios.almacen', $this->idAlmacen);
        }

        if ($this->buscar) {
            $query->where('articulos.nombre', 'like', '%' . $this->buscar . '%');
        }

        return $query->orderBy('ajuste_invetarios.id', 'desc');
    }

    public function map($ajuste): array
    {
        return [
            \Carbon\Carbon::parse($ajuste->created_at)->format('d/m/Y H:i'),
            $ajuste->nombre_almacen,
            strtoupper($ajuste->tipo_movimiento),
            $ajuste->codigo,
            $ajuste->nombre_articulo,
            $ajuste->cantidad,
            $ajuste->justificacion
        ];
    }

    public function headings(): array
    {
        return [
            'Fecha y Hora',
            'Almacén',
            'Movimiento',
            'Código',
            'Artículo',
            'Cantidad',
            'Motivo'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, 
            'B' => 20, 
            'C' => 15, 
            'D' => 15, 
            'E' => 40, 
            'F' => 12, 
            'G' => 30, 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = 'G'; 

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'], 
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '808080'], 
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Fecha
        $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Movimiento
        $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Cantidad

        return [];
    }
}