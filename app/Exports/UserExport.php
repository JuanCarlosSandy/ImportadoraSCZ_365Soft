<?php

namespace App\Exports;

use App\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UserExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithCustomStartCell, WithEvents, WithDrawings
{
    protected $fechaGeneracion;

    public function __construct()
    {
        $this->fechaGeneracion = date('d/m/Y H:i:s');
    }

    /**
    * @return \Illuminate\Database\Eloquent\Builder
    */
    public function query()
    {
        return User::join('personas','users.id','=','personas.id')
                ->join('roles','users.idrol','=','roles.id')
                ->join('sucursales','users.idsucursal','=','sucursales.id')
                ->select('personas.id','personas.nombre','personas.tipo_documento','personas.num_documento','personas.direccion','personas.telefono','personas.email','users.usuario','roles.nombre as rol', 'sucursales.nombre as sucursal')
                ->orderBy('personas.id', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Tipo Documento',
            'Nro Documento',
            'Direccion',
            'Telefono',
            'Email',
            'Usuario',
            'Rol',
            'Sucursal',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->nombre,
            $row->tipo_documento,
            $row->num_documento,
            $row->direccion,
            $row->telefono,
            $row->email,
            $row->usuario,
            $row->rol,
            $row->sucursal,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 30,
            'C' => 20,
            'D' => 20,
            'E' => 30,
            'F' => 15,
            'G' => 30,
            'H' => 20,
            'I' => 20,
            'J' => 25,
        ];  
    }

    public function startCell(): string
    {
        return 'A8';
    }

    public function drawings()
    {
        $drawings = [];
        $rutaLogo = public_path('img/logoPrincipal.png');
        
        if (file_exists($rutaLogo)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo Empresa');
            $drawing->setPath($rutaLogo);
            $drawing->setHeight(70);
            $drawing->setCoordinates('A1');
            $drawings[] = $drawing;
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Título
                $sheet->mergeCells('C2:J2');
                $sheet->setCellValue('C2', 'REPORTE DE USUARIOS');
                $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Fecha
                $sheet->mergeCells('C3:J3');
                $sheet->setCellValue('C3', 'Fecha de generación: ' . $this->fechaGeneracion);
                $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Filtros
                $sheet->setCellValue('A5', 'Reporte: General');
                $sheet->getStyle('A5')->getFont()->setBold(true);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el encabezado de la tabla
        $sheet->getStyle('A8:J8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Estilo para todo el contenido (bordes y alineación vertical)
        $sheet->getStyle('A9:J' . $sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        return [];
    }
}
