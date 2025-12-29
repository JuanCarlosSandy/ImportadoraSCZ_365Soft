<?php

namespace App\Exports;

use App\Grupo;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;

class GrupoExport implements FromCollection,  WithHeadings, WithStyles, WithColumnWidths
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
        return Grupo::select('nombre_grupo')->get();
    }
     //----------El titulo de cada embabezado como se mostrara en el Excel---
     public function headings(): array
     {
        return [
        //'ID',
        'nombre_grupo',
        ];
    }
    //----------------ancho de las columnas en el archivo Excel.------
    public function columnWidths(): array
    {
        return [
            'A' => 30,
        ];
    }
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
