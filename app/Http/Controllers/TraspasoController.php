<?php

namespace App\Http\Controllers;

use App\DetalleTraspaso;
use App\Inventario;
use App\Traspaso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use FPDF;

class TraspasoController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;

        $traspasos = Traspaso::select(
            'traspasos.id',
            'traspasos.almacen_origen',
            'traspasos.almacen_destino',
            'traspasos.fecha_traspaso',
            'traspasos.idusuario',
            'traspasos.created_at',
            'traspasos.estado',
            'ao.nombre_almacen as nombre_almacen_origen',
            'ad.nombre_almacen as nombre_almacen_destino',
            'personas.nombre as nombre_usuario' // ← Aquí sacamos el nombre del usuario
        )
            ->join('almacens as ao', 'traspasos.almacen_origen', '=', 'ao.id')
            ->join('almacens as ad', 'traspasos.almacen_destino', '=', 'ad.id')
            ->join('users', 'traspasos.idusuario', '=', 'users.id')
            ->join('personas', 'users.id', '=', 'personas.id') // ← Join con personas
            ->orderBy('traspasos.created_at', 'desc')

            ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
            ->paginate(100);

        return [
            'pagination' => [
                'total' => $traspasos->total(),
                'current_page' => $traspasos->currentPage(),
                'per_page' => $traspasos->perPage(),
                'last_page' => $traspasos->lastPage(),
                'from' => $traspasos->firstItem(),
                'to' => $traspasos->lastItem(),
            ],
            'traspasos' => $traspasos
        ];
    }

    //--registrando datos de Trapaso luego pasando datosa  inventario para registrar---
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Crear el traspaso
            $traspasos = new Traspaso();
            $traspasos->tipo_traspaso = $request->tipo_traspaso;
            $traspasos->idusuario = \Auth::user()->id;
            $traspasos->almacen_origen = $request->almacen_origen;
            $traspasos->almacen_destino = $request->almacen_destino;
            $traspasos->fecha_traspaso = $request->fecha_traspaso;
            $traspasos->save();

            // Obtener los detalles del traspaso
            $detalles = $request->data;

            foreach ($detalles as $detalle) {
                $cantidadRestante = $detalle['cantidad_traspaso'];

                // Inventarios origen (múltiples, ordenados por fecha de vencimiento)
                $inventariosOrigen = Inventario::where('idalmacen', $detalle['idalmacen'])
                    ->where('idarticulo', $detalle['idarticulo'])
                    ->where('saldo_stock', '>', 0)
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->get();

                foreach ($inventariosOrigen as $inventarioOrigen) {
                    if ($cantidadRestante <= 0)
                        break;

                    $cantidadADescontar = min($cantidadRestante, $inventarioOrigen->saldo_stock);
                    $inventarioOrigen->saldo_stock -= $cantidadADescontar;
                    $inventarioOrigen->save();

                    // Buscar o crear inventario en el destino con la misma fecha de vencimiento
                    $inventarioDestino = Inventario::where('idalmacen', $detalle['idalmacendes'])
                        ->where('idarticulo', $detalle['idarticulo'])
                        ->where('fecha_vencimiento', $inventarioOrigen->fecha_vencimiento)
                        ->first();

                    if ($inventarioDestino) {
                        $inventarioDestino->saldo_stock += $cantidadADescontar;
                        $inventarioDestino->cantidad += $cantidadADescontar;
                    } else {
                        $inventarioDestino = new Inventario();
                        $inventarioDestino->idalmacen = $detalle['idalmacendes'];
                        $inventarioDestino->idarticulo = $detalle['idarticulo'];
                        $inventarioDestino->saldo_stock = $cantidadADescontar;
                        $inventarioDestino->cantidad = $cantidadADescontar;
                        $inventarioDestino->fecha_vencimiento = $inventarioOrigen->fecha_vencimiento;
                    }
                    $inventarioDestino->save();

                    // Registrar detalle del traspaso por lote
                    $detalletraspaso = new DetalleTraspaso();
                    $detalletraspaso->idtraspaso = $traspasos->id;
                    $detalletraspaso->idinventario = $inventarioDestino->id;
                    $detalletraspaso->cantidad_traspaso = $cantidadADescontar;
                    $detalletraspaso->save();

                    $cantidadRestante -= $cantidadADescontar;
                }

                if ($cantidadRestante > 0) {
                    throw new Exception('Stock insuficiente para completar el traspaso del artículo: ' . $detalle['idarticulo']);
                }
            }


            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar traspaso', ['exception' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    //---listado por id lo que se traspaso--
    public function indexPorID(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $idtraspaso = $request->idtraspaso;

        // 🔹 DATOS GENERALES DEL TRASPASO
        $traspaso = DB::table('traspasos as t')
            ->join('almacens as ao', 't.almacen_origen', '=', 'ao.id')
            ->join('almacens as ad', 't.almacen_destino', '=', 'ad.id')
            ->select(
                't.id',
                't.created_at as fecha_traspaso',
                'ao.nombre_almacen as almacen_origen',
                'ad.nombre_almacen as almacen_destino'
            )
            ->where('t.id', $idtraspaso)
            ->first();

        // 🔹 DETALLE DEL TRASPASO
        $detalletrasp = DetalleTraspaso::join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
            ->join('traspasos as t', 'detalle_traspasos.idtraspaso', '=', 't.id')
            ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->select(
                'detalle_traspasos.id',
                'detalle_traspasos.cantidad_traspaso',
                'inventarios.saldo_stock',
                'inventarios.fecha_vencimiento',
                'articulos.nombre as nombre_producto',
                'articulos.unidad_envase',
                'articulos.precio_costo_unid',
                'proveedores.contacto'
            )
            ->where('detalle_traspasos.idtraspaso', $idtraspaso)
            ->get();

        // 🔹 RESPUESTA FINAL
        return [
            'traspaso' => $traspaso,
            'detalletrasp' => $detalletrasp
        ];
    }

    public function anularTraspaso($id)
    {
        DB::beginTransaction();

        try {

            $traspaso = Traspaso::findOrFail($id);

            // obtener detalles del traspaso
            $detalles = DetalleTraspaso::where('idtraspaso', $id)->get();

            foreach ($detalles as $detalle) {

                // inventario destino (donde se sumó)
                $inventarioDestino = Inventario::findOrFail($detalle->idinventario);

                // restar del destino
                $inventarioDestino->saldo_stock -= $detalle->cantidad_traspaso;
                $inventarioDestino->save();

                // buscar inventario del mismo articulo en almacen origen
                $inventarioOrigen = Inventario::where('idarticulo', $inventarioDestino->idarticulo)
                    ->where('idalmacen', $traspaso->almacen_origen)
                    ->first();

                if ($inventarioOrigen) {
                    // sumar al origen
                    $inventarioOrigen->saldo_stock += $detalle->cantidad_traspaso;
                    $inventarioOrigen->save();
                }
            }

            // marcar traspaso como anulado
            $traspaso->estado = 0;
            $traspaso->save();

            DB::commit();

            return response()->json([
                'message' => 'Traspaso anulado correctamente'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Error al anular el traspaso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportarPdf($id)
    {
        $traspaso = Traspaso::join('almacens as ao', 'traspasos.almacen_origen', '=', 'ao.id')
            ->join('almacens as ad', 'traspasos.almacen_destino', '=', 'ad.id')
            ->join('users', 'traspasos.idusuario', '=', 'users.id')
            ->join('personas', 'users.id', '=', 'personas.id')
            ->select(
                'traspasos.*',
                'ao.nombre_almacen as origen',
                'ad.nombre_almacen as destino',
                'personas.nombre as usuario'
            )
            ->where('traspasos.id', $id)
            ->firstOrFail();

        $detalles = DB::table('detalle_traspasos')
            ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
            ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->select(
                'articulos.codigo',
                'articulos.nombre',
                'articulos.descripcion_fabrica',
                'detalle_traspasos.cantidad_traspaso as cantidad'
            )
            ->where('detalle_traspasos.idtraspaso', $id)
            ->get();

        $pdf = new PDFConFooter('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->AliasNbPages();
        if ($traspaso->estado == 0) {

            $pdf->SetFont('Arial', 'B', 60);
            $pdf->SetTextColor(230, 230, 230);

            $pdf->RotatedText(70, 190, 'ANULADO', 45);

            $pdf->SetTextColor(0, 0, 0);
        }

        $rutaLogo = public_path('img/logoPrincipal.png');
        if (file_exists($rutaLogo)) {
            $pdf->Image($rutaLogo, 10, 5, 20);
        }

        $pdf->SetY(15);

        // --- ENCABEZADO ---
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, utf8_decode('REPORTE DE TRASPASO'), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(5);

        // Información General
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, utf8_decode('N° Traspaso:'), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 6, $traspaso->id, 0, 0);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 6, 'Fecha:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($traspaso->created_at)), 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, 'Origen:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 6, utf8_decode($traspaso->origen), 0, 0);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 6, 'Destino:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, utf8_decode($traspaso->destino), 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, 'Responsable:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, utf8_decode($traspaso->usuario), 0, 1);

        $pdf->Ln(10);

        // --- TABLA DE DETALLES ---
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);

        $pdf->Cell(25, 8, utf8_decode('CÓDIGO'), 1, 0, 'C', true);
        $pdf->Cell(135, 8, 'PRODUCTO', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'CANTIDAD', 1, 1, 'C', true);

        // Cuerpo
        $pdf->SetFont('Arial', '', 9);
        $totalCantidad = 0;

        foreach ($detalles as $det) {
            $nombreProducto = utf8_decode($det->nombre);
            if ($det->descripcion_fabrica) {
                $nombreProducto .= ' (' . utf8_decode($det->descripcion_fabrica) . ')';
            }

            $nombreProducto = substr($nombreProducto, 0, 80);

            $pdf->Cell(25, 7, utf8_decode($det->codigo), 1, 0, 'C');
            $pdf->Cell(135, 7, $nombreProducto, 1, 0, 'L');
            $pdf->Cell(30, 7, number_format($det->cantidad, 0), 1, 1, 'C');

            $totalCantidad += $det->cantidad;
        }

        // --- TOTALES ---
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(160, 8, 'TOTAL UNIDADES TRASPASADAS', 1, 0, 'R', true);
        $pdf->Cell(30, 8, number_format($totalCantidad, 0), 1, 1, 'C', true);

        // --- NOMBRE DEL ARCHIVO ---
        $origenClean = str_replace(' ', '', $traspaso->origen);
        $destinoClean = str_replace(' ', '', $traspaso->destino);
        $fechaClean = date('Y-m-d', strtotime($traspaso->fecha_traspaso));

        $nombreArchivo = "Traspaso_{$origenClean}_{$destinoClean}_{$fechaClean}.pdf";

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"$nombreArchivo\"");
    }
}

class PDFConFooter extends FPDF
{
        protected $angle = 0;

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Reporte Generado por el sistema Broken 365 - Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    function RotatedText($x, $y, $txt, $angle)
    {
        $this->Rotate($angle, $x, $y);
        $this->Text($x, $y, $txt);
        $this->Rotate(0);
    }

    function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1)
            $x = $this->x;
        if ($y == -1)
            $y = $this->y;

        if ($this->angle != 0)
            $this->_out('Q');

        $this->angle = $angle;

        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;

            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm',
                $c,
                $s,
                -$s,
                $c,
                $cx,
                $cy,
                -$cx,
                -$cy
            ));
        }
    }
}
