<?php
namespace App\Exports;

use App\Venta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


class VentasDetalladasExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles, WithEvents
{
    protected $filters;
    protected $totalVentasRegistradas = 0;
    protected $cachedCollection; // Nueva propiedad para cachear la colección

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Si ya tenemos la colección cacheada, la retornamos
        if ($this->cachedCollection) {
            return $this->cachedCollection;
        }

        $query = Venta::with(['detalles.producto', 'usuario.persona', 'cliente']);

        if (!empty($this->filters['sucursal']) && $this->filters['sucursal'] !== 'undefined') {
            $query->whereHas('usuario', function ($q) {
                $q->where('idsucursal', $this->filters['sucursal']);
            });
        }

        if (!empty($this->filters['tipoReporte'])) {
            if ($this->filters['tipoReporte'] === 'dia' && !empty($this->filters['fechaSeleccionada'])) {
                $inicio = $this->filters['fechaSeleccionada'] . ' 00:00:00';
                $fin = $this->filters['fechaSeleccionada'] . ' 23:59:59';
                $query->whereBetween('fecha_hora', [$inicio, $fin]);
            } else if ($this->filters['tipoReporte'] === 'mes' && !empty($this->filters['mesSeleccionado'])) {
                $mesSeleccionado = $this->filters['mesSeleccionado'];
                $inicio = $mesSeleccionado . '-01 00:00:00';
                $fin = date('Y-m-t', strtotime($mesSeleccionado . '-01')) . ' 23:59:59';
                $query->whereBetween('fecha_hora', [$inicio, $fin]);
            }
        }

        if (!empty($this->filters['estadoVenta']) && $this->filters['estadoVenta'] !== 'Todos' && $this->filters['estadoVenta'] !== 'undefined') {
            $query->where('estado', $this->filters['estadoVenta']);
        }

        if (!empty($this->filters['idcliente']) && $this->filters['idcliente'] !== 'undefined') {
            $query->where('idcliente', $this->filters['idcliente']);
        }

        $ventas = $query->orderBy('fecha_hora', 'asc')->get();

        $rows = new Collection();




        foreach ($ventas as $venta) {
            $tipoVenta = $venta->idtipo_venta == 1 ? 'Contado' : 'Crédito';
            $saldoRestante = null;
            if ($venta->idtipo_venta == 2) {
                $saldoRestante = \DB::table('cuotas_credito')
                    ->where('idcredito', $venta->id)
                    ->orderByDesc('numero_cuota')
                    ->value('saldo_restante');
            }
            if ($venta->estado == 1) {
                $this->totalVentasRegistradas += (float)$venta->total;
            }
            if ($venta->estado ==0) {
                $estadoTexto = 'Anulado';
            } else {
                if ($venta->idtipo_venta == 2 && $saldoRestante !== null && (float)$saldoRestante > 0) {
                    $estadoTexto = 'Saldo Pendiente Bs ' . number_format($saldoRestante, 2);
                } else {
                    $estadoTexto = 'Registrado';
                }
            }

            // Fila resumen de la venta
            $rows->push([
                'Venta N°: ' . $venta->num_comprobante,
                'Fecha: ' . date('d/m/Y H:i', strtotime($venta->fecha_hora)),
                'Cliente: ' . mb_strimwidth($venta->cliente->nombre ?? 'S/N', 0, 40, '...'),
                'Vendedor: ' . ($venta->usuario->persona->nombre ?? 'S/N'),
                'Total: ' . number_format($venta->total, 2),
                'Tipo: ' . $tipoVenta,
                'Estado: ' . $estadoTexto,
                '__META_ESTADO__' => $venta->estado,
                '__META_TIPO__'   => $venta->idtipo_venta,
                '__META_SALDO__'  => $saldoRestante,
            ]);

            // Cabecera de detalle
            $rows->push([
                'Producto', 'Cantidad', 'Precio', 'Descuento', 'Subtotal', '', '', '', ''
            ]);

            // Detalles - SOLO SUMAR SUBTOTALES DE VENTAS REGISTRADAS
            foreach ($venta->detalles as $d) {
                $subtotal = ($d->precio * $d->cantidad) - $d->descuento;

                $rows->push([
                    mb_strimwidth($d->producto->nombre ?? '-', 0, 40, '...'),
                    $d->cantidad,
                    number_format($d->precio, 2),
                    number_format($d->descuento, 2),
                    number_format($subtotal, 2),
                    '', '', '', ''
                ]);
            }
        }

        // Cachear la colección para evitar procesamiento duplicado
        $this->cachedCollection = $rows;
        return $this->cachedCollection;
    }

    public function headings(): array
    {
        return [
            'Reporte Detallado de Ventas',
            '', '', '', '', '', '', '', ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,  // Venta N° y Producto
            'B' => 25,  // Fecha
            'C' => 40,  // Cliente
            'D' => 25,  // Vendedor
            'E' => 20,  // Total
            'F' => 20,  // Estado
            'G' => 35,
            'H' => 10,
            'I' => 10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                    $sheet = $event->sheet->getDelegate();
                    $sheet->getRowDimension(1)->setRowHeight(50);
                    $sheet->getRowDimension(2)->setRowHeight(20);

                    /* ========= HEADER PRINCIPAL ========= */

                    // TÍTULO
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', 'REPORTE DETALLADO DE VENTAS');

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
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

                $sheet->getRowDimension(1)->setRowHeight(32);
                // FECHA PEQUEÑA
                $sheet->mergeCells('A2:G2');
                $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i'));

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'size' => 9,            // 🔹 más pequeña
                        'color' => ['rgb' => 'E6EEF3'], // blanco suave
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0B4F77'], // MISMO azul
                    ],
                ]);

                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo Empresa');
                $drawing->setPath(public_path('img/logoPrincipal.png'));

                // 🔥 Tamaño real del logo
                $drawing->setHeight(45);   // prueba entre 40 y 55
                // NO pongas setWidth si no es necesario (mantiene proporción)

                // Posición
                $drawing->setCoordinates('G1');
                $drawing->setOffsetX(15);
                $drawing->setOffsetY(8);

                $drawing->setWorksheet($sheet);


                // Colorear filas de resumen de ventas
                $highestRow = $sheet->getHighestRow();

for ($row = 1; $row <= $highestRow; $row++) {

    $cellValue = $sheet->getCell("A{$row}")->getValue();

    // Solo filas de VENTA
    if (is_string($cellValue) && str_starts_with($cellValue, 'Venta N°:')) {

        $estadoTexto = $sheet->getCell("G{$row}")->getValue();

        // 🔴 ANULADO
        if (str_contains($estadoTexto, 'Anulado')) {
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F4CCCC'],
                ],
            ]);
        }

        // 🟡 SALDO PENDIENTE
        elseif (str_contains($estadoTexto, 'Saldo Pendiente')) {
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
    }
}



                // Aplicar fondo celeste a encabezados de detalle
                // Usamos la colección cacheada en lugar de llamar al método collection() nuevamente
                $highestRow = $sheet->getHighestRow();

for ($row = 1; $row <= $highestRow; $row++) {

    $cellValue = $sheet->getCell("A{$row}")->getValue();
    $prevValue = $sheet->getCell("A" . ($row - 1))->getValue();

    // Header de PRODUCTOS justo después de una venta
    if (
        $cellValue === 'Producto'
        && is_string($prevValue)
        && str_starts_with($prevValue, 'Venta N°:')
    ) {
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B4F77'], // mismo azul header
            ],
        ]);
    }
}


                // Agregar total de ventas registradas al final
                $row = $sheet->getHighestRow() + 1;
                $sheet->setCellValue('D' . $row, 'Total de Ventas Registradas:');
                $sheet->setCellValue('E' . $row, number_format($this->totalVentasRegistradas, 2));
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->setColor(new Color('D0D0D0'));

                $sheet->getColumnDimension('H')->setVisible(false);
                $sheet->getColumnDimension('I')->setVisible(false);
                $sheet->getColumnDimension('J')->setVisible(false);


            }
        ];
    }
}