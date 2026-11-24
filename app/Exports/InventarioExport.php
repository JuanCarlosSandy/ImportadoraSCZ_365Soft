<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class InventarioExport extends DefaultValueBinder implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithColumnWidths,
    WithStyles,
    WithCustomStartCell,
    WithEvents,
    WithDrawings,
    WithCustomValueBinder
{
    protected $consulta;
    protected $modo;
    protected $idAlmacen;
    protected $nombreAlmacen;
    protected $almacenesColumnas;
    protected $buscar;
    protected $orden;
    protected $fechaGeneracion;

    public function __construct(
        $consulta,
        $modo,
        $idAlmacen,
        $nombreAlmacen,
        $almacenesColumnas,
        $buscar = '',
        $orden = 'stock_asc'
    ) {
        $this->consulta = $consulta;
        $this->modo = $modo;
        $this->idAlmacen = (int) $idAlmacen;
        $this->nombreAlmacen = $nombreAlmacen;
        $this->almacenesColumnas = $almacenesColumnas;
        $this->buscar = $buscar;
        $this->orden = $orden;
        $this->fechaGeneracion = date('d/m/Y H:i:s');
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Consulta principal
    |--------------------------------------------------------------------------
    */

    public function query()
    {
        return $this->consulta;
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Definir columnas dinámicas
    |--------------------------------------------------------------------------
    */

    private function obtenerColumnas()
    {
        if ($this->modo === 'item') {

            $columnas = [
                [
                    'campo' => 'codigo',
                    'titulo' => 'Código',
                    'ancho' => 20,
                    'tipo' => 'texto'
                ],
                [
                    'campo' => 'nombre_producto',
                    'titulo' => 'Producto',
                    'ancho' => 55,
                    'tipo' => 'texto'
                ],
                [
                    'campo' => 'saldo_stock_total',
                    'titulo' => 'Stock Actual',
                    'ancho' => 22,
                    'tipo' => 'stock'
                ]
            ];

            // Mostrar depósito si no es el seleccionado
            if ($this->idAlmacen !== 2) {

                $columnas[] = [
                    'campo' => 'stock_deposito',
                    'titulo' => 'Stock Depósito',
                    'ancho' => 22,
                    'tipo' => 'stock'
                ];
            }

            // Almacenes del mismo departamento
            foreach ($this->almacenesColumnas as $almacen) {

                $columnas[] = [
                    'campo' => 'stock_almacen_' . $almacen->id,
                    'titulo' => $almacen->nombre_almacen,
                    'ancho' => max(
                        22,
                        min(40, mb_strlen($almacen->nombre_almacen) + 5)
                    ),
                    'tipo' => 'stock'
                ];
            }

            return $columnas;
        }

        /*
        |--------------------------------------------------------------------------
        | Columnas para modo lote
        |--------------------------------------------------------------------------
        */

        return [
            [
                'campo' => 'nombre_producto',
                'titulo' => 'Producto',
                'ancho' => 55,
                'tipo' => 'texto'
            ],
            [
                'campo' => 'nombre_proveedor',
                'titulo' => 'Proveedor',
                'ancho' => 35,
                'tipo' => 'texto'
            ],
            [
                'campo' => 'unidad_envase',
                'titulo' => 'Unid/Paq',
                'ancho' => 18,
                'tipo' => 'numero'
            ],
            [
                'campo' => 'cantidad',
                'titulo' => 'Cantidad',
                'ancho' => 18,
                'tipo' => 'numero'
            ],
            [
                'campo' => 'saldo_stock',
                'titulo' => 'Stock Actual',
                'ancho' => 22,
                'tipo' => 'stock'
            ],
            [
                'campo' => 'fecha_ingreso',
                'titulo' => 'Fecha Ingreso',
                'ancho' => 22,
                'tipo' => 'fecha'
            ],
            [
                'campo' => 'fecha_vencimiento',
                'titulo' => 'Fecha Vencimiento',
                'ancho' => 24,
                'tipo' => 'fecha'
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Obtener última columna del Excel
    |--------------------------------------------------------------------------
    */

    private function obtenerUltimaColumna()
    {
        return Coordinate::stringFromColumnIndex(
            count($this->obtenerColumnas())
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Encabezados dinámicos
    |--------------------------------------------------------------------------
    */

    public function headings(): array
    {
        $encabezados = [];

        foreach ($this->obtenerColumnas() as $columna) {
            $encabezados[] = $columna['titulo'];
        }

        return $encabezados;
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Formatear stock
    |--------------------------------------------------------------------------
    */

    private function formatearStock($stock, $unidad = '')
    {
        $stock = (float) ($stock ?? 0);

        if ($stock == 0) {
            return 'Sin Stock';
        }

        $cantidad = rtrim(
            rtrim(
                number_format($stock, 4, '.', ''),
                '0'
            ),
            '.'
        );

        return trim($cantidad . ' ' . $unidad);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Mapear registros
    |--------------------------------------------------------------------------
    */

    public function map($row): array
    {
        $datos = [];

        foreach ($this->obtenerColumnas() as $columna) {

            $campo = $columna['campo'];
            $valor = $row->{$campo} ?? null;

            switch ($columna['tipo']) {

                case 'stock':

                    $unidad = $this->modo === 'item'
                        ? ($row->descripcion_fabrica ?? '')
                        : '';

                    $datos[] = $this->formatearStock(
                        $valor,
                        $unidad
                    );

                    break;

                case 'fecha':

                    $datos[] = !empty($valor)
                        ? date('d/m/Y', strtotime($valor))
                        : '-';

                    break;

                case 'numero':

                    $datos[] = (float) ($valor ?? 0);

                    break;

                case 'texto':
                default:

                    $datos[] = ($valor === null || $valor === '')
                        ? '-'
                        : (string) $valor;

                    break;
            }
        }

        return $datos;
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Conservar códigos como texto
    |--------------------------------------------------------------------------
    */

    public function bindValue(Cell $cell, $value)
    {
        if (
            $this->modo === 'item' &&
            $cell->getColumn() === 'A' &&
            $cell->getRow() >= 9
        ) {
            $cell->setValueExplicit(
                (string) $value,
                DataType::TYPE_STRING
            );

            return true;
        }

        // Evitar interpretar textos como fórmulas
        if (is_string($value)) {

            $cell->setValueExplicit(
                $value,
                DataType::TYPE_STRING
            );

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | 8. Anchos dinámicos
    |--------------------------------------------------------------------------
    */

    public function columnWidths(): array
    {
        $anchos = [];

        foreach ($this->obtenerColumnas() as $index => $columna) {

            $letra = Coordinate::stringFromColumnIndex(
                $index + 1
            );

            $anchos[$letra] = $columna['ancho'];
        }

        return $anchos;
    }

    /*
    |--------------------------------------------------------------------------
    | 9. Fila inicial de la tabla
    |--------------------------------------------------------------------------
    */

    public function startCell(): string
    {
        return 'A8';
    }

    /*
    |--------------------------------------------------------------------------
    | 10. Logo de la empresa
    |--------------------------------------------------------------------------
    */

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
            $drawing->setOffsetX(10);
            $drawing->setOffsetY(5);

            $drawings[] = $drawing;
        }

        return $drawings;
    }

    /*
    |--------------------------------------------------------------------------
    | 11. Estilos del Excel
    |--------------------------------------------------------------------------
    */

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->obtenerUltimaColumna();
        $highestRow = $sheet->getHighestRow();

        /*
        |--------------------------------------------------------------------------
        | Encabezados de la tabla
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A8:' . $lastColumn . '8')
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => [
                        'rgb' => 'FFFFFF'
                    ]
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '34495E'
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => 'CBD5E1'
                        ]
                    ]
                ]
            ]);

        $sheet->getRowDimension(8)->setRowHeight(32);

        /*
        |--------------------------------------------------------------------------
        | Estilos del contenido
        |--------------------------------------------------------------------------
        */

        if ($highestRow >= 9) {

            $sheet->getStyle(
                'A9:' . $lastColumn . $highestRow
            )->applyFromArray([
                'font' => [
                    'size' => 10,
                    'color' => [
                        'rgb' => '263238'
                    ]
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => 'DCE1E7'
                        ]
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            /*
            |--------------------------------------------------------------------------
            | Filas alternadas
            |--------------------------------------------------------------------------
            */

            $rangoDatos = 'A9:' . $lastColumn . $highestRow;

            $filaAlternada = new Conditional();

            $filaAlternada->setConditionType(
                Conditional::CONDITION_EXPRESSION
            );

            $filaAlternada->addCondition(
                'MOD(ROW(),2)=0'
            );

            $filaAlternada->getStyle()
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('F3F5F7');

            $sheet->getStyle($rangoDatos)
                ->setConditionalStyles([$filaAlternada]);

            /*
            |--------------------------------------------------------------------------
            | Estilos según tipo de columna
            |--------------------------------------------------------------------------
            */

            foreach ($this->obtenerColumnas() as $index => $columna) {

                $letra = Coordinate::stringFromColumnIndex(
                    $index + 1
                );

                $rango = $letra . '9:'
                    . $letra . $highestRow;

                if ($columna['tipo'] === 'stock') {

                    // Centrar cantidades
                    $sheet->getStyle($rango)
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    // Formato condicional: Sin Stock
                    $sinStock = new Conditional();

                    $sinStock->setConditionType(
                        Conditional::CONDITION_EXPRESSION
                    );

                    $sinStock->addCondition(
                        $letra . '9="Sin Stock"'
                    );

                    $sinStock->getStyle()
                        ->getFont()
                        ->setBold(true)
                        ->getColor()
                        ->setRGB('DC2626');

                    $sheet->getStyle($rango)
                        ->setConditionalStyles([
                            $sinStock,
                            $filaAlternada
                        ]);

                    // Resaltar stock del almacén seleccionado
                    if ($columna['campo'] === 'saldo_stock_total') {

                        $sheet->getStyle($rango)
                            ->getFont()
                            ->setBold(true);
                    }

                } elseif (
                    $columna['tipo'] === 'numero' ||
                    $columna['tipo'] === 'fecha'
                ) {

                    $sheet->getStyle($rango)
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );
                }
            }

            // Centrar códigos
            if ($this->modo === 'item') {

                $sheet->getStyle('A9:A' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );
            }
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | 12. Eventos y encabezado principal
    |--------------------------------------------------------------------------
    */

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $lastColumn = $this->obtenerUltimaColumna();
                $highestRow = $sheet->getHighestRow();

                /*
                |--------------------------------------------------------------------------
                | Título del reporte
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells(
                    'C2:' . $lastColumn . '2'
                );

                $sheet->setCellValue(
                    'C2',
                    'REPORTE DE INVENTARIO'
                );

                $sheet->getStyle('C2')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(18)
                    ->getColor()
                    ->setRGB('34495E');

                $sheet->getStyle('C2')
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_LEFT
                    );

                $sheet->getRowDimension(2)
                    ->setRowHeight(28);

                /*
                |--------------------------------------------------------------------------
                | Fecha de generación
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells(
                    'C3:' . $lastColumn . '3'
                );

                $sheet->setCellValue(
                    'C3',
                    'Fecha de generación: ' . $this->fechaGeneracion
                );

                $sheet->getStyle('C3')
                    ->getFont()
                    ->setSize(10)
                    ->getColor()
                    ->setRGB('64748B');

                /*
                |--------------------------------------------------------------------------
                | Ordenamiento
                |--------------------------------------------------------------------------
                */

                $ordenes = [
                    'nombre_asc' => 'Nombre de A - Z',
                    'stock_asc' => 'Menor a mayor stock',
                    'stock_desc' => 'Mayor a menor stock'
                ];

                $nombreOrden = $ordenes[$this->orden]
                    ?? $ordenes['stock_asc'];

                if ($this->modo === 'lote') {
                    $nombreOrden = 'Menor a mayor stock';
                }

                /*
                |--------------------------------------------------------------------------
                | Información de filtros
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells(
                    'A5:' . $lastColumn . '5'
                );

                $sheet->mergeCells(
                    'A6:' . $lastColumn . '6'
                );

                $sheet->mergeCells(
                    'A7:' . $lastColumn . '7'
                );

                $sheet->setCellValue(
                    'A5',
                    'Almacén: ' . $this->nombreAlmacen
                );

                $sheet->setCellValue(
                    'A6',
                    'Búsqueda: ' . (
                        !empty($this->buscar)
                            ? $this->buscar
                            : 'Ninguna'
                    )
                );

                $sheet->setCellValue(
                    'A7',
                    'Orden: ' . $nombreOrden
                );

                $sheet->getStyle(
                    'A5:' . $lastColumn . '7'
                )->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => [
                            'rgb' => '34495E'
                        ]
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'ECF0F1'
                        ]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                /*
                |--------------------------------------------------------------------------
                | Configuración de la hoja
                |--------------------------------------------------------------------------
                */

                $sheet->setTitle('Inventario');

                $sheet->setShowGridlines(false);

                $sheet->getDefaultRowDimension()
                    ->setRowHeight(21);

                // Filtros automáticos
                $sheet->setAutoFilter(
                    'A8:' . $lastColumn . max(8, $highestRow)
                );

                // Congelar encabezados y columnas principales
                if ($this->modo === 'item') {
                    $sheet->freezePane('C9');
                } else {
                    $sheet->freezePane('B9');
                }

                /*
                |--------------------------------------------------------------------------
                | Total de registros
                |--------------------------------------------------------------------------
                */

                $totalRegistros = max(0, $highestRow - 8);

                $filaTotal = $highestRow + 2;

                $sheet->mergeCells(
                    'A' . $filaTotal . ':'
                    . $lastColumn . $filaTotal
                );

                $sheet->setCellValue(
                    'A' . $filaTotal,
                    'Total de registros: ' . $totalRegistros
                );

                $sheet->getStyle(
                    'A' . $filaTotal
                )->getFont()
                    ->setBold(true)
                    ->setSize(11)
                    ->getColor()
                    ->setRGB('34495E');
            }
        ];
    }
}