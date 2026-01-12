<?php

namespace App\Exports;

use App\Articulo;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemsCompuestosExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    public function collection()
{
    return Articulo::leftJoin('itemcompuesto as ic', 'ic.idarticulo', '=', 'articulos.id')
        ->leftJoin('articulos as ai', 'ai.id', '=', 'ic.iditem')
        ->where('articulos.tipo_producto', 'C')
        ->where('articulos.condicion', 1)
        ->selectRaw('
            articulos.nombre,
            articulos.precio_uno,
            articulos.precio_dos,
            articulos.precio_tres,
            articulos.descripcion,
            articulos.maximo_descuento,
            GROUP_CONCAT(CONCAT(ai.nombre, " (", ic.cantidad, ")") SEPARATOR " - ") as productos
        ')
        ->groupBy(
            'articulos.id',
            'articulos.nombre',
            'articulos.precio_uno',
            'articulos.precio_dos',
            'articulos.precio_tres',
            'articulos.descripcion',
            'articulos.maximo_descuento'
        )
        ->orderBy('articulos.id', 'asc')
        ->get();
}


    public function headings(): array
{
    return [
        'Nombre',
        'Precio 1',
        'Precio 2',
        'Precio 3',
        'Descripción',
        'Máximo Descuento',
        'Productos Relacionados (Nombre y Cantidad)',
    ];
}


    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 12,
            'C' => 12,
            'D' => 12,
            'E' => 40,
            'F' => 18,
            'G' => 60,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
