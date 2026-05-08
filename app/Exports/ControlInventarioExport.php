<?php

namespace App\Exports;

use App\ControlInventario;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class ControlInventarioExport implements FromCollection, WithStyles, WithEvents, WithDrawings, WithCustomStartCell
{
    protected $control;

   public function __construct($id)
{
    $this->control = ControlInventario::with(
        'usuario',
        'almacen',
        'detalles.articulo'
    )->findOrFail($id);

    // 🔥 AGREGAR STOCK ACTUAL IGUAL QUE EN PDF
    foreach ($this->control->detalles as $detalle) {
        $inventario = \App\Inventario::where('idalmacen', $this->control->idalmacen)
            ->where('idarticulo', $detalle->idarticulo)
            ->first();

        $detalle->stock_actual = $inventario ? $inventario->saldo_stock : 0;
    }
}

    public function collection()
    {
        $data = [];

        foreach ($this->control->detalles as $d) {
            $data[] = [
                $d->articulo->codigo,
                $d->articulo->nombre,
                $d->stocksistema,
                $d->stock_actual,
                $d->stockfisico,
                $d->stockfisico - $d->stocksistema,
                $this->getEstadoTexto($d->estado)
            ];
        }

        return new Collection($data);
    }

    private function getEstadoTexto($estado)
    {
        if ($estado == 1) return 'NO AJUSTADO';
        if ($estado == 2) return 'VERIFICADO';
        if ($estado == 3) return 'SIN DIFERENCIA';
        return 'ANULADO';
    }

    public function drawings()
    {
        $ruta = public_path('img/logoPrincipal.png');

        if (file_exists($ruta)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setPath($ruta);
            $drawing->setHeight(60);
            $drawing->setCoordinates('A1');

            return [$drawing];
        }

        return [];
    }
    public function startCell(): string
{
    return 'A11'; // 👈 IMPORTANTE (debajo del header)
}

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $control = $this->control;

                $total = $control->detalles->count();
                $verificados = $control->detalles->where('estado', 2)->count();
                $pendientes = $control->detalles->where('estado', 1)->count();
                $anulados = $control->detalles->where('estado', 0)->count();

                $estadoGeneral = $pendientes == 0 ? 'AJUSTADO' : 'PENDIENTE';

                // 🔷 TITULO
                $sheet->mergeCells('B2:E2');
                $sheet->setCellValue('B2', 'DETALLE DE CONTROL DE INVENTARIO');
                $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 🔷 FECHA GENERACIÓN
                $sheet->mergeCells('F2:G2');
                $sheet->setCellValue('F2', 'Generado: ' . date('d/m/Y H:i'));
                $sheet->getStyle('F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // 🔷 INFO GENERAL
                $sheet->setCellValue('A4', 'Almacén:');
                $sheet->setCellValue('B4', $control->almacen->nombre_almacen);

                $sheet->setCellValue('E4', 'Responsable:');
                $sheet->setCellValue('F4', $control->usuario->usuario);

                $sheet->setCellValue('A5', 'Fecha:');
                $sheet->setCellValue('B5', $control->fechahora);

                $sheet->setCellValue('E5', 'Estado:');
                $sheet->setCellValue('F5', $estadoGeneral);

                // 🔷 RESUMEN
                $sheet->setCellValue('A7', 'Productos: ' . $total);
                $sheet->setCellValue('C7', 'Verificados: ' . $verificados);
                $sheet->setCellValue('E7', 'Pendientes: ' . $pendientes);
                $sheet->setCellValue('G7', 'Anulados: ' . $anulados);

                $sheet->getStyle('A7:H7')->getFont()->setBold(true);

                // 🔷 HEADERS TABLA
                $sheet->setCellValue('A10', 'Codigo');
                $sheet->setCellValue('B10', 'Artículo');
                $sheet->setCellValue('C10', 'Stock Sistema');
                $sheet->setCellValue('D10', 'Stock Actual');
                $sheet->setCellValue('E10', 'Stock Físico');
                $sheet->setCellValue('F10', 'Diferencia');
                $sheet->setCellValue('G10', 'Estado');

                // 🔷 ESTILO HEADER
                $sheet->getStyle('A10:G10')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '34495E']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ],
                ]);

                // 🔷 BORDES Y ESTILO TABLA
                $lastRow = 10 + $control->detalles->count();

                $sheet->getStyle("A10:G{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 🔷 ALINEACIONES
                $sheet->getStyle("B11:G{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 🔷 COLORES DINÁMICOS (DIFERENCIA)
                for ($i = 11; $i <= $lastRow; $i++) {
                    $valor = $sheet->getCell("G{$i}")->getValue();

                    if ($valor > 0) {
                        $sheet->getStyle("G{$i}")->getFont()->getColor()->setRGB('008000'); // verde
                    } elseif ($valor < 0) {
                        $sheet->getStyle("G{$i}")->getFont()->getColor()->setRGB('FF0000'); // rojo
                    }
                }
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(20);

        return [];
    }
}