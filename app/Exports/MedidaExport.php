<?php

namespace App\Exports;

use App\Medida;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;

class MedidaExport implements FromCollection,  WithHeadings, WithStyles, WithColumnWidths
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
        return Medida::select('descripcion_medida','descripcion_corta','estado')->get();
    }
    //----------El titulo de cada embabezado como se mostrara en el Excel---
    public function headings(): array
    {
        return [
        'descripcion_medida',
        'descripcion_corta',
        'estado',
        ];
    }
    //----------------ancho de las columnas en el archivo Excel.------
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30,
            'C' => 15,
        ];
    }
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
