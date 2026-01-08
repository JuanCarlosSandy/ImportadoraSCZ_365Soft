<?php

namespace App\Http\Controllers;

use App\AjusteInvetario;
use App\Inventario;
use App\TipoBajas;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use FPDF;

class AjusteInventarioController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        if ($buscar == '') {
            $ajuste = AjusteInvetario::join('articulos', 'ajuste_invetarios.producto', '=', 'articulos.id')
                ->join('tipo_bajas', 'ajuste_invetarios.idtipobajas', '=', 'tipo_bajas.id')
                ->join('almacens', 'ajuste_invetarios.almacen', '=', 'almacens.id')
                ->select(
                    'ajuste_invetarios.*',
                    'articulos.nombre as nombre',
                    'tipo_bajas.nombre as tipo',
                    'almacens.nombre_almacen as nombre_almacen',
                    'articulos.descripcion_fabrica'
                )
                ->orderBy('id', 'desc')->paginate(10);
        } else {
            $ajuste = AjusteInvetario::join('articulos', 'ajuste_invetarios.producto', '=', 'articulos.id')
                ->join('tipo_bajas', 'ajuste_invetarios.idtipobajas', '=', 'tipo_bajas.id')
                ->join('almacens', 'ajuste_invetarios.almacen', '=', 'almacens.id')
                ->select(
                    'ajuste_invetarios.*',
                    'articulos.nombre as nombre',
                    'tipo_bajas.nombre as tipo',
                    'almacens.nombre_almacen as nombre_almacen',
                    'articulos.descripcion_fabrica'
                )
                ->when($criterio == '', function ($query) use ($buscar) {
                    $query->where(function ($q) use ($buscar) {
                        $q->where('articulos.nombre', 'like', "%$buscar%")
                            ->orWhere('tipo_bajas.nombre', 'like', "%$buscar%")
                            ->orWhere('almacens.nombre_almacen', 'like', "%$buscar%")
                            ->orWhere('ajuste_invetarios.cantidad', 'like', "%$buscar%")
                            ->orWhere('ajuste_invetarios.created_at', 'like', "%$buscar%");
                    });
                }, function ($query) use ($criterio, $buscar) {
                    $query->where($criterio, 'like', "%$buscar%");
                })
                ->orderBy('id', 'desc')->paginate(10);
        }
        return [
            'pagination' => [
                'total' => $ajuste->total(),
                'current_page' => $ajuste->currentPage(),
                'per_page' => $ajuste->perPage(),
                'last_page' => $ajuste->lastPage(),
                'from' => $ajuste->firstItem(),
                'to' => $ajuste->lastItem(),
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

    public function exportarPDF(Request $request)
    {
        $productos = $request->productos;
        $almacenId = $request->almacen;
        $motivo = $request->motivo;
        $fecha = now()->format('d/m/Y H:i');

        // Obtener el nombre del almacén
        $nombreAlmacen = \DB::table('almacens')->where('id', $almacenId)->value('nombre_almacen');

        $pdf = new PDFConFooter('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AliasNbPages();

        $truncarTexto = function ($pdf, $texto, $maxWidth) {
            $texto = utf8_decode($texto);
            if ($pdf->GetStringWidth($texto) <= $maxWidth) {
                return $texto;
            }
            while ($pdf->GetStringWidth($texto . '...') > $maxWidth && strlen($texto) > 0) {
                $texto = substr($texto, 0, -1);
            }
            return $texto . '...';
        };

        // ENCABEZADO
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, utf8_decode("REPORTE DE AJUSTE DE INVENTARIO - $nombreAlmacen"), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, utf8_decode("Generado el $fecha"), 0, 1, 'C');
        $pdf->Ln(5);

        // FILTROS
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(50, 6, utf8_decode("Almacén: $nombreAlmacen"), 0, 0, 'L');
        $pdf->Cell(80, 6, utf8_decode("Motivo: $motivo"), 0, 1, 'L');
        $pdf->Ln(8);

        $pageWidth = $pdf->GetPageWidth() - 40;
        $startX = ($pdf->GetPageWidth() - $pageWidth) / 2;

        // ENCABEZADO DE TABLA
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetX($startX);
        $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
        $pdf->Cell(30, 8, utf8_decode('Código'), 1, 0, 'C', true);
        $pdf->Cell(70, 8, utf8_decode('Producto'), 1, 0, 'C', true);
        $pdf->Cell(50, 8, utf8_decode('Proveedor'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, utf8_decode('Stock Actual'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, utf8_decode('Stock Real'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, utf8_decode('Cantidad Ajuste'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, utf8_decode('Stock Restante'), 1, 1, 'C', true);

        // CUERPO
        $pdf->SetFont('Arial', '', 8);
        $contador = 1;

        foreach ($productos as $producto) {
            // Valida que los campos existan, si no, asigna 0
            $stockActual = isset($producto['stock_actual']) ? $producto['stock_actual'] : 0;
            $stockReal = isset($producto['stock_real']) ? $producto['stock_real'] : 0;
            $cantidadAjuste = $stockActual - $stockReal;
            $stockRestante = $stockReal; // O calcula según tu lógica

            $pdf->SetX($startX);
            $pdf->Cell(10, 7, $contador++, 1, 0, 'C');
            $pdf->Cell(30, 7, $truncarTexto($pdf, $producto['codigo'] ?? '', 28), 1);
            $pdf->Cell(70, 7, $truncarTexto($pdf, $producto['nombre'] ?? '', 68), 1, 0, 'L');
            $pdf->Cell(50, 7, $truncarTexto($pdf, $producto['nombre_proveedor'] ?? '', 48), 1, 0, 'L');
            $pdf->Cell(25, 7, number_format($stockActual, 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(25, 7, number_format($stockReal, 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(25, 7, number_format($cantidadAjuste, 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(25, 7, number_format($stockRestante, 0, ',', '.'), 1, 1, 'R');
        }

        $nombreArchivo = 'Ajuste_Inventario_' . str_replace(' ', '_', $nombreAlmacen) . '_' . now()->format('d-m-Y') . '.pdf';
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
