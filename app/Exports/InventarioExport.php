<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InventarioExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithEvents
{
    protected $modo;
    protected $idAlmacen;
    protected $nombreAlmacen;

    public function __construct($modo, $idAlmacen)
    {
        $this->modo = $modo;
        $this->idAlmacen = $idAlmacen;
        
        // Obtener nombre del almacén
        $almacen = DB::table('almacens')->where('id', $idAlmacen)->first();
        $this->nombreAlmacen = $almacen ? $almacen->nombre_almacen : 'Desconocido';
    }

    public function query()
    {
        if ($this->modo === 'item') {
            return DB::table('articulos')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('personas', 'proveedores.id', '=', 'personas.id')
                ->leftJoin('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->select(
                    'articulos.nombre as nombre_producto',
                    'categorias.nombre as categoria',
                    'personas.nombre as proveedor',
                    'articulos.unidad_envase',
                    DB::raw('SUM(inventarios.saldo_stock) as stock_unidades'),
                    DB::raw('FLOOR(SUM(inventarios.saldo_stock) / COALESCE(articulos.unidad_envase, 1)) as stock_cajas')
                )
                ->where('inventarios.idalmacen', $this->idAlmacen)
                ->where('articulos.condicion', 1)
                ->groupBy('articulos.id', 'articulos.nombre', 'personas.nombre', 'articulos.unidad_envase', 'categorias.nombre')
                ->orderBy('personas.nombre')
                ->orderBy('articulos.nombre');
        } else {
            return DB::table('articulos')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('personas', 'proveedores.id', '=', 'personas.id')
                ->leftJoin('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->select(
                    'articulos.nombre as nombre_producto',
                    'categorias.nombre as categoria',
                    'personas.nombre as proveedor',
                    'articulos.unidad_envase',
                    'inventarios.saldo_stock',
                    'inventarios.created_at',
                    'inventarios.fecha_vencimiento',
                    DB::raw('FLOOR(inventarios.saldo_stock / COALESCE(articulos.unidad_envase, 1)) as stock_cajas')
                )
                ->where('inventarios.idalmacen', $this->idAlmacen)
                ->where('articulos.condicion', 1)
                ->orderBy('personas.nombre')
                ->orderBy('articulos.nombre');
        }
    }

    public function headings(): array
    {
        return $this->modo === 'item'
            ? ['Nombre del producto', 'Categoría', 'Proveedor', 'Unidades por paquete', 'Stock en unidades', 'Stock en cajas']
            : ['Nombre del producto', 'Categoría', 'Proveedor', 'Unidades por paquete', 'Stock en unidades', 'Stock en cajas', 'Fecha Ingreso', 'Fecha Vencimiento'];
    }

    public function map($row): array
    {
        if ($this->modo === 'item') {
            return [
                substr($row->nombre_producto, 0, 70),
                $row->categoria ?? 'Sin categoría',
                $row->proveedor,
                $row->unidad_envase,
                $row->stock_unidades,
                $row->stock_cajas,
            ];
        } else {
            return [
                substr($row->nombre_producto, 0, 70),
                $row->categoria ?? 'Sin categoría',
                $row->proveedor,
                $row->unidad_envase,
                $row->saldo_stock,
                $row->stock_cajas,
                date('d/m/Y', strtotime($row->created_at)),
                date('d/m/Y', strtotime($row->fecha_vencimiento)),
            ];
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35, // Nombre del producto
            'B' => 18, // Categoría
            'C' => 20, // Proveedor
            'D' => 18, // Unidades por paquete
            'E' => 18, // Stock en unidades
            'F' => 15, // Stock en cajas
            'G' => 18, // Fecha Ingreso (solo lote)
            'H' => 18, // Fecha Vencimiento (solo lote)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headings = $this->modo === 'item' ? 6 : 8;

        // Encabezado de datos en la fila 4 (después de las 3 filas de encabezado gráfico)
        // Los estilos se aplican en el evento AfterSheet, aquí dejamos estilos adicionales
        
        // Resaltar filas con stock en unidades = 0 (columna E, a partir de fila 5)
        $column = 'E';
        $lastRow = $sheet->getHighestRow();

        for ($row = 5; $row <= $lastRow; $row++) {
            $cell = $column . $row;
            $value = $sheet->getCell($cell)->getValue();

            if (is_numeric($value) && (int)$value === 0) {
                $sheet->getStyle("A$row:" . chr(64 + $headings) . "$row")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'F8D7DA'], // rojo suave
                    ],
                ]);
            }
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Insertar 3 filas al inicio para el encabezado
                $sheet->insertNewRowBefore(1, 3);
                
                // Agregar título
                $title = 'REPORTE DE INVENTARIO';
                $sheet->setCellValue('A1', $title);
                $sheet->mergeCells('A1:F1');
                
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => '212234'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Agregar nombre del almacén
                $sheet->setCellValue('A2', 'Almacén: ' . $this->nombreAlmacen);
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'size' => 11,
                        'color' => ['rgb' => '212234'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                
                // Agregar fecha
                $sheet->setCellValue('A3', 'Fecha: ' . date('d/m/Y H:i:s'));
                $sheet->mergeCells('A3:F3');
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'color' => ['rgb' => '212234'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                
                // Agregar logo
                $logoPath = public_path('img/logoPrincipal.png');
                if (file_exists($logoPath)) {
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setName('Logo');
                    $drawing->setDescription('Logo Empresa');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(65);
                    $drawing->setCoordinates('F1');
                    $drawing->setOffsetX(15);
                    $drawing->setOffsetY(8);
                    $drawing->setWorksheet($sheet);
                }
                
                // Ajustar altura de filas de encabezado
                $sheet->getRowDimension(1)->setRowHeight(45);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(20);
                
                // Aplicar estilos al encabezado de datos (fila 4)
                $headings = $this->modo === 'item' ? 6 : 8;
                $sheet->getStyle('A4:' . chr(64 + $headings) . '4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '212234'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}
