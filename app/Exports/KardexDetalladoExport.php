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
    protected $data;     
    protected $articulo; 
    protected $rango;    

    protected $rowVentas;
    protected $rowCompras;
    protected $rowTraspasos; // NUEVO PROP
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
        // Obtenemos traspasos con seguridad (por si viene null)
        $traspasos = isset($this->data->traspasos) ? $this->data->traspasos : [];

        // --- ENCABEZADO PRINCIPAL ---
        $rows[] = ['KARDEX DETALLADO DE PRODUCTO']; 
        $rows[] = [$this->rango];                   
        $rows[] = [''];                             
        
        $rows[] = ['Código:', $this->articulo->codigo]; 
        $rows[] = ['Producto:', $this->articulo->nombre]; 
        $rows[] = ['']; 

        // --- 1. VENTAS ---
        $this->rowVentas = count($rows) + 1; 
        $rows[] = ['1. VENTAS']; 
        
        if (count($this->data->ventas) > 0) {
            $rows[] = ['FECHA', 'DOC', 'CLIENTE', 'MODO', 'CANT.', 'TOTAL UNID.'];
            foreach ($this->data->ventas as $v) {
                $rows[] = [
                    $v->fecha_hora,
                    $v->num_comprobante,
                    $v->nombre_cliente,
                    $v->modo_venta,
                    $this->formatCantidad($v->cantidad),
                    $this->formatCantidad($v->cantidad_en_unidades) 
                ];
            }
        } else {
            $rows[] = ['No hay ventas en este periodo.'];
        }
        $rows[] = ['']; 

        // --- 2. COMPRAS ---
        $this->rowCompras = count($rows) + 1;
        $rows[] = ['2. COMPRAS / INGRESOS'];

        if (count($this->data->ingresos) > 0) {
            $rows[] = ['FECHA', 'DOC', 'REGISTRADO POR', '', '', 'CANT.'];
            foreach ($this->data->ingresos as $i) {
                $rows[] = [
                    $i->fecha_hora,
                    $i->num_comprobante,
                    $i->responsable_compra,
                    '', '', 
                    $this->formatCantidad($i->cantidad)
                ];
            }
        } else {
            $rows[] = ['No hay compras en este periodo.'];
        }
        $rows[] = [''];

        // --- 3. TRASPASOS (NUEVO) ---
        $this->rowTraspasos = count($rows) + 1;
        $rows[] = ['3. TRASPASOS ENTRE ALMACENES'];

        if (count($traspasos) > 0) {
            // Reutilizamos columnas: A=Fecha, B=Tipo, C=Origen, D=Destino, E=Responsable, F=Cant
            $rows[] = ['FECHA', 'MOVIMIENTO', 'ORIGEN', 'DESTINO', 'RESPONSABLE', 'CANT.'];

            foreach ($traspasos as $t) {
                // Definimos signo visual
                $signo = ($t->tipo_movimiento == 'Entrada' || $t->tipo_movimiento == 'ENTRADA') ? '+' : '-';
                $cantTexto = $signo . $t->cantidad;

                $rows[] = [
                    $t->fecha_hora,
                    strtoupper($t->tipo_movimiento),
                    $t->almacen_origen,
                    $t->almacen_destino,
                    $t->responsable,
                    $this->formatCantidad($cantTexto) // Reutilizamos format para agregar "unidades"
                ];
            }
        } else {
            $rows[] = ['No hay traspasos en este periodo.'];
        }
        $rows[] = [''];

        // --- 4. AJUSTES (Movido a sección 4) ---
        $this->rowAjustes = count($rows) + 1;
        $rows[] = ['4. AJUSTES'];

        if (count($this->data->ajustes) > 0) {
            $rows[] = ['FECHA', 'MOTIVO', '', '', '', 'CANT.'];
            foreach ($this->data->ajustes as $a) {
                // Corrección: Usar propiedad correcta de ajuste
                $cantAbs = abs($a->cantidad);
                $signo = ($a->cantidad > 0) ? '+' : '-';
                $cantTexto = $signo . $cantAbs;

                $rows[] = [
                    $a->fecha_hora,
                    $a->motivo, 
                    '', '', '', 
                    $this->formatCantidad($cantTexto) 
                ];
            }
        } else {
            $rows[] = ['No hay ajustes en este periodo.'];
        }

        return $rows;
    }

    private function formatCantidad($cant)
    {
        // Simple lógica para no repetir "unidad" si ya viene formateado, o agregarlo
        // Aquí asumimos que $cant es un número o string numérico.
        // Si contiene texto, solo lo devolvemos, si es numérico agregamos sufijo.
        if (is_numeric(str_replace(['+', '-'], '', $cant))) {
             return $cant . ' ' . (abs((float)$cant) == 1 ? 'unidad' : 'unidades');
        }
        return $cant;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Fecha
            'B' => 15, // Doc / Mov
            'C' => 30, // Cliente / Origen (Ajustado un poco)
            'D' => 30, // Modo / Destino (Ajustado para nombres largos de almacen)
            'E' => 20, // Cant / Responsable
            'F' => 20, // Total
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]], 
            2 => ['font' => ['italic' => true, 'size' => 10]], 
            4 => ['font' => ['bold' => true]], 
            5 => ['font' => ['bold' => true]], 
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
                // Títulos Generales
                $sheet->mergeCells('A1:F1'); 
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Estilos de Secciones
                $this->estilarSeccion($sheet, $this->rowVentas, 'C8DCFF'); // Azulito
                $this->estilarSeccion($sheet, $this->rowCompras, 'DCFFDC'); // Verdecito
                $this->estilarSeccion($sheet, $this->rowTraspasos, 'E6E6FA'); // Lila (NUEVO)
                $this->estilarSeccion($sheet, $this->rowAjustes, 'FFF0C8'); // Naranja suave
            },
        ];
    }

    private function estilarSeccion($sheet, $rowNumber, $colorHex)
    {
        // Estilo del Título de la Sección (Ej: "3. TRASPASOS...")
        $sheet->mergeCells("A{$rowNumber}:F{$rowNumber}");
        $sheet->getStyle("A{$rowNumber}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colorHex]
            ]
        ]);

        $headerRow = $rowNumber + 1;
        $valorCelda = $sheet->getCell("A{$headerRow}")->getValue();
        
        // Mensajes de "No hay datos..." para centrar y poner cursiva
        $mensajesVacios = [
            'No hay ventas en este periodo.',
            'No hay compras en este periodo.',
            'No hay traspasos en este periodo.',
            'No hay ajustes en este periodo.'
        ];

        if (!in_array($valorCelda, $mensajesVacios)) {
            // Si HAY datos, ponemos negrita a los encabezados de tabla (FECHA, DOC, etc)
             $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            
            // Alineamos a la derecha las columnas de cantidades (E y F en general, o solo F)
            // Para Traspasos, la cantidad está en F.
            $sheet->getStyle("F{$headerRow}:F1000")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        } else {
            // Si NO hay datos, centramos el mensaje
            $sheet->mergeCells("A{$headerRow}:F{$headerRow}");
            $sheet->getStyle("A{$headerRow}")->applyFromArray([
                'font' => ['italic' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
        }
    }
}