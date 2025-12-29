<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventarioExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    protected $modo;
    protected $idAlmacen;

    public function __construct($modo, $idAlmacen)
    {
        $this->modo = $modo;
        $this->idAlmacen = $idAlmacen;
    }

    public function query()
    {
        if ($this->modo === 'item') {
            return DB::table('articulos')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->select(
                    'articulos.nombre as item',
                    'proveedores.contacto as laboratorio',
                    'articulos.unidad_envase',
                    DB::raw('SUM(inventarios.saldo_stock) as saldo_stock_total')
                )
                ->where('inventarios.idalmacen', $this->idAlmacen)
                ->where('articulos.condicion', 1)
                ->groupBy('articulos.id', 'articulos.nombre', 'proveedores.contacto', 'articulos.unidad_envase')
                ->orderBy('articulos.nombre');
        } else {
            return DB::table('articulos')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->select(
                    'articulos.nombre as item',
                    'proveedores.contacto as laboratorio',
                    'articulos.unidad_envase',
                    'inventarios.saldo_stock',
                    'inventarios.created_at',
                    'inventarios.fecha_vencimiento'
                )
                ->where('inventarios.idalmacen', $this->idAlmacen)
                ->where('articulos.condicion', 1)
                ->orderBy('articulos.nombre');
        }
    }

    public function headings(): array
    {
        return $this->modo === 'item'
            ? ['Item', 'Laboratorio', 'Unidad Envase', 'Stock Total']
            : ['Item', 'Laboratorio', 'Unidad Envase', 'Stock', 'Fecha Ingreso', 'Fecha Vencimiento'];
    }

    public function map($row): array
    {
        if ($this->modo === 'item') {
            return [
                substr($row->item, 0, 70),
                $row->laboratorio,
                $row->unidad_envase,
                $row->saldo_stock_total,
            ];
        } else {
            return [
                substr($row->item, 0, 70),
                $row->laboratorio,
                $row->unidad_envase,
                $row->saldo_stock,
                date('d/m/Y', strtotime($row->created_at)),
                date('d/m/Y', strtotime($row->fecha_vencimiento)),
            ];
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50, // Item
            'B' => 25, // Laboratorio
            'C' => 15, // Unidad Envase
            'D' => 12, // Stock
            'E' => 18, // Fecha Ingreso
            'F' => 18, // Fecha Vencimiento
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headings = $this->modo === 'item' ? 4 : 6;

        // Encabezado en negrita + fondo celeste
        $sheet->getStyle('A1:' . chr(64 + $headings) . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'DDEBF7'],
            ],
        ]);

        // Resaltar filas con stock 0 (columna D en item, D en lote también)
        $column = $this->modo === 'item' ? 'D' : 'D';
        $lastRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $lastRow; $row++) {
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
}
