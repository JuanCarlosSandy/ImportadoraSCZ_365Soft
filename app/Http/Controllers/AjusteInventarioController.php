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

        if ($tipo == 'excel') {
            return Excel::download(
                new AjusteInventarioExport($fechaInicio, $fechaFin, $idAlmacen, $buscar), 
                'Reporte_Ajustes_' . date('d-m-Y') . '.xlsx'
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

        $ajustes = $query->orderBy('ajuste_invetarios.id', 'desc')->get();

        $pdf = new PDFConFooter('P', 'mm', 'A4'); 
        $pdf->AddPage();
        $pdf->AliasNbPages();

        $rutaLogo = public_path('img/logoPrincipal.png');
        if (file_exists($rutaLogo)) {
            $pdf->Image($rutaLogo, 10, 5, 20);
        }

        $pdf->SetY(15);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('REPORTE DE AJUSTES DE INVENTARIO'), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, utf8_decode("Rango de Fecha: $fechaInicio al $fechaFin"), 0, 1, 'C');
        
        if ($idAlmacen) {
            $nombreAlmacen = $ajustes->first() ? $ajustes->first()->nombre_almacen : 'Almacén Seleccionado';
            $pdf->Cell(0, 5, utf8_decode("Almacén: $nombreAlmacen"), 0, 1, 'C');
        } else {
            $pdf->Cell(0, 5, utf8_decode("Almacén: TODOS"), 0, 1, 'C');
        }
        $pdf->Ln(5);

        // ENCABEZADOS
        $pdf->SetFont('Arial', 'B', 7); 
        $pdf->SetFillColor(220, 220, 220);        
        
        $pdf->Cell(25, 7, 'FECHA', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'ALMACEN', 1, 0, 'C', true);
        $pdf->Cell(15, 7, 'TIPO', 1, 0, 'C', true);
        $pdf->Cell(60, 7, 'ARTICULO', 1, 0, 'C', true);
        $pdf->Cell(15, 7, 'CANTIDAD', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'MOTIVO', 1, 1, 'C', true); 

        // CUERPO
        $pdf->SetFont('Arial', '', 7); 

        if ($ajustes->count() == 0) {
            $pdf->Cell(190, 10, utf8_decode('No hay registros para los filtros seleccionados.'), 1, 1, 'C');
        }

        foreach ($ajustes as $row) {
            $nombreArt = substr(utf8_decode($row->nombre_articulo), 0, 45); 
            $motivo = substr(utf8_decode($row->justificacion), 0, 25);
            $almacenCorto = substr(utf8_decode($row->nombre_almacen), 0, 20);
            
            $tipo = strtoupper($row->tipo_movimiento) == 'ENTRADA' ? 'ENTRADA' : 'SALIDA';
            
            $pdf->Cell(25, 6, date('d/m/y H:i', strtotime($row->created_at)), 1, 0, 'C');
            $pdf->Cell(30, 6, $almacenCorto, 1, 0, 'L');
            $pdf->Cell(15, 6, $tipo, 1, 0, 'C');
            $pdf->Cell(60, 6, $nombreArt, 1, 0, 'L');
            $pdf->Cell(15, 6, $row->cantidad, 1, 0, 'R');
            $pdf->Cell(35, 6, $motivo, 1, 1, 'L');
        }

        $nombreArchivo = 'Reporte_Ajustes_' . now()->format('Ymd_His') . '.pdf';
        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"$nombreArchivo\"");
    }
}

class PDFConFooter extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100);
        $this->Cell(0, 10, utf8_decode('Ajuste de Inventario - Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}
