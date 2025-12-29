<?php

namespace App\Exports;

use App\Inventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosPorVencerseExport implements FromQuery, WithHeadings, WithColumnWidths, WithStyles
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $user = Auth::user();

        $query = Inventario::join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'articulos.codigo',
                'articulos.nombre',
                'almacens.nombre_almacen',
                'articulos.unidad_envase',
                'inventarios.saldo_stock',
                DB::raw('DATEDIFF(inventarios.fecha_vencimiento, CURDATE()) AS dias_restantes'),
                'inventarios.fecha_vencimiento',
                'personas.nombre as nombre_proveedor'
            )
            ->whereRaw('DATEDIFF(inventarios.fecha_vencimiento, CURDATE()) < 30')
            ->whereRaw('DATEDIFF(inventarios.fecha_vencimiento, CURDATE()) > 0')
            ->orderBy('inventarios.id', 'asc');

        // ✅ Si el usuario no es rol 4, filtramos por su sucursal
        if ($user->idrol != 4) {
            $query->where('almacens.sucursal', $user->idsucursal);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Código',
            'Producto',
            'Almacén',
            'Unidad X Paq.',
            'Saldo Stock',
            'Días Vencimiento',
            'Fecha Vencimiento',
            'Proveedor',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 25,
            'D' => 15,
            'E' => 15,
            'F' => 18,
            'G' => 20,
            'H' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
