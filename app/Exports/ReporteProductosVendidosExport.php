<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{
    FromCollection, WithHeadings, WithMapping,
    WithColumnWidths, WithStyles, WithCustomStartCell,
    WithEvents, WithDrawings
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReporteProductosVendidosExport implements
    FromCollection, WithHeadings, WithMapping,
    WithColumnWidths, WithStyles, WithCustomStartCell,
    WithEvents, WithDrawings
{
    protected $sucursal;
    protected $fechaInicio;
    protected $fechaFin;
    protected $fechaGeneracion;
    protected $nombreSucursal;


    public function __construct($sucursal, $fechaInicio, $fechaFin, $nombreSucursal)
    {
        $this->sucursal = $sucursal;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->nombreSucursal = $nombreSucursal;
        $this->fechaGeneracion = date('d/m/Y H:i:s');
    }

    // 🔥 DATA
    public function collection()
    {
        return DB::table('detalle_ventas as dv')
            ->join('ventas as v', 'dv.idventa', '=', 'v.id')
            ->join('articulos as a', 'dv.idarticulo', '=', 'a.id')
            ->join('users as u', 'v.idusuario', '=', 'u.id')
            ->join('sucursales as s', 'v.idsucursal', '=', 's.id')
            ->select(
                'a.codigo as codigo_producto',
                'a.nombre as producto',
                'dv.cantidad',
                'dv.precio as precio_unitario',
                DB::raw('(dv.cantidad * dv.precio) as subtotal'),
                'v.num_comprobante',
                'v.fecha_hora',
                'u.usuario as vendedor',
                'v.estado',
                DB::raw("
                    CASE 
                        WHEN v.idtipo_pago = 1 THEN 'Efectivo'
                        WHEN v.idtipo_pago = 7 THEN 'QR'
                        WHEN v.idtipo_pago = 13 THEN 'Compuesto'
                        ELSE 'Otro'
                    END as tipo_pago
                ")
            )
            ->whereBetween('v.fecha_hora', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->where('v.idsucursal', $this->sucursal)
            ->orderBy('v.fecha_hora', 'desc')
            ->get();
    }

    // 🔹 HEADERS
    public function headings(): array
    {
        return [
            'Codigo',
            'Producto',
            'Cantidad',
            'Precio',
            'Subtotal',
            'Comprobante',
            'Fecha',
            'Usuario',
            'Estado',
            'Pago',
        ];
    }

    // 🔹 MAPEO
    public function map($row): array
    {
        return [
            $row->codigo_producto,
            $row->producto,
            $row->cantidad,
            $row->precio_unitario,
            $row->subtotal,
            $row->num_comprobante,
            $row->fecha_hora,
            $row->vendedor,
            $row->estado == 1 ? 'Registrado' : 'Anulado',
            $row->tipo_pago,
        ];
    }

    // 🔹 ANCHOS
    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 40,
            'C' => 12,
            'D' => 15,
            'E' => 18,
            'F' => 20,
            'G' => 22,
            'H' => 25,
            'I' => 15,
            'J' => 18,
        ];
    }

    public function startCell(): string
    {
        return 'A8';
    }

    // 🔹 LOGO
    public function drawings()
    {
        $drawings = [];

        $rutaLogo = public_path('img/logoPrincipal.png');

        if (file_exists($rutaLogo)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setPath($rutaLogo);
            $drawing->setHeight(70);
            $drawing->setCoordinates('A1');
            $drawings[] = $drawing;
        }

        return $drawings;
    }

    // 🔹 HEADER + FILTROS
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $sheet = $event->sheet;

                // TÍTULO
                $sheet->mergeCells('C2:I2');
                $sheet->setCellValue('C2', 'REPORTE DE PRODUCTOS VENDIDOS');
                $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16);

                // FECHA
                $sheet->mergeCells('C3:I3');
                $sheet->setCellValue('C3', 'Fecha de generación: ' . $this->fechaGeneracion);

                // FILTROS
                $sheet->setCellValue('A5', 'Sucursal: ' . $this->nombreSucursal);
                $sheet->setCellValue('E5', 'Desde: ' . $this->fechaInicio);
                $sheet->setCellValue('A6', 'Hasta: ' . $this->fechaFin);

                $sheet->getStyle('A5:J6')->getFont()->setBold(true);
            }
        ];
    }

    // 🔹 ESTILOS (MISMO DISEÑO)
    public function styles(Worksheet $sheet)
    {
        // HEADER TABLA
        $sheet->getStyle('A8:J8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // BORDES
        $sheet->getStyle('A9:J' . $sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // NUMÉRICOS DERECHA
        $sheet->getStyle('C9:D' . $sheet->getHighestRow())
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}