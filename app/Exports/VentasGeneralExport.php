<?php
namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VentasGeneralExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithEvents, WithCustomStartCell
{
    protected $filters;
    protected $totalVentasRegistradas = 0;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function query()
    {
        $query = DB::table('ventas')
        ->join('personas', 'ventas.idcliente', '=', 'personas.id')
        ->join('users', 'ventas.idusuario', '=', 'users.id')

        // 🔹 SUBCONSULTA: última cuota por venta
        ->leftJoin(DB::raw('(
            SELECT c1.idcredito, c1.saldo_restante
            FROM cuotas_credito c1
            INNER JOIN (
                SELECT idcredito, MAX(numero_cuota) AS max_cuota
                FROM cuotas_credito
                GROUP BY idcredito
            ) c2
            ON c1.idcredito = c2.idcredito
            AND c1.numero_cuota = c2.max_cuota
        ) AS cc'), 'cc.idcredito', '=', 'ventas.id')

        ->select(
            'ventas.id',
            'ventas.num_comprobante',
            'ventas.fecha_hora',
            'personas.nombre as cliente',
            'ventas.total',
            'users.usuario as vendedor',
            'ventas.estado',
            'ventas.idtipo_venta',
            'cc.saldo_restante'
        );

        if (!empty($this->filters['sucursal']) && $this->filters['sucursal'] !== 'undefined') {
            $query->where('users.idsucursal', $this->filters['sucursal']);
        }

        if (!empty($this->filters['tipoReporte'])) {
            if ($this->filters['tipoReporte'] === 'dia' && !empty($this->filters['fechaSeleccionada'])) {
                $inicio = $this->filters['fechaSeleccionada'] . ' 00:00:00';
                $fin = $this->filters['fechaSeleccionada'] . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$inicio, $fin]);
            } else if ($this->filters['tipoReporte'] === 'mes' && !empty($this->filters['mesSeleccionado'])) {
                $mesSeleccionado = $this->filters['mesSeleccionado'];
                $inicio = $mesSeleccionado . '-01 00:00:00';
                $fin = date('Y-m-t', strtotime($mesSeleccionado . '-01')) . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$inicio, $fin]);
            }
        }

        if (!empty($this->filters['estadoVenta']) && $this->filters['estadoVenta'] !== 'Todos' && $this->filters['estadoVenta'] !== 'undefined') {
            $query->where('ventas.estado', $this->filters['estadoVenta']);
        }

        if (!empty($this->filters['idcliente']) && $this->filters['idcliente'] !== 'undefined') {
            $query->where('ventas.idcliente', $this->filters['idcliente']);
        }
        if (!empty($this->filters['idusuario']) && $this->filters['idusuario'] !== 'undefined') {
            $query->where('ventas.idusuario', $this->filters['idusuario']);
        }

        return $query->orderBy('ventas.fecha_hora', 'asc');
    }

    public function headings(): array
    {
        return [
            'N° Comprobante',
            'Fecha y Hora',
            'Cliente',
            'Total Venta',
            'Vendedor',
            'Tipo de Venta',
            'Estado'
        ];
    }


    public function map($row): array
    {
        // Tipo de venta
        $tipoVenta = ($row->idtipo_venta == 1) ? 'Contado' : 'Crédito';

        // Estado
        if ($row->estado == 0) {
            $estadoTexto = 'Anulado';
        } else {
            if (
                $row->idtipo_venta == 2 &&
                $row->saldo_restante !== null &&
                (float)$row->saldo_restante > 0
            ) {
                $estadoTexto = 'Saldo Pendiente Bs ' . number_format($row->saldo_restante, 2);
            } else {
                $estadoTexto = 'Registrado';
                $this->totalVentasRegistradas += $row->total;
            }
        }

        return [
            $row->num_comprobante,
            date('d/m/Y H:i', strtotime($row->fecha_hora)),
            mb_strimwidth($row->cliente, 0, 30, '...'),
            number_format($row->total, 2),
            mb_strimwidth($row->vendedor, 0, 25, '...'),
            $tipoVenta,
            $estadoTexto
        ];
    }


    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 20,
            'C' => 40,
            'D' => 15,
            'E' => 25,
            'F' => 15,
            'G' => 28,
        ];
    }


    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();

            /* ================= HEADER ================= */

            // Alturas
            $sheet->getRowDimension(1)->setRowHeight(45);
            $sheet->getRowDimension(2)->setRowHeight(20);

            // TÍTULO
            $sheet->mergeCells('A1:G1');
            $sheet->setCellValue('A1', 'REPORTE GENERAL DE VENTAS');

            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0B4F77'],
                ],
            ]);

            // FECHA
            $sheet->mergeCells('A2:G2');
            $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i'));

            $sheet->getStyle('A2')->applyFromArray([
                'font' => [
                    'size' => 9,
                    'color' => ['rgb' => 'E6EEF3'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0B4F77'],
                ],
            ]);

            // LOGO
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo Empresa');
            $drawing->setPath(public_path('img/logoPrincipal.png'));
            $drawing->setHeight(45);
            $drawing->setCoordinates('G1');
            $drawing->setOffsetX(15);
            $drawing->setOffsetY(8);
            $drawing->setWorksheet($sheet);

            /* ================= CABECERA TABLA ================= */

            $sheet->getStyle('A3:G3')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0B4F77'],
                ],
            ]);

            /* ================= FILAS ================= */

            $row = 4;
            foreach ($this->query()->cursor() as $rowData) {

                // 🔴 ANULADO
                if ($rowData->estado == 0) {
                    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F4CCCC'],
                        ],
                    ]);
                }

                // 🟡 CRÉDITO CON SALDO
                elseif (
                    $rowData->idtipo_venta == 2 &&
                    $rowData->saldo_restante !== null &&
                    (float)$rowData->saldo_restante > 0
                ) {
                    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFE699'],
                        ],
                    ]);
                }

                // 🟢 REGISTRADO
                else {
                    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'DDEBF7'],
                        ],
                    ]);
                }

                $row++;
            }

            /* ================= TOTAL ================= */

            $sheet->setCellValue('C' . $row, 'Total de Ventas Registradas:');
            $sheet->setCellValue('D' . $row, number_format($this->totalVentasRegistradas, 2));
            $sheet->getStyle("C{$row}:D{$row}")->getFont()->setBold(true);

            /* ================= BORDES ================= */

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->setColor(new Color('D0D0D0'));
        }
    ];
}

}
