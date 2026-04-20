<?php

namespace App\Http\Controllers;

use App\AjusteInvetario;
use App\Inventario;
use App\TipoBajas;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AjusteInventarioExport;
use FPDF;

class AjusteInventarioController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $idAlmacen = $request->idAlmacen;

        $query = AjusteInvetario::join('articulos', 'ajuste_invetarios.producto', '=', 'articulos.id')
            ->join('tipo_bajas', 'ajuste_invetarios.idtipobajas', '=', 'tipo_bajas.id')
            ->join('almacens', 'ajuste_invetarios.almacen', '=', 'almacens.id')
            ->select(
                'ajuste_invetarios.*',
                'articulos.nombre as nombre',
                'tipo_bajas.nombre as tipo',
                'almacens.nombre_almacen as nombre_almacen',
                'articulos.descripcion_fabrica'
            );

        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $query->whereBetween('ajuste_invetarios.created_at', [
                $fechaInicio . ' 00:00:00', 
                $fechaFin . ' 23:59:59'
            ]);
        }

        if (!empty($idAlmacen)) {
            $query->where('ajuste_invetarios.almacen', $idAlmacen);
        }

        if ($buscar != '') {
            $query->where(function ($q) use ($buscar, $criterio) {
                if ($criterio != '') {
                    $q->where($criterio, 'like', "%$buscar%");
                } 
                else {
                    $q->where('articulos.nombre', 'like', "%$buscar%")
                    ->orWhere('tipo_bajas.nombre', 'like', "%$buscar%")
                    ->orWhere('almacens.nombre_almacen', 'like', "%$buscar%")
                    ->orWhere('ajuste_invetarios.cantidad', 'like', "%$buscar%")
                    ->orWhere('ajuste_invetarios.created_at', 'like', "%$buscar%");
                }
            });
        }

        $ajuste = $query->orderBy('ajuste_invetarios.id', 'desc')->paginate(10);

        return [
            'pagination' => [
                'total'        => $ajuste->total(),
                'current_page' => $ajuste->currentPage(),
                'per_page'     => $ajuste->perPage(),
                'last_page'    => $ajuste->lastPage(),
                'from'         => $ajuste->firstItem(),
                'to'           => $ajuste->lastItem(),
            ],
            'ajuste' => $ajuste
        ];
    }

    public function listarMotivo(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        if ($buscar == '') {
            $motivo = TipoBajas::orderBy('id', 'desc')->paginate(6);
        } else {
            $motivo = TipoBajas::where('nombre', 'like', '%' . $buscar . '%')->orderBy('id', 'desc')->paginate(6);
        }


        return [
            'pagination' => [
                'total' => $motivo->total(),
                'current_page' => $motivo->currentPage(),
                'per_page' => $motivo->perPage(),
                'last_page' => $motivo->lastPage(),
                'from' => $motivo->firstItem(),
                'to' => $motivo->lastItem(),
            ],
            'motivos' => $motivo
        ];
    }
    //nuevo codigo para obtener el stock actual añadido en fecha 13/03/25
    public function obtenerStock(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        // Validamos que se envíe el id del producto y del almacén
        $producto = $request->producto;
        $almacen = $request->almacen;

        // Buscar la suma del stock total del producto en el almacén
        $stockTotal = Inventario::where('idarticulo', $producto)
            ->where('idalmacen', $almacen)
            ->sum('saldo_stock'); // Sumamos el saldo_stock para todos los registros que coincidan

        // Retornar el stock total o 0 si no existe en el inventario
        return response()->json(['stock_actual' => $stockTotal ?: 0]);
    }



    public function store(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $ajuste = new AjusteInvetario();
        $ajuste->cantidad = $request->cantidad;
        $ajuste->idtipobajas = $request->idtipobaja;
        $ajuste->producto = $request->producto;
        $ajuste->almacen = $request->idAlmacenSeleccionado;

        $ajuste->save();

        $detalle = [
            'cantidad' => $ajuste->cantidad,
            'idarticulo' => $ajuste->producto
        ];
        $this->actualizarInventario($ajuste->almacen, $detalle);

        return ['idArticulo' => $ajuste->id];
    }

    public function registrarMultiple(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'almacen_id' => 'required|integer',
            'motivo_id' => 'required|integer',
            'productos' => 'required|array|min:1',
            'productos.*.tipo_movimiento' => 'required|in:entrada,salida',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->productos as $productoData) {
                
                $inventario = Inventario::where('idarticulo', $productoData['producto_id'])
                    ->where('idalmacen', $request->almacen_id)
                    ->first();

                $stockActual = $inventario ? $inventario->saldo_stock : 0;

                if ($productoData['tipo_movimiento'] === 'salida') {
                    if ($productoData['cantidad'] > $stockActual) {
                        throw new \Exception("No hay suficiente stock para dar de baja ({$productoData['cantidad']}) del producto ID: {$productoData['producto_id']}. Stock actual: {$stockActual}");
                    }
                }

                $ajuste = new AjusteInvetario();
                $ajuste->cantidad = $productoData['cantidad'];
                $ajuste->idtipobajas = $request->motivo_id;
                $ajuste->producto = $productoData['producto_id'];
                $ajuste->almacen = $request->almacen_id;
                $ajuste->tipo_movimiento = $productoData['tipo_movimiento'];
                $ajuste->save();

                if ($inventario) {
                    if ($ajuste->tipo_movimiento === 'entrada') {
                        $inventario->saldo_stock += $ajuste->cantidad;
                    } else {
                        $inventario->saldo_stock -= $ajuste->cantidad;
                    }
                    $inventario->save();

                } else {
                    if ($ajuste->tipo_movimiento === 'entrada') {
                        $nuevoInv = new Inventario();
                        $nuevoInv->idarticulo = $ajuste->producto;
                        $nuevoInv->idalmacen = $ajuste->almacen;
                        $nuevoInv->saldo_stock = $ajuste->cantidad;
                        $nuevoInv->cantidad = $ajuste->cantidad; 
                        
                        $nuevoInv->fecha_vencimiento = '2099-12-31'; 

                        $nuevoInv->save();
                    } else {
                        throw new \Exception("No existe inventario del producto para realizar la salida.");
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Ajuste procesado correctamente"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function actualizarInventario($idAlmacen, $detalle)
    {
        $cantidadRestante = $detalle['cantidad'];

        $inventarios = Inventario::where('idalmacen', $idAlmacen)
            ->where('idarticulo', $detalle['idarticulo'])
            ->where('saldo_stock', '>', 0) 
            ->orderBy('fecha_vencimiento', 'asc') 
            ->get();

        foreach ($inventarios as $inventario) {
            if ($cantidadRestante <= 0) break;

            if ($inventario->saldo_stock >= $cantidadRestante) {
                $inventario->saldo_stock -= $cantidadRestante;
                $cantidadRestante = 0;
            } else {
                $cantidadRestante -= $inventario->saldo_stock;
                $inventario->saldo_stock = 0;
            }
            $inventario->save();
        }

        if ($cantidadRestante > 0) {
            throw new \Exception("Inconsistencia: El stock físico no coincidió con el cálculo previo.");
        }
    }

    public function registrarMotivo(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $motivo = new TipoBajas();

        $motivo->nombre = $request->nombre;
        $motivo->save();
    }

    public function generarReporte(Request $request, $tipo)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $idAlmacen = $request->idAlmacen;
        $buscar = $request->buscar;

        $fechaInicioNombre = $this->formatearFechaParaNombre($fechaInicio);
        $fechaFinNombre = $this->formatearFechaParaNombre($fechaFin);
        $nombreBase = "ReporteAjustes_{$fechaInicioNombre}_{$fechaFinNombre}";

        if ($tipo == 'excel') {
            return Excel::download(
                new AjusteInventarioExport($fechaInicio, $fechaFin, $idAlmacen, $buscar), 
                $nombreBase . '.xlsx'
            );
        }         
        if ($tipo == 'pdf') {
            return $this->exportarPDF($fechaInicio, $fechaFin, $idAlmacen, $buscar);
        }
    }

    public function exportarPDF($fechaInicio, $fechaFin, $idAlmacen, $buscar)
    {
        // OBTENER DATOS
        $query = AjusteInvetario::join('articulos', 'ajuste_invetarios.producto', '=', 'articulos.id')
            ->join('tipo_bajas', 'ajuste_invetarios.idtipobajas', '=', 'tipo_bajas.id')
            ->join('almacens', 'ajuste_invetarios.almacen', '=', 'almacens.id')
            ->select(
                'ajuste_invetarios.*',
                'articulos.nombre as nombre_articulo',
                'articulos.codigo',
                'tipo_bajas.nombre as justificacion',
                'almacens.nombre_almacen'
            );

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('ajuste_invetarios.created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        }
        if ($idAlmacen) {
            $query->where('ajuste_invetarios.almacen', $idAlmacen);
        }
        if ($buscar) {
            $query->where('articulos.nombre', 'like', '%' . $buscar . '%');
        }

        $ajustes = $query->orderBy('ajuste_invetarios.id', 'desc')->get();

        $pdf = new PDFConFooter('L', 'mm', 'A4'); 
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // --- ENCABEZADO ---
        $rutaLogo = public_path('img/logoPrincipal.png');
        if (file_exists($rutaLogo)) {
            $pdf->Image($rutaLogo, 10, 5, 20);
        }

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 10, utf8_decode('REPORTE DE AJUSTES DE INVENTARIO'), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, utf8_decode('Fecha de generación: ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        $pdf->Ln(5);

        // --- CAJA DE FILTROS ---
        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Rect(10, $pdf->GetY(), 277, 16, 'F');

        $pdf->SetX(12);
        $pdf->Cell(35, 8, utf8_decode('Rango Fechas:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(100, 8, utf8_decode($fechaInicio . ' al ' . $fechaFin), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 8, utf8_decode('Almacén:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $nombreAlmacen = $idAlmacen ? ($ajustes->first() ? $ajustes->first()->nombre_almacen : 'Almacén Seleccionado') : 'TODOS';
        $pdf->Cell(100, 8, utf8_decode(substr($nombreAlmacen, 0, 50)), 0, 1, 'L');

        $pdf->SetX(12);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(35, 8, utf8_decode('Búsqueda:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(100, 8, utf8_decode($buscar ?: 'Ninguna'), 0, 0, 'L');
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 8, utf8_decode('Registros:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(100, 8, $ajustes->count(), 0, 1, 'L');
        
        $pdf->Ln(5);

        // --- CABECERA DE TABLA ---
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(52, 73, 94); // Azul oscuro corporativo
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(180, 180, 180);

        $pdf->Cell(30, 8, 'FECHA', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'ALMACEN', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'TIPO', 1, 0, 'C', true);
        $pdf->Cell(92, 8, 'ARTICULO', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'CANTIDAD', 1, 0, 'C', true);
        $pdf->Cell(65, 8, 'MOTIVO', 1, 1, 'C', true);

        // --- CUERPO DE TABLA ---
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(0, 0, 0);

        if ($ajustes->count() == 0) {
            $pdf->Cell(277, 10, utf8_decode('No hay registros para los filtros seleccionados.'), 1, 1, 'C');
        }

        $fill = false;
        foreach ($ajustes as $row) {
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            
            $tipo = strtoupper($row->tipo_movimiento) == 'ENTRADA' ? 'ENTRADA' : 'SALIDA';
            
            $pdf->Cell(30, 7, date('d/m/y H:i', strtotime($row->created_at)), 1, 0, 'C', true);
            $pdf->Cell(45, 7, utf8_decode(substr($row->nombre_almacen, 0, 30)), 1, 0, 'L', true);
            $pdf->Cell(20, 7, $tipo, 1, 0, 'C', true);
            $pdf->Cell(92, 7, utf8_decode(substr($row->nombre_articulo, 0, 65)), 1, 0, 'L', true);
            $pdf->Cell(25, 7, number_format($row->cantidad, 2), 1, 0, 'R', true);
            $pdf->Cell(65, 7, utf8_decode(substr($row->justificacion, 0, 50)), 1, 1, 'L', true);
            $fill = !$fill;
        }

        $fechaInicioNombre = $this->formatearFechaParaNombre($fechaInicio);
        $fechaFinNombre = $this->formatearFechaParaNombre($fechaFin);
        $nombreArchivo = "ReporteAjustes_{$fechaInicioNombre}_{$fechaFinNombre}.pdf";
        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"$nombreArchivo\"");
    }

    private function formatearFechaParaNombre($fecha)
    {
        if (empty($fecha)) {
            return now()->format('Ymd');
        }

        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return now()->format('Ymd');
        }

        return date('Ymd', $timestamp);
    }
}

class PDFConFooter extends FPDF
{
    public function Footer()
    {
        // Posiciona el pie de página a 1.5 cm del final
        $this->SetY(-15);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, utf8_decode('Reporte Generado por el sistema - Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'C');
    }
}
