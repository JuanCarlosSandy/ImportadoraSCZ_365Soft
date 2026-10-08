<?php

namespace App\Exports;

use App\Inventario;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosBajoStockExport implements FromQuery, WithHeadings, WithColumnWidths, WithStyles, WithEvents
{
    use Exportable;

    protected $almacen_id;
    protected $medicamento;
    protected $laboratorio;
    protected $codigo;

    public function __construct($almacen_id, $medicamento, $laboratorio, $codigo = null)
    {
        $this->almacen_id = ($almacen_id === 'null' || $almacen_id === '')
            ? null
            : $almacen_id;

        $this->medicamento = ($medicamento === 'null')
            ? ''
            : $medicamento;

        $this->laboratorio = ($laboratorio === 'null')
            ? ''
            : $laboratorio;

        $this->codigo = ($codigo === 'null')
            ? ''
            : trim((string) $codigo);
    }

    public function query()
    {
        $usuario = \Auth::user();

        $query = Inventario::join(
            'almacens',
            'inventarios.idalmacen',
            '=',
            'almacens.id'
        )
            ->join(
                'articulos',
                'inventarios.idarticulo',
                '=',
                'articulos.id'
            )
            ->where('articulos.condicion', 1)
            ->leftJoin(
                'proveedores',
                'articulos.idproveedor',
                '=',
                'proveedores.id'
            )
            ->leftJoin(
                'personas',
                'proveedores.id',
                '=',
                'personas.id'
            )

            // Stock del producto en el almacén con ID 2
            ->leftJoin(
                DB::raw('(
                    SELECT
                        idarticulo,
                        SUM(saldo_stock) AS stock_almacen_2
                    FROM inventarios
                    WHERE idalmacen = 2
                    GROUP BY idarticulo
                ) AS inventario_almacen_2'),
                'inventario_almacen_2.idarticulo',
                '=',
                'inventarios.idarticulo'
            )

            ->select(
                'articulos.codigo',
                'almacens.nombre_almacen',
                'articulos.nombre as nombre_producto',

                DB::raw(
                    "COALESCE(personas.nombre, 'Sin proveedor') as nombre_proveedor"
                ),

                'articulos.stock as stock_minimo',

                DB::raw(
                    'SUM(inventarios.saldo_stock) as saldo_stock'
                ),

                DB::raw(
                    'COALESCE(
                        inventario_almacen_2.stock_almacen_2,
                        0
                    ) as stock_almacen_2'
                ),

                DB::raw('(CASE
                    WHEN SUM(inventarios.saldo_stock) = 0
                        THEN "Sin Stock"
                    ELSE "Bajo Stock"
                END) as estado')
            )

            ->groupBy(
                'articulos.codigo',
                'almacens.nombre_almacen',
                'articulos.nombre',
                DB::raw(
                    "COALESCE(personas.nombre, 'Sin proveedor')"
                ),
                'articulos.stock',
                'inventario_almacen_2.stock_almacen_2'
            )

            ->havingRaw(
                'SUM(inventarios.saldo_stock) <= articulos.stock'
            );

        // Filtrar por sucursal del usuario
        if ($usuario && $usuario->idrol != 4) {
            $query->where(
                'almacens.sucursal',
                $usuario->idsucursal
            );
        }

        // Filtro por almacén
        if ($this->almacen_id) {
            $query->where(
                'inventarios.idalmacen',
                $this->almacen_id
            );
        }

        // Filtro por medicamento
        if ($this->medicamento) {
            $query->where(
                'articulos.nombre',
                'like',
                '%' . $this->medicamento . '%'
            );
        }

        // Filtro por laboratorio / proveedor
        if ($this->laboratorio) {
            $query->whereRaw(
                "COALESCE(personas.nombre, 'Sin proveedor') like ?",
                ['%' . $this->laboratorio . '%']
            );
        }

        // Filtro por código
        if ($this->codigo) {
            $query->where(
                'articulos.codigo',
                'like',
                '%' . $this->codigo . '%'
            );
        }

        // Orden:
        // 1. Almacén
        // 2. Stock actual ascendente
        // 3. Nombre del producto
        return $query
            ->orderBy(
                'almacens.nombre_almacen',
                'asc'
            )
            ->orderByRaw(
                'SUM(inventarios.saldo_stock) ASC'
            )
            ->orderBy(
                'articulos.nombre',
                'asc'
            );
    }

    public function headings(): array
    {
        return [
            'Codigo',
            'Almacen',
            'Producto',
            'Proveedor',
            'Stock Minimo',
            'Stock Actual',
            'Stock Deposito',
            'Estado'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 25,
            'C' => 34,
            'D' => 28,
            'E' => 15,
            'F' => 15,
            'G' => 18,
            'H' => 15
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12
                ]
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // Espacio para título, fecha y filtros
                $sheet->insertNewRowBefore(1, 4);

                // Título
                $sheet->setCellValue(
                    'A1',
                    'INFORME DE PRODUCTOS BAJO STOCK'
                );

                $sheet->mergeCells('A1:H1');

                $sheet->getStyle('A1')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(14);

                $sheet->getStyle('A1')
                    ->getAlignment()
                    ->setHorizontal('center');

                // Fecha
                $sheet->setCellValue(
                    'A2',
                    'Generado el ' . date('d/m/Y H:i')
                );

                $sheet->mergeCells('A2:H2');

                $sheet->getStyle('A2')
                    ->getAlignment()
                    ->setHorizontal('center');

                // Filtros
                $filtrosTexto = [];

                if ($this->almacen_id) {

                    $nombre = DB::table('almacens')
                        ->where('id', $this->almacen_id)
                        ->value('nombre_almacen');

                    $filtrosTexto[] =
                        'Almacen: ' . ($nombre ?? 'Desconocido');

                } else {

                    $filtrosTexto[] =
                        'Almacen: Todos';
                }

                $filtrosTexto[] =
                    'Producto: ' .
                    ($this->medicamento ?: 'Todos');

                $filtrosTexto[] =
                    'Laboratorio: ' .
                    ($this->laboratorio ?: 'Todos');

                $filtrosTexto[] =
                    'Codigo: ' .
                    ($this->codigo ?: 'Todos');

                $sheet->setCellValue(
                    'A3',
                    'Filtros: ' .
                    implode(' | ', $filtrosTexto)
                );

                $sheet->mergeCells('A3:H3');

                $sheet->getStyle('A3')
                    ->getFont()
                    ->setItalic(true)
                    ->setColor(
                        new \PhpOffice\PhpSpreadsheet\Style\Color(
                            '555555'
                        )
                    );

                $sheet->getStyle('A3')
                    ->getAlignment()
                    ->setHorizontal('center');

                // Agrupación visual por almacén
                $highestRow = $sheet->getHighestRow();

                $lastAlmacen = '';

                for ($row = 6; $row <= $highestRow; $row++) {

                    $almacen = $sheet
                        ->getCell("B$row")
                        ->getValue();

                    if (
                        $almacen !== $lastAlmacen &&
                        $almacen != ''
                    ) {

                        $sheet->insertNewRowBefore(
                            $row,
                            1
                        );

                        $sheet->setCellValue(
                            "A$row",
                            'ALMACEN: ' . $almacen
                        );

                        $sheet->mergeCells(
                            "A$row:H$row"
                        );

                        $sheet->getStyle("A$row")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(12);

                        $sheet->getStyle("A$row")
                            ->getFill()
                            ->setFillType(
                                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
                            )
                            ->getStartColor()
                            ->setARGB('D9D9D9');

                        $lastAlmacen = $almacen;

                        $row++;
                        $highestRow++;
                    }

                    // Estado ahora está en la columna H
                    $estado = $sheet
                        ->getCell("H$row")
                        ->getValue();

                    // Sin stock
                    if ($estado === 'Sin Stock') {

                        $sheet->getStyle(
                            "A$row:H$row"
                        )
                            ->getFill()
                            ->setFillType(
                                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
                            )
                            ->getStartColor()
                            ->setARGB('FF9999');

                    // Bajo stock
                    } elseif ($estado === 'Bajo Stock') {

                        $sheet->getStyle(
                            "A$row:H$row"
                        )
                            ->getFill()
                            ->setFillType(
                                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
                            )
                            ->getStartColor()
                            ->setARGB('FFFF99');
                    }
                }
            }
        ];
    }
}