<?php

namespace App\Exports;

use App\Inventario;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class ProductosBajoStockExport implements FromQuery, WithHeadings, WithColumnWidths, WithStyles, WithEvents
{
    use Exportable;

    protected $almacen_id;
    protected $medicamento;
    protected $laboratorio;

    public function __construct($almacen_id, $medicamento, $laboratorio)
    {
        $this->almacen_id = ($almacen_id === 'null' || $almacen_id === '') ? null : $almacen_id;
        $this->medicamento = ($medicamento === 'null') ? '' : $medicamento;
        $this->laboratorio = ($laboratorio === 'null') ? '' : $laboratorio;
    }

    public function query()
    {
        $usuario = \Auth::user();

       $query = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
    ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
    ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
    ->join('personas', 'proveedores.id', '=', 'personas.id')
    ->select(
        'almacens.nombre_almacen',
        'articulos.nombre as nombre_producto',
        'personas.nombre as nombre_proveedor',
        'articulos.stock as stock_minimo',
        DB::raw('SUM(inventarios.saldo_stock) as saldo_stock'),
        DB::raw('(CASE 
            WHEN SUM(inventarios.saldo_stock) = 0 THEN "Sin Stock" 
            ELSE "Bajo Stock" 
        END) as estado')
    )
    ->groupBy(
        'almacens.nombre_almacen',
        'articulos.nombre',
        'personas.nombre',
        'articulos.stock'
    )
    ->havingRaw('SUM(inventarios.saldo_stock) <= articulos.stock');


        if ($usuario && $usuario->idrol != 4) {
            $query->where('almacens.sucursal', $usuario->idsucursal);
        }

        if ($this->almacen_id) {
            $query->where('inventarios.idalmacen', $this->almacen_id);
        }
        if ($this->medicamento) {
            $query->where('articulos.nombre', 'like', '%' . $this->medicamento . '%');
        }
        if ($this->laboratorio) {
            $query->where('personas.nombre', 'like', '%' . $this->laboratorio . '%');
        }

        return $query
            ->orderBy('almacens.nombre_almacen', 'asc')
            ->orderBy('personas.nombre', 'asc');
    }

    public function headings(): array
    {
        return ['Almacén', 'Producto', 'Proveedor', 'Stock Mínimo', 'Stock Actual', 'Estado'];
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 35, 'C' => 25, 'D' => 15, 'E' => 15, 'F' => 15];
    }

    public function styles(Worksheet $sheet)
    {
        return [ 1 => ['font' => ['bold' => true, 'size' => 12]] ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                
                $sheet->insertNewRowBefore(1, 4);

                $sheet->setCellValue('A1', 'INFORME DE PRODUCTOS BAJO STOCK');
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                $sheet->setCellValue('A2', 'Generado el ' . date('d/m/Y H:i'));
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

                
                $filtrosTexto = [];
                if ($this->almacen_id) {
                   $nombre = DB::table('almacens')->where('id', $this->almacen_id)->value('nombre_almacen');
                   $filtrosTexto[] = "Almacén: " . ($nombre ?? 'Desconocido');
                } else { $filtrosTexto[] = "Almacén: Todos"; }
                
                $sheet->setCellValue('A3', "Filtros: " . implode(" | ", $filtrosTexto));
                $sheet->mergeCells('A3:F3');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('555555'));
                $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');


                
                $highestRow = $sheet->getHighestRow();
                $lastAlmacen = '';
                
                
                for ($row = 6; $row <= $highestRow; $row++) {
                    
                    $almacen = $sheet->getCell("A$row")->getValue();

                    
                    if ($almacen !== $lastAlmacen && $almacen != '') {
                        $sheet->insertNewRowBefore($row, 1);
                        $sheet->setCellValue("A$row", $almacen);
                        $sheet->mergeCells("A$row:F$row");
                        
                        $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(12);
                        $sheet->getStyle("A$row")->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('D9D9D9'); 
                        
                        $lastAlmacen = $almacen;
                        $row++; 
                        $highestRow++; 
                    }

                    
                    
                    $estado = $sheet->getCell("F$row")->getValue();
                    
                    if ($estado === 'Sin Stock') {
                        
                        $sheet->getStyle("A$row:F$row")->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FF9999'); 
                    } 
                    elseif ($estado === 'Bajo Stock') {
                        
                        $sheet->getStyle("A$row:F$row")->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFFF99'); 
                    }
                }
            }
        ];
    }
}