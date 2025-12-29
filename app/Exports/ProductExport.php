<?php

namespace App\Exports;

use App\Articulo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductExport implements FromQuery, WithHeadings, WithColumnWidths, WithStyles
{
    public function query()
    {
        return Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->leftJoin('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->select(
                'articulos.codigo',
                'articulos.nombre',
                'articulos.descripcion',
                'categorias.nombre as nombre_categoria',
                'proveedores.contacto as nombre_proveedor',
                'articulos.unidad_envase',
                'articulos.precio_costo_unid',
                'articulos.precio_costo_paq',
                'articulos.precio_uno',
                'articulos.precio_dos',
                'articulos.stock',
            )
            ->orderBy('articulos.nombre', 'desc');
    }

    public function headings(): array
    {
        return [
            'Codigo',
            'Nombre',
            'Descripcion',
            'Categoria',
            'Proveedor',
            'Unidades por Paquete',
            'Precio Costo Unidad',
            'Precio Costo Paquete',
            'Precio Venta 1',
            'Precio Venta 2',
            'Stock Minimo',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 30,
            'D' => 20,
            'E' => 20,
            'F' => 15,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 18,
            'K' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}