<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KardexDetalladoExport implements FromArray, WithColumnWidths, WithStyles, WithEvents
{
    protected $data;     // Objeto con ventas, ingresos, ajustes
    protected $articulo; // Objeto del artículo
    protected $rango;    // Texto del rango de fechas

    // Variables para guardar en qué fila cae cada título de sección (para pintarlo luego)
    protected $rowVentas;
    protected $rowCompras;
    protected $rowAjustes;

    public function __construct($data, $articulo, $inicio, $fin)
    {
        $this->data = $data;
        $this->articulo = $articulo;
        $this->rango = "Rango: $inicio al $fin";
    }

    public function array(): array
    {
        $rows = [];

        // --- ENCABEZADO DEL DOCUMENTO ---
        $rows[] = ['KARDEX DETALLADO DE PRODUCTO']; // Fila 1
        $rows[] = [$this->rango];                   // Fila 2
        $rows[] = [''];                             // Fila 3 (Espacio)
        
        // Info Producto
        $rows[] = ['Código:', $this->articulo->codigo]; // Fila 4
        $rows[] = ['Producto:', $this->articulo->nombre]; // Fila 5
        $rows[] = ['']; // Fila 6 (Espacio)

        // ==========================================
        // SECCIÓN 1: VENTAS
        // ==========================================
        $this->rowVentas = count($rows) + 1; // Guardamos el número de fila actual
        $rows[] = ['1. VENTAS']; // Título Sección
        
        if (count($this->data->ventas) > 0) {
            // Cabeceras de Tabla Ventas
            $rows[] = ['FECHA', 'DOC', 'CLIENTE', 'MODO', 'CANT.'];
            
            foreach ($this->data->ventas as $v) {
                $rows[] = [
                    $v->fecha_hora,
                    $v->num_comprobante,
                    $v->nombre_cliente,
                    $v->modo_venta,
                    $this->formatCantidad($v->cantidad)
                ];
            }
        } else {
            $rows[] = ['No hay ventas en este periodo.'];
        }
        $rows[] = ['']; // Espacio

        // ==========================================
        // SECCIÓN 2: COMPRAS
        // ==========================================
        $this->rowCompras = count($rows) + 1;
        $rows[] = ['2. COMPRAS / INGRESOS'];

        if (count($this->data->ingresos) > 0) {
            // Cabeceras Tabla Compras (Alineamos Cantidad a la col E)
            $rows[] = ['FECHA', 'DOC', 'REGISTRADO POR', '', 'CANT.'];

            foreach ($this->data->ingresos as $i) {
                $rows[] = [
                    $i->fecha_hora,
                    $i->num_comprobante,
                    $i->responsable_compra,
                    '', // Columna vacía para alinear
                    $this->formatCantidad($i->cantidad)
                ];
            }
        } else {
            $rows[] = ['No hay compras en este periodo.'];
        }
        $rows[] = [''];

        // ==========================================
        // SECCIÓN 3: AJUSTES
        // ==========================================
        $this->rowAjustes = count($rows) + 1;
        $rows[] = ['3. AJUSTES'];

        if (count($this->data->ajustes) > 0) {
            // Cabeceras Tabla Ajustes
            $rows[] = ['FECHA', 'MOTIVO', '', '', 'CANT.'];

            foreach ($this->data->ajustes as $a) {
                $rows[] = [
                    $a->fecha_hora,
                    $a->motivo, // El motivo puede ser largo, ocupará visualmente col B, C y D
                    '',
                    '',
                    $this->formatCantidad($a->cantidad) // Corregido: antes tenías $v->cantidad
                ];
            }
        } else {
            $rows[] = ['No hay ajustes en este periodo.'];
        }

        return $rows;
    }

    // Helper para formato "1 unidad" o "N unidades"
    private function formatCantidad($cant)
    {
        return $cant . ' ' . ($cant == 1 ? 'unidad' : 'unidades');
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Fecha
            'B' => 20, // Doc / Motivo
            'C' => 35, // Cliente / Responsable
            'D' => 20, // Modo
            'E' => 20, // Cantidad
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]], // Título Principal
            2 => ['font' => ['italic' => true, 'size' => 10]], // Rango fechas
            4 => ['font' => ['bold' => true]], // Label Codigo
            5 => ['font' => ['bold' => true]], // Label Producto
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
                // 1. Centrar Título Principal
                $sheet->mergeCells('A1:E1'); 
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->mergeCells('A2:E2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 2. Estilo Sección VENTAS (Fondo Azulito)
                $this->estilarSeccion($sheet, $this->rowVentas, 'C8DCFF');

                // 3. Estilo Sección COMPRAS (Fondo Verdecito)
                $this->estilarSeccion($sheet, $this->rowCompras, 'DCFFDC');

                // 4. Estilo Sección AJUSTES (Fondo Naranja bajito)
                $this->estilarSeccion($sheet, $this->rowAjustes, 'FFF0C8');

                // 5. Bordes generales a todo
                $lastRow = $sheet->getHighestRow();
                // Opcional: poner bordes a todo o dejarlo limpio
            },
        ];
    }

    // Función auxiliar para pintar la cabecera de la sección y la fila de títulos de columnas
    private function estilarSeccion($sheet, $rowNumber, $colorHex)
    {
        // Fila del Título de Sección (Ej: "1. VENTAS")
        $sheet->mergeCells("A{$rowNumber}:E{$rowNumber}");
        $sheet->getStyle("A{$rowNumber}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colorHex]
            ]
        ]);

        // Fila de Encabezados de Tabla (La siguiente fila)
        $headerRow = $rowNumber + 1;
        // Verificamos si existe esa fila (si hay datos o mensaje "no hay datos")
        if ($sheet->getCell("A{$headerRow}")->getValue() != 'No hay ventas en este periodo.' && 
            $sheet->getCell("A{$headerRow}")->getValue() != 'No hay compras en este periodo.' &&
            $sheet->getCell("A{$headerRow}")->getValue() != 'No hay ajustes en este periodo.') 
        {
             $sheet->getStyle("A{$headerRow}:E{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            // Alinear a la derecha la columna de Cantidad (E)
            $sheet->getStyle("E{$headerRow}:E1000")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        } else {
            // Si es mensaje de "No hay...", lo centramos e italica
            $sheet->mergeCells("A{$headerRow}:E{$headerRow}");
            $sheet->getStyle("A{$headerRow}")->applyFromArray([
                'font' => ['italic' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
        }
    }
}