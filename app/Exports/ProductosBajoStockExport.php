<?php

namespace App\Exports;

use App\Inventario;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosBajoStockExport implements FromQuery, WithHeadings, WithColumnWidths, WithStyles
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $usuario = Auth::user(); // Usuario logueado

        $query = Inventario::join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'articulos.codigo',
                'articulos.nombre as nombre_producto',
                'almacens.nombre_almacen',
                'inventarios.saldo_stock',
                'personas.nombre as nombre_proveedor'
            )
            ->whereRaw('articulos.stock > inventarios.saldo_stock')
            ->orderBy('inventarios.id', 'desc');

        // ✅ Filtrar por la sucursal del usuario, excepto si tiene idrol = 4
        if ($usuario->idrol != 4) {
            $query->where('almacens.sucursal', $usuario->idsucursal);
        }

        return $query;
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
            'F' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
