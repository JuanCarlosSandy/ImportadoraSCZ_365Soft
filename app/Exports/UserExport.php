<?php

namespace App\Exports;

use App\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithCustomStartCell, WithEvents, WithDrawings
{
    protected $fechaGeneracion;

    public function __construct()
    {
        $this->fechaGeneracion = date('d/m/Y H:i:s');
    }

    /**
     * Consulta a la base de datos eliminando ID, Dirección y Email.
     * Se asegura de traer el Punto de Venta.
     */
    public function query()
    {
        return User::join('personas', 'users.id', '=', 'personas.id')
            ->join('roles', 'users.idrol', '=', 'roles.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->leftJoin('punto_ventas', 'users.idpuntoventa', '=', 'punto_ventas.id')
            ->select(
                'personas.nombre',
                'personas.tipo_documento',
                'personas.num_documento',
                'personas.telefono',
                'users.usuario',
                'roles.nombre as rol',
                'sucursales.nombre as sucursal',
                'punto_ventas.nombre as puntoventa'
            )
            ->orderBy('personas.id', 'desc');
    }

    /**
     * Definición de encabezados en el orden solicitado.
     */
    public function headings(): array
    {
        return [
            'Nombre',
            'Documento', // Unificado
            'Teléfono',
            'Usuario',
            'Rol',
            'Sucursal',
            'Punto de Venta', // Ubicada después de Sucursal
        ];
    }

    /**
     * Mapeo de datos para cada fila.
     */
    public function map($row): array
    {
        return [
            $row->nombre,
            $this->formatearDocumento($row->tipo_documento, $row->num_documento),
            $row->telefono,
            $row->usuario,
            $row->rol,
            $row->sucursal,
            $row->puntoventa ?: 'Sin punto de venta', // Manejo de valor por defecto
        ];
    }

    /**
     * Anchos de columna para mejorar la legibilidad.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 35, // Nombre
            'B' => 22, // Documento
            'C' => 18, // Teléfono
            'D' => 20, // Usuario
            'E' => 20, // Rol
            'F' => 25, // Sucursal
            'G' => 28, // Punto de Venta
        ];
    }

    /**
     * Celda de inicio para dejar espacio al encabezado/logo.
     */
    public function startCell(): string
    {
        return 'A8';
    }

    /**
     * Inserción del logo de la empresa.
     */
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

    /**
     * Configuración de eventos para el diseño del encabezado superior.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Título del Reporte
                $sheet->mergeCells('C2:G2');
                $sheet->setCellValue('C2', 'REPORTE DE USUARIOS');
                $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Fecha de Generación
                $sheet->mergeCells('C3:G3');
                $sheet->setCellValue('C3', 'Fecha de generación: ' . $this->fechaGeneracion);
                $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Información adicional
                $sheet->setCellValue('A5', 'Reporte: General de Usuarios');
                $sheet->getStyle('A5')->getFont()->setBold(true);
            },
        ];
    }

    /**
     * Estilos de la tabla (Encabezados y Bordes).
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo para los encabezados de la tabla (Fila 8)
        $sheet->getStyle('A8:G8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
        ]);

        // Estilo para el contenido (bordes y alineación)
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 9) {
            $sheet->getStyle('A9:G' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ],
            ]);
        }

        return [];
    }

    /**
     * Lógica de unificación de Tipo y Número de Documento.
     * Mapeo: 1->CI, 2->CEX, 3->PAS, 4->OD, 5->NIT.
     */
    private function formatearDocumento($tipoDocumento, $numDocumento)
    {
        $tipos = [
            1 => 'CI',
            2 => 'CEX',
            3 => 'PAS',
            4 => 'OD',
            5 => 'NIT',
        ];

        $abreviatura = isset($tipos[$tipoDocumento]) ? $tipos[$tipoDocumento] : '';
        $numero = trim((string) $numDocumento);

        if ($abreviatura !== '' && $numero !== '') {
            return $abreviatura . ' ' . $numero;
        }

        return $abreviatura ?: $numero;
    }
}