<?php

namespace App\Exports;

use App\Categoria;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\FromCollection;

class LineaExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
        return Categoria::select('nombre','descripcion')->get();
    }
    //----------El titulo de cada embabezado como se mostrara en el Excel---
    public function headings(): array
    {
        return [
        //'ID',
        'nombre',
        'descripcion',
        ];
    }
     //----------------ancho de las columnas en el archivo Excel.------
     public function columnWidths(): array
     {
        return [
            'A' => 30,
            'B' => 30,
        ];
    }
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
