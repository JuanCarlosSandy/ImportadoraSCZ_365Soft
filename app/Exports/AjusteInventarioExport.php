<?php

namespace App\Exports;

use App\AjusteInvetario;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AjusteInventarioExport implements FromQuery, WithHeadings, WithColumnWidths, WithStyles, WithMapping, WithCustomStartCell, WithEvents, WithDrawings
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $idAlmacen;
    protected $nombreAlmacen;
    protected $buscar;
    protected $fechaGeneracion;

    public function __construct($fechaInicio, $fechaFin, $idAlmacen, $buscar)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->idAlmacen = $idAlmacen;
        $this->buscar = $buscar;
        $this->fechaGeneracion = date('d/m/Y H:i:s');

        // Obtener nombre del almacén
        if ($idAlmacen) {
            $almacen = \Illuminate\Support\Facades\DB::table('almacens')->where('id', $idAlmacen)->first();
            $this->nombreAlmacen = $almacen ? $almacen->nombre_almacen : 'Desconocido';
        } else {
            $this->nombreAlmacen = 'TODOS';
        }
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
            'A' => 22, 
            'B' => 25, 
            'C' => 15, 
            'D' => 15, 
            'E' => 45, 
            'F' => 15, 
            'G' => 35, 
        ];
    }

    public function startCell(): string
    {
        return 'A8';
    }

    public function drawings()
    {
        $drawings = [];
        $rutaLogo = public_path('img/logoPrincipal.png');
        
        if (file_exists($rutaLogo)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo Empresa');
            $drawing->setPath($rutaLogo);
            $drawing->setHeight(70);
            $drawing->setCoordinates('A1');
            $drawings[] = $drawing;
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Título
                $sheet->mergeCells('C2:G2');
                $sheet->setCellValue('C2', 'REPORTE DE AJUSTES DE INVENTARIO');
                $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Fecha
                $sheet->mergeCells('C3:F3');
                $sheet->setCellValue('C3', 'Fecha de generación: ' . $this->fechaGeneracion);
                $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Filtros
                $sheet->setCellValue('A5', 'Rango Fechas: ' . $this->fechaInicio . ' al ' . $this->fechaFin);
                $sheet->setCellValue('E5', 'Almacén: ' . $this->nombreAlmacen);
                $sheet->setCellValue('A6', 'Búsqueda: ' . (!empty($this->buscar) ? $this->buscar : 'Ninguna'));

                $sheet->getStyle('A5:G6')->getFont()->setBold(true);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $lastColumn = 'G'; 

        // Estilo para el encabezado de la tabla (fila 8)
        $sheet->getStyle("A8:{$lastColumn}8")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Estilo para todo el contenido (bordes y alineación vertical)
        if ($highestRow >= 9) {
            $sheet->getStyle("A9:{$lastColumn}{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Alineación específica
            $sheet->getStyle("A9:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Fecha
            $sheet->getStyle("C9:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Movimiento
            $sheet->getStyle("F9:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);  // Cantidad
        }
        
        return [];
    }
}