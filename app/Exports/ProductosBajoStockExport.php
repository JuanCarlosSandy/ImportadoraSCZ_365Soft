<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosBajoStockExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function array(): array
    {
        $filas = is_array($this->datos) ? $this->datos : $this->datos->toArray();

        return array_map(function ($item) {
            $row = (array) $item;

            return [
                $this->toAscii($row['nombre_almacen'] ?? ''),
                $this->toAscii($row['nombre_producto'] ?? ''),
                $this->toAscii($row['nombre_categoria'] ?? ''),
                $this->toAscii($row['nombre_proveedor'] ?? ''),
                isset($row['stock_actual']) ? (int) round($row['stock_actual']) : 0,
                isset($row['stock_minimo']) ? (int) round($row['stock_minimo']) : 0,
            ];
        }, $filas);
    }

    private function toAscii($text): string
    {
        $text = (string) $text;
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : $text;
    }

    public function headings(): array
    {
        return [
            'Almacen',
            'Producto',
            'Categoria',
            'Proveedor',
            'Stock actual',
            'Stock minimo',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 24,
            'B' => 34,
            'C' => 24,
            'D' => 30,
            'E' => 14,
            'F' => 14,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('E:F')->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
