<?php

namespace App\Exports;

use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

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

class InventarioHistoricoExport extends DefaultValueBinder implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithColumnWidths,
    WithStyles,
    WithCustomStartCell,
    WithEvents,
    WithDrawings,
    WithCustomValueBinder,
    WithStrictNullComparison
{
    protected $consulta;
    protected $idAlmacen;
    protected $nombreAlmacen;
    protected $almacenesColumnas;
    protected $buscar;
    protected $orden;

    protected $tipoConsulta;
    protected $fechaConsulta;
    protected $fechaCorte;
    protected $fechaGeneracion;
    protected $mensajeHistorial;
    protected $departamento;

    protected $columnas = [];

    const FILA_ENCABEZADO = 13;
    const FILA_DATOS = 14;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        $consulta,
        $idAlmacen,
        $nombreAlmacen,
        $almacenesColumnas,
        $informacionHistorial,
        $buscar = '',
        $orden = 'stock_asc'
    ) {
        $this->consulta = $consulta;
        $this->idAlmacen = (int) $idAlmacen;
        $this->nombreAlmacen = $nombreAlmacen;
        $this->almacenesColumnas = $almacenesColumnas;
        $this->buscar = $buscar;
        $this->orden = $orden;

        $this->tipoConsulta =
            $informacionHistorial['tipo_consulta'];

        $this->mensajeHistorial =
            $informacionHistorial['mensaje_historial'];

        $this->departamento =
            $informacionHistorial['departamento'] ?? '-';

        $this->fechaConsulta = Carbon::parse(
            $informacionHistorial['fecha_consulta'],
            'America/La_Paz'
        )->format('d/m/Y');

        $this->fechaCorte = Carbon::parse(
            $informacionHistorial['fecha_corte'],
            'America/La_Paz'
        )->format('d/m/Y H:i:s');

        $this->fechaGeneracion = Carbon::now(
            'America/La_Paz'
        )->format('d/m/Y H:i:s');

        // Construir columnas una sola vez
        $this->columnas = $this->construirColumnas();
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
    | 2. Configuración del tipo de consulta
    |--------------------------------------------------------------------------
    */

    private function obtenerConfiguracionConsulta()
    {
        $configuraciones = [

            'ACTUAL' => [
                'titulo' => 'INVENTARIO ACTUAL',
                'titulo_stock' => 'Stock Actual',
                'fondo' => 'EFF6FF',
                'texto' => '1E40AF'
            ],

            'HISTORICO_REGISTRADO' => [
                'titulo' => 'HISTÓRICO REGISTRADO',
                'titulo_stock' => 'Stock Histórico',
                'fondo' => 'ECFDF5',
                'texto' => '166534'
            ],

            'HISTORICO_ESTIMADO' => [
                'titulo' => 'HISTÓRICO ESTIMADO',
                'titulo_stock' => 'Stock Estimado',
                'fondo' => 'FFF7ED',
                'texto' => '9A3412'
            ]
        ];

        return $configuraciones[$this->tipoConsulta]
            ?? $configuraciones['ACTUAL'];
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Definir columnas dinámicas
    |--------------------------------------------------------------------------
    */

    private function construirColumnas()
    {
        $config = $this->obtenerConfiguracionConsulta();

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
                'titulo' => $config['titulo_stock'],
                'ancho' => 24,
                'tipo' => 'stock'
            ]
        ];

        // Mostrar depósito si no es el seleccionado
        if ($this->idAlmacen !== 2) {

            $columnas[] = [
                'campo' => 'stock_deposito',
                'titulo' => 'Stock Depósito',
                'ancho' => 24,
                'tipo' => 'stock'
            ];
        }

        // Almacenes adicionales del departamento
        foreach ($this->almacenesColumnas as $almacen) {

            $columnas[] = [
                'campo' =>
                    'stock_almacen_' . $almacen->id,

                'titulo' =>
                    $almacen->nombre_almacen,

                'ancho' => max(
                    24,
                    min(
                        40,
                        mb_strlen($almacen->nombre_almacen) + 5
                    )
                ),

                'tipo' => 'stock'
            ];
        }

        // Unidad de medida independiente
        $columnas[] = [
            'campo' => 'descripcion_fabrica',
            'titulo' => 'Unidad de Medida',
            'ancho' => 20,
            'tipo' => 'unidad'
        ];

        return $columnas;
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Obtener última columna
    |--------------------------------------------------------------------------
    */

    private function obtenerUltimaColumna()
    {
        return Coordinate::stringFromColumnIndex(
            count($this->columnas)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Encabezados de la tabla
    |--------------------------------------------------------------------------
    */

    public function headings(): array
    {
        return array_column(
            $this->columnas,
            'titulo'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Mapear registros
    |--------------------------------------------------------------------------
    */

    public function map($row): array
    {
        $datos = [];

        foreach ($this->columnas as $columna) {

            $campo = $columna['campo'];

            $valor = $row->{$campo} ?? null;

            if ($columna['tipo'] === 'stock') {

                // Mantener cantidades numéricas
                $datos[] = is_numeric($valor)
                    ? (float) $valor
                    : 'Sin datos';

            } else {

                $datos[] = (
                    $valor === null ||
                    $valor === ''
                ) ? '-' : (string) $valor;
            }
        }

        return $datos;
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Conservar códigos y proteger textos
    |--------------------------------------------------------------------------
    */

    public function bindValue(Cell $cell, $value)
    {
        // Conservar códigos con ceros iniciales
        if (
            $cell->getColumn() === 'A' &&
            $cell->getRow() >= self::FILA_DATOS
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

        foreach ($this->columnas as $index => $columna) {

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
        return 'A' . self::FILA_ENCABEZADO;
    }

    /*
    |--------------------------------------------------------------------------
    | 10. Logo de la empresa
    |--------------------------------------------------------------------------
    */

    public function drawings()
    {
        $drawings = [];

        $rutaLogo = public_path(
            'img/logoPrincipal.png'
        );

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
    | 11. Estilos de la tabla
    |--------------------------------------------------------------------------
    */

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->obtenerUltimaColumna();
        $highestRow = $sheet->getHighestRow();

        $filaEncabezado = self::FILA_ENCABEZADO;
        $filaDatos = self::FILA_DATOS;

        /*
        |--------------------------------------------------------------------------
        | Encabezados
        |--------------------------------------------------------------------------
        */

        $rangoEncabezado =
            'A' . $filaEncabezado . ':'
            . $lastColumn . $filaEncabezado;

        $sheet->getStyle($rangoEncabezado)
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
                    'horizontal' =>
                        Alignment::HORIZONTAL_CENTER,

                    'vertical' =>
                        Alignment::VERTICAL_CENTER,

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

        $sheet->getRowDimension($filaEncabezado)
            ->setRowHeight(32);

        /*
        |--------------------------------------------------------------------------
        | Estilos del contenido
        |--------------------------------------------------------------------------
        */

        if ($highestRow >= $filaDatos) {

            $rangoDatos =
                'A' . $filaDatos . ':'
                . $lastColumn . $highestRow;

            $sheet->getStyle($rangoDatos)
                ->applyFromArray([

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
                        'vertical' =>
                            Alignment::VERTICAL_CENTER
                    ]
                ]);

            /*
            |--------------------------------------------------------------------------
            | Filas alternadas
            |--------------------------------------------------------------------------
            */

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
                ->setConditionalStyles([
                    $filaAlternada
                ]);

            /*
            |--------------------------------------------------------------------------
            | Estilos por columna
            |--------------------------------------------------------------------------
            */

            foreach ($this->columnas as $index => $columna) {

                $letra = Coordinate::stringFromColumnIndex(
                    $index + 1
                );

                $rango =
                    $letra . $filaDatos . ':'
                    . $letra . $highestRow;

                if ($columna['tipo'] === 'stock') {

                    // Centrar cantidades
                    $sheet->getStyle($rango)
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Formato numérico
                    |--------------------------------------------------------------------------
                    |
                    | Positivo: cantidad
                    | Negativo: cantidad en rojo
                    | Cero: Sin Stock
                    | Texto: Sin datos
                    |
                    */

                    $sheet->getStyle($rango)
                        ->getNumberFormat()
                        ->setFormatCode(
                            '#,##0.####;[Red]-#,##0.####;"Sin Stock";@'
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Formato condicional: cero o negativo
                    |--------------------------------------------------------------------------
                    */

                    $stockBajo = new Conditional();

                    $stockBajo->setConditionType(
                        Conditional::CONDITION_EXPRESSION
                    );

                    $referencia = $letra . $filaDatos;

                    $stockBajo->addCondition(
                        'AND(ISNUMBER('
                        . $referencia
                        . '),'
                        . $referencia
                        . '<=0)'
                    );

                    $stockBajo->getStyle()
                        ->getFont()
                        ->setBold(true)
                        ->getColor()
                        ->setRGB('DC2626');

                    $sheet->getStyle($rango)
                        ->setConditionalStyles([
                            $stockBajo,
                            $filaAlternada
                        ]);

                    // Resaltar almacén seleccionado
                    if (
                        $columna['campo'] ===
                        'saldo_stock_total'
                    ) {

                        $sheet->getStyle($rango)
                            ->getFont()
                            ->setBold(true);
                    }

                } elseif (
                    $columna['tipo'] === 'unidad'
                ) {

                    $sheet->getStyle($rango)
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );
                }
            }

            // Centrar códigos
            $sheet->getStyle(
                'A' . $filaDatos . ':A' . $highestRow
            )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | 12. Encabezado principal y eventos
    |--------------------------------------------------------------------------
    */

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $lastColumn = $this->obtenerUltimaColumna();
                $highestRow = $sheet->getHighestRow();

                $config =
                    $this->obtenerConfiguracionConsulta();

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
                    'REPORTE DE INVENTARIO HISTÓRICO'
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
                | Fecha consultada
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells(
                    'C3:' . $lastColumn . '3'
                );

                $sheet->setCellValue(
                    'C3',
                    'Inventario al: '
                    . $this->fechaConsulta
                );

                $sheet->getStyle('C3')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(12)
                    ->getColor()
                    ->setRGB('34495E');

                /*
                |--------------------------------------------------------------------------
                | Fecha de generación
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells(
                    'C4:' . $lastColumn . '4'
                );

                $sheet->setCellValue(
                    'C4',
                    'Fecha de generación: '
                    . $this->fechaGeneracion
                );

                $sheet->getStyle('C4')
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

                /*
                |--------------------------------------------------------------------------
                | Información de filtros
                |--------------------------------------------------------------------------
                */

                $informacion = [

                    5 => 'Almacén: '
                        . $this->nombreAlmacen,

                    6 => 'Departamento: '
                        . $this->departamento,

                    7 => 'Búsqueda: '
                        . (
                            !empty($this->buscar)
                                ? $this->buscar
                                : 'Ninguna'
                        ),

                    8 => 'Orden: '
                        . $nombreOrden
                ];

                foreach ($informacion as $fila => $texto) {

                    $sheet->mergeCells(
                        'A' . $fila . ':'
                        . $lastColumn . $fila
                    );

                    $sheet->setCellValueExplicit(
                        'A' . $fila,
                        $texto,
                        DataType::TYPE_STRING
                    );

                    $sheet->getRowDimension($fila)
                        ->setRowHeight(21);
                }

                $sheet->getStyle(
                    'A5:' . $lastColumn . '8'
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
                        'vertical' =>
                            Alignment::VERTICAL_CENTER
                    ]
                ]);

                /*
                |--------------------------------------------------------------------------
                | Indicador de consulta histórica
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells(
                    'A9:' . $lastColumn . '9'
                );

                $sheet->setCellValue(
                    'A9',
                    $config['titulo']
                );

                $sheet->getStyle('A9')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(12);

                $sheet->getRowDimension(9)
                    ->setRowHeight(25);

                /*
                |--------------------------------------------------------------------------
                | Mensaje informativo
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells(
                    'A10:' . $lastColumn . '10'
                );

                $sheet->setCellValue(
                    'A10',
                    $this->mensajeHistorial
                );

                $sheet->getStyle('A10')
                    ->getAlignment()
                    ->setWrapText(true);

                $sheet->getRowDimension(10)
                    ->setRowHeight(45);

                /*
                |--------------------------------------------------------------------------
                | Fecha de corte
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells(
                    'A11:' . $lastColumn . '11'
                );

                $sheet->setCellValue(
                    'A11',
                    'Inicio del historial registrado: '
                    . $this->fechaCorte
                );

                $sheet->getRowDimension(11)
                    ->setRowHeight(22);

                /*
                |--------------------------------------------------------------------------
                | Colores según tipo de consulta
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle(
                    'A9:' . $lastColumn . '11'
                )->applyFromArray([

                    'font' => [
                        'color' => [
                            'rgb' => $config['texto']
                        ]
                    ],

                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => $config['fondo']
                        ]
                    ],

                    'alignment' => [
                        'vertical' =>
                            Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Espacio antes de la tabla
                $sheet->getRowDimension(12)
                    ->setRowHeight(10);

                /*
                |--------------------------------------------------------------------------
                | Configuración de la hoja
                |--------------------------------------------------------------------------
                */

                $sheet->setTitle(
                    'Inventario Histórico'
                );

                $sheet->setShowGridlines(false);

                $sheet->getDefaultRowDimension()
                    ->setRowHeight(21);

                /*
                |--------------------------------------------------------------------------
                | Filtros automáticos
                |--------------------------------------------------------------------------
                */

                $sheet->setAutoFilter(
                    'A' . self::FILA_ENCABEZADO
                    . ':' . $lastColumn
                    . max(
                        self::FILA_ENCABEZADO,
                        $highestRow
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Congelar encabezados y columnas principales
                |--------------------------------------------------------------------------
                */

                $sheet->freezePane(
                    'C' . self::FILA_DATOS
                );

                /*
                |--------------------------------------------------------------------------
                | Total de registros
                |--------------------------------------------------------------------------
                */

                $totalRegistros = max(
                    0,
                    $highestRow - self::FILA_ENCABEZADO
                );

                $filaTotal = $highestRow + 2;

                $sheet->mergeCells(
                    'A' . $filaTotal . ':'
                    . $lastColumn . $filaTotal
                );

                $sheet->setCellValue(
                    'A' . $filaTotal,
                    'Total de productos: '
                    . $totalRegistros
                );

                $sheet->getStyle('A' . $filaTotal)
                    ->getFont()
                    ->setBold(true)
                    ->setSize(11)
                    ->getColor()
                    ->setRGB('34495E');

                /*
                |--------------------------------------------------------------------------
                | Nota para inventario estimado
                |--------------------------------------------------------------------------
                */

                if (
                    $this->tipoConsulta ===
                    'HISTORICO_ESTIMADO'
                ) {

                    $filaNota = $filaTotal + 2;

                    $sheet->mergeCells(
                        'A' . $filaNota . ':'
                        . $lastColumn . $filaNota
                    );

                    $sheet->setCellValue(
                        'A' . $filaNota,
                        'Nota: Las existencias anteriores al inicio '
                        . 'del historial registrado son estimadas. '
                        . 'Pueden presentar diferencias debido a '
                        . 'modificaciones o inconsistencias en '
                        . 'los registros antiguos. Los valores '
                        . 'negativos deben ser revisados.'
                    );

                    $sheet->getStyle(
                        'A' . $filaNota
                    )->getFont()
                        ->setItalic(true)
                        ->setSize(10)
                        ->getColor()
                        ->setRGB('9A3412');

                    $sheet->getStyle(
                        'A' . $filaNota
                    )->getAlignment()
                        ->setWrapText(true);

                    $sheet->getRowDimension($filaNota)
                        ->setRowHeight(45);
                }
            }
        ];
    }
}