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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Facades\DB; 

class VentasDetalladasExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles, WithEvents
{
    protected $filters;
    protected $totalVentasRegistradas = 0;
    protected $cachedCollection;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        if ($this->cachedCollection) {
            return $this->cachedCollection;
        }

        $query = Venta::with(['detalles.producto', 'usuario.persona', 'cliente']);
        
        if (!empty($this->filters['sucursal']) && $this->filters['sucursal'] !== 'undefined') {
             $query->whereHas('usuario', function ($q) { $q->where('idsucursal', $this->filters['sucursal']); });
        }
        
        
        if (!empty($this->filters['tipoReporte'])) {
        
        // REPORTE POR DÍA
            if ($this->filters['tipoReporte'] === 'dia' && !empty($this->filters['fechaSeleccionada'])) {
                $fecha = $this->filters['fechaSeleccionada'];
                $query->whereBetween('fecha_hora', [
                    $fecha . ' 00:00:00',
                    $fecha . ' 23:59:59'
                ]);
            } 
            // REPORTE POR MES
            elseif ($this->filters['tipoReporte'] === 'mes' && !empty($this->filters['mesSeleccionado'])) {
                $mes = $this->filters['mesSeleccionado'];
                $query->whereBetween('fecha_hora', [
                    $mes . '-01 00:00:00',
                    date('Y-m-t', strtotime($mes . '-01')) . ' 23:59:59'
                ]);
            }
        }

    // 3. Filtro ESTADO
        if (!empty($this->filters['estadoVenta']) && $this->filters['estadoVenta'] !== 'Todos' && $this->filters['estadoVenta'] !== 'undefined') {
            $query->where('estado', $this->filters['estadoVenta']);
        }

        // 4. Filtro CLIENTE
        if (!empty($this->filters['idcliente']) && $this->filters['idcliente'] !== 'undefined') {
            $query->where('idcliente', $this->filters['idcliente']);
        }

        $ventas = $query->orderBy('fecha_hora', 'asc')->get();
        $rows = new Collection();

        foreach ($ventas as $venta) {
            // Lógica Saldo
            $saldoRestante = null;
            if ($venta->idtipo_venta == 2) {
                $saldoRestante = DB::table('cuotas_credito')->where('idcredito', $venta->id)->orderByDesc('numero_cuota')->value('saldo_restante');
            }

            // Lógica Estado
            $estadoTexto = 'Registrado';
            if ($venta->estado == 0) {
                $estadoTexto = 'Anulado';
            } else {
                
                // NO sumamos aquí todavía, sumaremos los subtotales calculados abajo para ser exactos
                
                if ($venta->idtipo_venta == 2 && $saldoRestante !== null && (float)$saldoRestante > 0) {
                    $estadoTexto = 'Saldo Pendiente Bs ' . number_format($saldoRestante, 2);
                }
            }

            // FILA SEPARADORA (VENTA)
            $infoVenta = "VENTA #{$venta->num_comprobante} | Fecha: " . date('d/m/Y H:i', strtotime($venta->fecha_hora)) . 
                         " | Cliente: " . ($venta->cliente->nombre ?? 'S/N');
            $infoEconomica = "Total: " . number_format($venta->total, 2) . " (" . ($venta->idtipo_venta == 1 ? 'Contado' : 'Crédito') . ")";

            $rows->push([
                $infoVenta, '', '', '', '', $infoEconomica, $estadoTexto
            ]);

            $sumaSubtotalesVenta = 0;

            // DETALLES PRODUCTOS
            foreach ($venta->detalles as $d) {
                $modo = strtolower($d->modo_venta ?? 'unidad'); 
                
                // Visualización de texto cantidad (ej: 2 cajas)
                $plural = ($d->cantidad > 1 && substr($modo, -1) != 's') ? 's' : '';
                $textoCantidad = $d->cantidad . ' ' . $modo . $plural;

                $producto = $d->producto;
                $codigo = $producto->codigo ?? '-';
                $nombreProducto = $producto->nombre ?? '-';
                
                // Usamos 'unidad_envase' igual que en el PDF (si es null o 0, es 1)
                $cantPorCaja = (isset($producto->unidad_envase) && $producto->unidad_envase > 0) 
                                ? $producto->unidad_envase 
                                : 1;

                // ============================================================
                // 🔹 LÓGICA MATEMÁTICA IDÉNTICA AL PDF
                // ============================================================
                $subtotalLinea = 0;
                $precioUnitario = $d->precio;
                $textoUnidadCajaVisual = '-'; // Por defecto guion

                if ($modo == 'caja') {
                    // Cajas * Unidades * Precio
                    $subtotalLinea = $d->cantidad * $cantPorCaja * $precioUnitario;
                    $textoUnidadCajaVisual = $cantPorCaja; // Solo mostramos el número si es caja

                } elseif ($modo == 'docena') {
                    // Docenas * 12 * Precio
                    $subtotalLinea = $d->cantidad * 12 * $precioUnitario;
                    
                } else {
                    // Unidades * Precio
                    $subtotalLinea = $d->cantidad * $precioUnitario;
                }
                // ============================================================

                // Sumamos al acumulador de esta venta
                $sumaSubtotalesVenta += $subtotalLinea;

                $rows->push([
                    $textoCantidad,
                    $codigo,
                    $nombreProducto,
                    $textoUnidadCajaVisual, // Ahora muestra guion si no es caja
                    number_format($d->precio, 2),
                    number_format($subtotalLinea, 2),
                    '' 
                ]);
            }

            // Sumar al total global solo si no está anulada
            if ($venta->estado != 0) {
                $this->totalVentasRegistradas += $sumaSubtotalesVenta;
            }
        }

        $this->cachedCollection = $rows;
        return $this->cachedCollection;
    }

    public function headings(): array
    {
        return [
            ['REPORTE DETALLADO DE VENTAS'],
            ['Fecha de generación: ' . date('d/m/Y H:i')],
            ['Cant.', 'Código', 'Producto', 'U. x Caja', 'P. Unitario', 'Subtotal', 'Datos de Venta / Estado']
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 25,
            'C' => 60,
            'D' => 15,
            'E' => 22,
            'F' => 22,
            'G' => 30,
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
                $sheet->setShowGridlines(false);

                // --- HEADER PRINCIPAL ---
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', 'REPORTE DETALLADO DE VENTAS');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B4F77']],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                $sheet->mergeCells('A2:G2');
                $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => 'E6EEF3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B4F77']],
                ]);

                // LOGO (Asegúrate que la ruta sea correcta)
                if(file_exists(public_path('img/logoPrincipal.png'))) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setPath(public_path('img/logoPrincipal.png'));
                    $drawing->setHeight(45);
                    $drawing->setCoordinates('G1');
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(8);
                    $drawing->setWorksheet($sheet);
                }

                // --- CABECERAS DE TABLA (FILA 3) ---
                $sheet->getStyle('A3:G3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B4F77']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]]
                ]);
                $sheet->getRowDimension(3)->setRowHeight(25);

                $highestRow = $sheet->getHighestRow();

                for ($row = 4; $row <= $highestRow; $row++) {
                    $valA = $sheet->getCell("A{$row}")->getValue();

                    // ¿ES FILA DE SEPARACIÓN (VENTA)?
                    if (is_string($valA) && str_contains($valA, 'VENTA #')) {
                        $sheet->mergeCells("A{$row}:E{$row}");
                        $estado = $sheet->getCell("G{$row}")->getValue();
                        
                        $bgColor = 'E0E0E0'; $textColor = '000000';
                        if ($estado == 'Anulado') { $bgColor = 'F4CCCC'; $textColor = 'CC0000'; } 
                        elseif (str_contains($estado ?? '', 'Saldo')) { $bgColor = 'FFF2CC'; }

                        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                            'font' => ['bold' => true, 'color' => ['rgb' => $textColor]],
                            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                            'borders' => [
                                'top' => ['borderStyle' => Border::BORDER_THIN],
                                'bottom' => ['borderStyle' => Border::BORDER_THIN],
                            ]
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(25);
                    } 
                    // ¿ES FILA DE PRODUCTO?
                    else {
                        $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);

                        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => 'D0D0D0']
                                ],
                            ],
                            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(28);
                    }
                }

                // TOTAL GENERAL
                $row = $sheet->getHighestRow() + 1;
                $sheet->setCellValue('E' . $row, 'TOTAL GENERAL:');
                $sheet->setCellValue('F' . $row, number_format($this->totalVentasRegistradas, 2));
                
                $sheet->getStyle("E{$row}:F{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                $sheet->getRowDimension($row)->setRowHeight(30);
            }
        ];
    }
}