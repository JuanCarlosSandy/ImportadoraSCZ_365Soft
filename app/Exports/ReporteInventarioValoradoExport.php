<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
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

class ReporteInventarioValoradoExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithCustomStartCell, WithEvents, WithDrawings
{
    protected $idAlmacen;
    protected $idLaboratorio;
    protected $idPresentacion;
    protected $buscar;
    protected $criterio;
    protected $tipoSeleccionado;
    protected $nombreAlmacen;
    protected $nombreLaboratorio;
    protected $nombrePresentacion;
    protected $fechaGeneracion;

    public function __construct($idAlmacen, $idLaboratorio = null, $idPresentacion = null, $buscar = null, $criterio = null, $tipoSeleccionado = null, $nombreAlmacen = null, $nombreLaboratorio = null, $nombrePresentacion = null)
    {
        $this->idAlmacen = $idAlmacen;
        $this->idLaboratorio = $idLaboratorio;
        $this->idPresentacion = $idPresentacion;
        $this->buscar = $buscar;
        $this->criterio = $criterio;
        $this->tipoSeleccionado = $tipoSeleccionado;
        $this->nombreAlmacen = $nombreAlmacen ?? 'Todos';
        $this->nombreLaboratorio = $nombreLaboratorio ?? 'Todos';
        $this->nombrePresentacion = $nombrePresentacion ?? 'Todos';
        $this->fechaGeneracion = date('d/m/Y H:i:s');
    }

    public function collection()
    {
        $idAlmacen = $this->idAlmacen;
        $idLaboratorio = $this->idLaboratorio;
        $idPresentacion = $this->idPresentacion;
        $buscar = $this->buscar;

        $inventarios = DB::table('articulos')
            ->leftJoin('inventarios', function ($join) use ($idAlmacen) {
                $join->on('articulos.id', '=', 'inventarios.idarticulo')
                    ->where('inventarios.idalmacen', '=', $idAlmacen);
            })
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->leftJoin('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'articulos.nombre as nombre_producto',
                'articulos.unidad_envase',
                DB::raw('ROUND(articulos.precio_costo_unid, 2) as precio_costo_unid'),
                'almacens.nombre_almacen',
                'personas.nombre as nombre_proveedor',
                'articulos.precio_uno as precio_venta',
                'categorias.nombre as nombre_categoria',
                DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) as saldo_stock_total'),
                DB::raw('FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / articulos.unidad_envase) as stock_en_paquetes'),
                DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) % articulos.unidad_envase as unidades_restantes'),
                DB::raw('ROUND(articulos.precio_costo_unid * IFNULL(SUM(inventarios.saldo_stock), 0), 2) as valor_total')
            )
            ->where('articulos.condicion', '=', 1);

        // 🔹 Filtros opcionales
        if (!empty($idLaboratorio)) {
            $inventarios->where('articulos.idproveedor', $idLaboratorio);
        }

        if (!empty($idPresentacion)) {
            $inventarios->where('articulos.idcategoria', $idPresentacion);
        }

        if (!empty($buscar)) {
            $inventarios->where(function ($query) use ($buscar) {
                $query->where('articulos.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('almacens.nombre_almacen', 'like', '%' . $buscar . '%');
            });
        }

        $data = $inventarios
            ->groupBy(
                'articulos.nombre',
                'almacens.nombre_almacen',
                'articulos.unidad_envase',
                'articulos.precio_costo_unid',
                'categorias.nombre',
                'personas.nombre',
                'articulos.precio_uno'
            )
            ->orderBy('articulos.nombre')
            ->orderBy('almacens.nombre_almacen')
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            'Almacén',
            'Producto',
            'Categoría',
            'Proveedor',
            'Precio Venta',
            'Precio Costo (Unid)',
            'Stock Total',
            'Valor Total',
        ];
    }

    public function map($row): array
    {
        return [
            $row->nombre_almacen,
            $row->nombre_producto,
            $row->nombre_categoria,  
            $row->nombre_proveedor,
            $row->precio_venta,
            $row->precio_costo_unid,
            $row->saldo_stock_total,
            $row->valor_total,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  
            'B' => 40,  
            'C' => 25,  
            'D' => 30,  
            'E' => 15,  
            'F' => 18,  
            'G' => 15,  
            'H' => 18,  
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
                $sheet->mergeCells('C2:H2');
                $sheet->setCellValue('C2', 'REPORTE DE INVENTARIO FÍSICO VALORADO');
                $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Fecha
                $sheet->mergeCells('C3:H3');
                $sheet->setCellValue('C3', 'Fecha de generación: ' . $this->fechaGeneracion);
                $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Filtros
                $sheet->setCellValue('A5', 'Almacén: ' . $this->nombreAlmacen);
                $sheet->setCellValue('E5', 'Proveedor: ' . $this->nombreLaboratorio);
                $sheet->setCellValue('A6', 'Categoría: ' . $this->nombrePresentacion);
                $sheet->setCellValue('E6', 'Búsqueda: ' . (!empty($this->buscar) ? $this->buscar : 'Ninguna'));

                $sheet->getStyle('A5:H6')->getFont()->setBold(true);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el encabezado de la tabla
        $sheet->getStyle('A8:H8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Estilo para todo el contenido (bordes y alineación vertical)
        $sheet->getStyle('A9:H' . $sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Alinear columnas numéricas a la derecha
        $sheet->getStyle('E9:H' . $sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        return [];
    }
}
