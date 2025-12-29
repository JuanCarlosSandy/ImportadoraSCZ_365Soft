<?php

namespace App\Exports;

use App\Marca;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\FromCollection;

class MarcaExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
//class MarcaExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
        //return Marca::select('id','nombre','condicion')->get();
        return Marca::select('nombre','condicion')->get();

    }
    //----------El titulo de cada embabezado como se mostrara en el Excel---
    public function headings(): array
    {
        return [
            //'ID',
            'nombre',
            'condicion',
        ];
    }
    //----------------ancho de las columnas en el archivo Excel.------
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 15,
            // 'A' => 15,
            // 'B' => 25,
            // 'C' => 20,
        ];
    }
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
