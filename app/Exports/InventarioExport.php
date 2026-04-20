<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InventarioExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithCustomStartCell, WithEvents, WithDrawings
{
    protected $modo;
    protected $idAlmacen;
    protected $nombreAlmacen;
    protected $buscar;
    protected $fechaGeneracion;

    public function __construct($modo, $idAlmacen, $buscar = '')
    {
        $this->modo = $modo;
        $this->idAlmacen = $idAlmacen;
        $this->buscar = $buscar;
        $this->fechaGeneracion = date('d/m/Y H:i:s');
        
        // Obtener nombre del almacén
        $almacen = DB::table('almacens')->where('id', $idAlmacen)->first();
        $this->nombreAlmacen = $almacen ? $almacen->nombre_almacen : 'Todos';
    }

    public function query()
    {
        $query = DB::table('articulos')
            ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->leftJoin('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->where('inventarios.idalmacen', $this->idAlmacen)
            ->where('articulos.condicion', 1);

        if (!empty($this->buscar)) {
            $query->where(function ($q) {
                $q->where('articulos.nombre', 'like', '%' . $this->buscar . '%')
                  ->orWhere('categorias.nombre', 'like', '%' . $this->buscar . '%')
                  ->orWhere('personas.nombre', 'like', '%' . $this->buscar . '%');
            });
        }

        if ($this->modo === 'item') {
            return $query->select(
                    'articulos.nombre as nombre_producto',
                    'categorias.nombre as categoria',
                    'personas.nombre as proveedor',
                    'articulos.unidad_envase',
                    DB::raw('SUM(inventarios.saldo_stock) as stock_unidades')
                )
                ->groupBy('articulos.id', 'articulos.nombre', 'personas.nombre', 'articulos.unidad_envase', 'categorias.nombre')
                ->orderBy('categorias.nombre')
                ->orderBy('articulos.nombre');
        } else {
            return $query->select(
                    'articulos.nombre as nombre_producto',
                    'categorias.nombre as categoria',
                    'personas.nombre as proveedor',
                    'articulos.unidad_envase',
                    'inventarios.saldo_stock',
                    DB::raw('FLOOR(inventarios.saldo_stock / COALESCE(articulos.unidad_envase, 1)) as stock_cajas'),
                    'inventarios.created_at',
                    'inventarios.fecha_vencimiento'
                )
                ->orderBy('categorias.nombre')
                ->orderBy('articulos.nombre');
        }
    }

    public function headings(): array
    {
        return $this->modo === 'item'
            ? ['Nombre del producto', 'Categoría', 'Proveedor', 'Unidades por paquete', 'Stock Actual']
            : ['Nombre del producto', 'Categoría', 'Proveedor', 'Unidades por paquete', 'Stock en unidades', 'Stock en cajas', 'Fecha Ingreso', 'Fecha Vencimiento'];
    }

    public function map($row): array
    {
        if ($this->modo === 'item') {
            return [
                $row->nombre_producto,
                $row->categoria ?? 'Sin categoría',
                $row->proveedor,
                $row->unidad_envase,
                $row->stock_unidades,
            ];
        } else {
            return [
                $row->nombre_producto,
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
            'A' => 45, // Nombre del producto
            'B' => 25, // Categoría
            'C' => 30, // Proveedor
            'D' => 20, // Unidades por paquete
            'E' => 15, // Stock Actual / Unidades
            'F' => 15, // Stock en cajas (solo lote)
            'G' => 20, // Fecha Ingreso (solo lote)
            'H' => 20, // Fecha Vencimiento (solo lote)
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

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->modo === 'item' ? 'E' : 'H';
        $highestRow = $sheet->getHighestRow();

        // Estilo para el encabezado de la tabla (fila 8)
        $sheet->getStyle('A8:' . $lastColumn . '8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Estilo para todo el contenido (bordes y alineación vertical)
        if ($highestRow >= 9) {
            $sheet->getStyle('A9:' . $lastColumn . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Alinear columnas numéricas a la derecha (desde D en adelante)
            $sheet->getStyle('D9:' . $lastColumn . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $lastColumn = $this->modo === 'item' ? 'E' : 'H';

                // Título
                $sheet->mergeCells('C2:' . $lastColumn . '2');
                $sheet->setCellValue('C2', 'REPORTE DE INVENTARIO');
                $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Fecha
                $sheet->mergeCells('C3:' . $lastColumn . '3');
                $sheet->setCellValue('C3', 'Fecha de generación: ' . $this->fechaGeneracion);
                $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Filtros
                $sheet->setCellValue('A5', 'Almacén: ' . $this->nombreAlmacen);
                $sheet->setCellValue('A6', 'Búsqueda: ' . (!empty($this->buscar) ? $this->buscar : 'Ninguna'));
                $sheet->getStyle('A5:H6')->getFont()->setBold(true);
            },
        ];
    }
}
