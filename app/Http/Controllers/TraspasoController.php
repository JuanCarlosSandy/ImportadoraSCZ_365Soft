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
use App\MovimientoInventario;
use App\Services\MovimientoInventarioService;

class TraspasoController extends Controller
{

    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;

        $usuario = \Auth::user();

        $articulos = collect(explode(',', $request->articulos))
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();

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
            'personas.nombre as nombre_usuario'
        )
            ->join(
                'almacens as ao',
                'traspasos.almacen_origen',
                '=',
                'ao.id'
            )
            ->join(
                'almacens as ad',
                'traspasos.almacen_destino',
                '=',
                'ad.id'
            )
            ->join('users', 'traspasos.idusuario', '=', 'users.id')
            ->join('personas', 'users.id', '=', 'personas.id');

        // Filtrar por sucursal para los roles 1, 2 y 3
        if (in_array($usuario->idrol, [1, 2, 3])) {
            $traspasos->where(function ($query) use ($usuario) {
                $query->where('ao.sucursal', $usuario->idsucursal)
                    ->orWhere('ad.sucursal', $usuario->idsucursal);
            });
        }

        // Filtrar por artículos seleccionados
        if (!empty($articulos)) {
            $traspasos->whereExists(function ($query) use ($articulos) {
                $query->select(\DB::raw(1))
                    ->from('detalle_traspasos')
                    ->join(
                        'inventarios',
                        'detalle_traspasos.idinventario',
                        '=',
                        'inventarios.id'
                    )
                    ->whereColumn(
                        'detalle_traspasos.idtraspaso',
                        'traspasos.id'
                    )
                    ->whereIn(
                        'inventarios.idarticulo',
                        $articulos
                    );
            });
        } else {
            // Filtrar por fecha cuando no se seleccionan artículos
            if ($fechaInicio && $fechaFin) {
                $traspasos->whereBetween(
                    'traspasos.fecha_traspaso',
                    [$fechaInicio, $fechaFin]
                );
            }
        }

        $traspasos = $traspasos
            ->orderBy('traspasos.created_at', 'desc')
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
        $request->validate([
            'almacen_origen' => 'required|integer|exists:almacens,id',
            'almacen_destino' => 'required|integer|exists:almacens,id|different:almacen_origen',
            'fecha_traspaso' => 'required|date',
            'data' => 'required|array|min:1',
            'data.*.idarticulo' => 'required|integer|exists:articulos,id',
            'data.*.cantidad_traspaso' => 'required|integer|min:1',
        ]);

        try {

            DB::beginTransaction();

            $idAlmacenOrigen = (int) $request->almacen_origen;
            $idAlmacenDestino = (int) $request->almacen_destino;

            // Crear la cabecera del traspaso.
            $traspasos = new Traspaso();
            $traspasos->tipo_traspaso = $request->tipo_traspaso;
            $traspasos->idusuario = \Auth::user()->id;
            $traspasos->almacen_origen = $idAlmacenOrigen;
            $traspasos->almacen_destino = $idAlmacenDestino;
            $traspasos->fecha_traspaso = $request->fecha_traspaso;
            $traspasos->save();

            $detalles = $request->data;

            // Bloquear los artículos en un orden consistente.
            $idsArticulos = collect($detalles)
                ->pluck('idarticulo')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->unique()
                ->sort()
                ->values();

            foreach ($idsArticulos as $idArticulo) {
                DB::table('articulos')
                    ->where('id', $idArticulo)
                    ->lockForUpdate()
                    ->first();
            }

            foreach ($detalles as $detalle) {

                $idarticulo = (int) $detalle['idarticulo'];
                $cantidadRestante = (int) $detalle['cantidad_traspaso'];

                // Validar que los almacenes del detalle coincidan
                // con los almacenes de la cabecera.
                if (
                    (int) $detalle['idalmacen'] !== $idAlmacenOrigen ||
                    (int) $detalle['idalmacendes'] !== $idAlmacenDestino
                ) {
                    throw new \Exception(
                        'Los almacenes del detalle no coinciden con el traspaso.'
                    );
                }

                // Bloquear inventarios de origen y destino.
                $inventariosBloqueados = Inventario::where('idarticulo', $idarticulo)
                    ->whereIn('idalmacen', [
                        $idAlmacenOrigen,
                        $idAlmacenDestino
                    ])
                    ->orderBy('idalmacen')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                // Obtener stock total del almacén de origen.
                $stockAnteriorOrigen = (int) $inventariosBloqueados
                    ->where('idalmacen', $idAlmacenOrigen)
                    ->sum('saldo_stock');

                // Obtener stock total del almacén de destino.
                $stockAnteriorDestino = (int) $inventariosBloqueados
                    ->where('idalmacen', $idAlmacenDestino)
                    ->sum('saldo_stock');

                // Inventarios disponibles en el almacén de origen.
                $inventariosOrigen = Inventario::where('idalmacen', $idAlmacenOrigen)
                    ->where('idarticulo', $idarticulo)
                    ->where('saldo_stock', '>', 0)
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                // Validar disponibilidad antes de realizar el traspaso.
                $stockDisponible = (int) $inventariosOrigen->sum('saldo_stock');

                if ($cantidadRestante > $stockDisponible) {
                    throw new \Exception(
                        'Stock insuficiente para completar el traspaso del artículo: '
                        . $idarticulo
                        . '. Stock disponible: '
                        . $stockDisponible
                    );
                }

                foreach ($inventariosOrigen as $inventarioOrigen) {

                    if ($cantidadRestante <= 0) {
                        break;
                    }

                    $cantidadADescontar = min(
                        $cantidadRestante,
                        (int) $inventarioOrigen->saldo_stock
                    );

                    // ==========================================
                    // 1. DESCONTAR STOCK DEL ALMACÉN ORIGEN
                    // ==========================================

                    $inventarioOrigen->saldo_stock -= $cantidadADescontar;
                    $inventarioOrigen->save();

                    // ==========================================
                    // 2. INCREMENTAR STOCK DEL ALMACÉN DESTINO
                    // ==========================================

                    $inventarioDestino = Inventario::where('idalmacen', $idAlmacenDestino)
                        ->where('idarticulo', $idarticulo)
                        ->where('fecha_vencimiento', $inventarioOrigen->fecha_vencimiento)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->first();

                    if ($inventarioDestino) {

                        $inventarioDestino->saldo_stock += $cantidadADescontar;
                        $inventarioDestino->cantidad += $cantidadADescontar;

                    } else {

                        $inventarioDestino = new Inventario();
                        $inventarioDestino->idalmacen = $idAlmacenDestino;
                        $inventarioDestino->idarticulo = $idarticulo;
                        $inventarioDestino->saldo_stock = $cantidadADescontar;
                        $inventarioDestino->cantidad = $cantidadADescontar;
                        $inventarioDestino->fecha_vencimiento =
                            $inventarioOrigen->fecha_vencimiento;
                    }

                    $inventarioDestino->save();

                    // ==========================================
                    // 3. REGISTRAR DETALLE DEL TRASPASO
                    // ==========================================

                    $detalletraspaso = new DetalleTraspaso();
                    $detalletraspaso->idtraspaso = $traspasos->id;
                    $detalletraspaso->idinventario = $inventarioDestino->id;
                    $detalletraspaso->cantidad_traspaso = $cantidadADescontar;
                    $detalletraspaso->stock_actual_destino =
                        $inventarioDestino->saldo_stock;
                    $detalletraspaso->save();

                    // ==========================================
                    // 4. OBTENER STOCK ACTUAL DE AMBOS ALMACENES
                    // ==========================================

                    $stockActualOrigen = (int) Inventario::where(
                        'idalmacen',
                        $idAlmacenOrigen
                    )
                        ->where('idarticulo', $idarticulo)
                        ->lockForUpdate()
                        ->get()
                        ->sum('saldo_stock');

                    $stockActualDestino = (int) Inventario::where(
                        'idalmacen',
                        $idAlmacenDestino
                    )
                        ->where('idarticulo', $idarticulo)
                        ->lockForUpdate()
                        ->get()
                        ->sum('saldo_stock');

                    // Verificar que las variaciones sean correctas.
                    if (
                        ($stockAnteriorOrigen - $stockActualOrigen) !== $cantidadADescontar ||
                        ($stockActualDestino - $stockAnteriorDestino) !== $cantidadADescontar
                    ) {
                        throw new \Exception(
                            'Inconsistencia en el movimiento de inventario del artículo: '
                            . $idarticulo
                        );
                    }

                    // ==========================================
                    // 5. REGISTRAR HISTORIAL DE SALIDA
                    // ==========================================

                    MovimientoInventarioService::registrar(
                        $idarticulo,
                        $idAlmacenOrigen,
                        MovimientoInventario::TRASPASO_SALIDA,
                        $stockAnteriorOrigen,
                        $stockActualOrigen,
                        'TRASPASO',
                        $traspasos->id,
                        $detalletraspaso->id,
                        'Traspaso hacia almacén ID: ' . $idAlmacenDestino
                    );

                    // ==========================================
                    // 6. REGISTRAR HISTORIAL DE ENTRADA
                    // ==========================================

                    MovimientoInventarioService::registrar(
                        $idarticulo,
                        $idAlmacenDestino,
                        MovimientoInventario::TRASPASO_ENTRADA,
                        $stockAnteriorDestino,
                        $stockActualDestino,
                        'TRASPASO',
                        $traspasos->id,
                        $detalletraspaso->id,
                        'Traspaso desde almacén ID: ' . $idAlmacenOrigen
                    );

                    // Actualizar saldos de referencia para el siguiente lote.
                    $stockAnteriorOrigen = $stockActualOrigen;
                    $stockAnteriorDestino = $stockActualDestino;

                    $cantidadRestante -= $cantidadADescontar;
                }

                if ($cantidadRestante > 0) {
                    throw new \Exception(
                        'Stock insuficiente para completar el traspaso del artículo: '
                        . $idarticulo
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Traspaso registrado correctamente',
                'idtraspaso' => $traspasos->id
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error al registrar traspaso', [
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
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
                'articulos.codigo as codigo_producto',
                'proveedores.contacto',
                'detalle_traspasos.stock_actual_destino'
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

            // Obtener y bloquear el traspaso.
            $traspaso = Traspaso::where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            // Evitar anulaciones duplicadas.
            if ((int) $traspaso->estado === 0) {
                throw new \Exception(
                    'Este traspaso ya fue anulado anteriormente.'
                );
            }

            $idAlmacenOrigen = (int) $traspaso->almacen_origen;
            $idAlmacenDestino = (int) $traspaso->almacen_destino;

            if ($idAlmacenOrigen === $idAlmacenDestino) {
                throw new \Exception(
                    'Los almacenes de origen y destino no pueden ser iguales.'
                );
            }

            // Obtener detalles del traspaso.
            $detalles = DetalleTraspaso::where('idtraspaso', $id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($detalles->isEmpty()) {
                throw new \Exception(
                    'El traspaso no contiene detalles.'
                );
            }

            // Obtener los artículos involucrados.
            $idsArticulos = Inventario::whereIn(
                'id',
                $detalles->pluck('idinventario')->unique()->values()->all()
            )
                ->pluck('idarticulo')
                ->unique()
                ->sort()
                ->values();

            // Bloquear artículos en orden consistente.
            foreach ($idsArticulos as $idArticulo) {

                DB::table('articulos')
                    ->where('id', $idArticulo)
                    ->lockForUpdate()
                    ->first();
            }

            foreach ($detalles as $detalle) {

                $cantidad = (int) $detalle->cantidad_traspaso;

                if ($cantidad <= 0) {
                    throw new \Exception(
                        'Cantidad inválida en el detalle de traspaso.'
                    );
                }

                // Identificar el producto desde el inventario destino.
                $inventarioReferencia = Inventario::findOrFail(
                    $detalle->idinventario
                );

                $idarticulo = (int) $inventarioReferencia->idarticulo;

                // Bloquear inventarios de ambos almacenes.
                $inventariosBloqueados = Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->whereIn('idalmacen', [
                        $idAlmacenOrigen,
                        $idAlmacenDestino
                    ])
                    ->orderBy('idalmacen')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                // Stock total antes de la anulación.
                $stockAnteriorOrigen = (int) $inventariosBloqueados
                    ->where('idalmacen', $idAlmacenOrigen)
                    ->sum('saldo_stock');

                $stockAnteriorDestino = (int) $inventariosBloqueados
                    ->where('idalmacen', $idAlmacenDestino)
                    ->sum('saldo_stock');

                // ==========================================
                // 1. OBTENER INVENTARIO DESTINO
                // ==========================================

                $inventarioDestino = $inventariosBloqueados
                    ->firstWhere('id', $detalle->idinventario);

                if (
                    !$inventarioDestino ||
                    (int) $inventarioDestino->idalmacen !== $idAlmacenDestino
                ) {
                    throw new \Exception(
                        'No se encontró el inventario destino del artículo: '
                        . $idarticulo
                    );
                }

                // Validar stock disponible para la devolución.
                if ($inventarioDestino->saldo_stock < $cantidad) {
                    throw new \Exception(
                        'No se puede anular el traspaso. '
                        . 'Stock insuficiente en el almacén destino '
                        . 'para el artículo ID: ' . $idarticulo
                        . '. Disponible: ' . $inventarioDestino->saldo_stock
                        . '. Requerido: ' . $cantidad
                    );
                }

                // ==========================================
                // 2. DESCONTAR STOCK DEL DESTINO
                // ==========================================

                $inventarioDestino->saldo_stock -= $cantidad;
                $inventarioDestino->save();

                // ==========================================
                // 3. DEVOLVER STOCK AL ORIGEN
                // ==========================================

                $inventarioOrigen = Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idAlmacenOrigen)
                    ->where(
                        'fecha_vencimiento',
                        $inventarioDestino->fecha_vencimiento
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($inventarioOrigen) {

                    $inventarioOrigen->saldo_stock += $cantidad;

                } else {

                    // Si el inventario de origen ya no existe,
                    // volver a crearlo para devolver el stock.
                    $inventarioOrigen = new Inventario();
                    $inventarioOrigen->idarticulo = $idarticulo;
                    $inventarioOrigen->idalmacen = $idAlmacenOrigen;
                    $inventarioOrigen->saldo_stock = $cantidad;
                    $inventarioOrigen->cantidad = $cantidad;
                    $inventarioOrigen->fecha_vencimiento =
                        $inventarioDestino->fecha_vencimiento;
                }

                $inventarioOrigen->save();

                // ==========================================
                // 4. OBTENER STOCK DESPUÉS DE LA ANULACIÓN
                // ==========================================

                $stockActualOrigen = (int) Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idAlmacenOrigen)
                    ->lockForUpdate()
                    ->get()
                    ->sum('saldo_stock');

                $stockActualDestino = (int) Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idAlmacenDestino)
                    ->lockForUpdate()
                    ->get()
                    ->sum('saldo_stock');

                // Verificar consistencia de las cantidades.
                if (
                    ($stockActualOrigen - $stockAnteriorOrigen) !== $cantidad ||
                    ($stockAnteriorDestino - $stockActualDestino) !== $cantidad
                ) {
                    throw new \Exception(
                        'Inconsistencia al anular el movimiento del artículo: '
                        . $idarticulo
                    );
                }

                // ==========================================
                // 5. REGISTRAR SALIDA DEL ALMACÉN DESTINO
                // ==========================================

                MovimientoInventarioService::registrar(
                    $idarticulo,
                    $idAlmacenDestino,
                    MovimientoInventario::ANULACION_TRASPASO_ENTRADA,
                    $stockAnteriorDestino,
                    $stockActualDestino,
                    'TRASPASO',
                    $traspaso->id,
                    $detalle->id,
                    'Anulación de traspaso. Devolución hacia almacén ID: '
                    . $idAlmacenOrigen
                );

                // ==========================================
                // 6. REGISTRAR ENTRADA AL ALMACÉN ORIGEN
                // ==========================================

                MovimientoInventarioService::registrar(
                    $idarticulo,
                    $idAlmacenOrigen,
                    MovimientoInventario::ANULACION_TRASPASO_SALIDA,
                    $stockAnteriorOrigen,
                    $stockActualOrigen,
                    'TRASPASO',
                    $traspaso->id,
                    $detalle->id,
                    'Anulación de traspaso. Devolución desde almacén ID: '
                    . $idAlmacenDestino
                );
            }

            // ==========================================
            // 7. MARCAR TRASPASO COMO ANULADO
            // ==========================================

            $traspaso->estado = 0;
            $traspaso->save();

            DB::commit();

            return response()->json([
                'message' => 'Traspaso anulado correctamente'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error al anular traspaso', [
                'idtraspaso' => $id,
                'exception' => $e->getMessage()
            ]);

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
                'detalle_traspasos.cantidad_traspaso as cantidad',
                'detalle_traspasos.stock_actual_destino as stock_actual_destino'
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
        $pdf->Cell(105, 8, 'PRODUCTO', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'STOCK DESTINO', 1, 0, 'C', true);
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
            $pdf->Cell(105, 7, $nombreProducto, 1, 0, 'L');
            $pdf->Cell(30, 7, number_format($det->stock_actual_destino, 0), 1, 0, 'C');
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
