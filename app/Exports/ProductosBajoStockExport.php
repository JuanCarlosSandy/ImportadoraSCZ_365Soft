<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosBajoStockExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    protected $datos;

    // Recibimos el array (la parte "data" de tu JSON)
    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    /**
     * Aquí convertimos tu array JSON en las filas del Excel
     */
    public function array(): array
    {
        // Usamos array_map para devolver SOLO los campos que queremos en el orden de los encabezados
        return array_map(function ($item) {
            // $item representa cada objeto dentro de "data"
            return [
                $item['codigo'],              // Columna A: Código
                $item['nombre_producto'],     // Columna B: Producto
                $item['nombre_almacen'],      // Columna C: Almacen
                $item['stock'],               // Columna D: Saldo Stock (Nota: en tu JSON es "stock", no "saldo_stock")
                $item['nombre_proveedor'],    // Columna E: Proveedor
            ];
        }, $this->datos);
    }

    public function headings(): array
    {
        return [
            'Código',
            'Producto',
            'Almacen',
            'Saldo Stock',
            'Proveedor',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 20,
            'D' => 15,
            'E' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}