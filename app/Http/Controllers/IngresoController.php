<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Response;

use Barryvdh\DomPDF\Facade as PDF;
use FPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Caja;
use App\Ingreso;
use App\Articulo;
use App\DetalleIngreso;
use App\IngresoCuota;
use App\User;
use App\Notifications\NotifyAdmin;
use Exception;
use App\Inventario;
use App\MovimientoInventario;
use App\Services\MovimientoInventarioService;

class IngresoController extends Controller
{
    public function generarNotaIngreso($idIngreso)
    {
        $ingreso = Ingreso::join(
            "personas",
            "ingresos.idproveedor",
            "=",
            "personas.id"
        )
            ->join("users", "ingresos.idusuario", "=", "users.id")
            ->select(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.created_at",
                "ingresos.impuesto",
                "ingresos.total",
                "ingresos.estado",
                "personas.nombre",
                "personas.tipo_documento",
                "personas.num_documento",
                "personas.direccion",
                "personas.email",
                "personas.telefono",
                "users.usuario"
            )
            ->where("ingresos.id", "=", $idIngreso)
            ->orderBy("ingresos.id", "desc")
            ->first();

        $detalles = DetalleIngreso::join(
            "articulos",
            "detalle_ingresos.idarticulo",
            "=",
            "articulos.id"
        )
            ->select(
                "detalle_ingresos.cantidad",
                "detalle_ingresos.precio",
                "detalle_ingresos.tipo_compra",
                "articulos.nombre as articulo",
                "articulos.unidad_envase as unidad_x_paquete"
            )
            ->where("detalle_ingresos.idingreso", "=", $idIngreso)
            ->orderBy("detalle_ingresos.id", "desc")
            ->get();

        $pdf = PDF::loadView("pdf.nota_ingreso", [
            "ingreso" => $ingreso,
            "detalles" => $detalles,
        ]);

        return $pdf->download("nota_ingreso.pdf");
    }
    public function generarPdfBoleta($idIngreso)
    {
        $ingreso = Ingreso::join(
            "personas",
            "ingresos.idproveedor",
            "=",
            "personas.id"
        )
            ->join("users", "ingresos.idusuario", "=", "users.id")
            ->select(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.created_at",
                "ingresos.impuesto",
                "ingresos.total",
                "ingresos.estado",
                "personas.nombre",
                "personas.tipo_documento",
                "personas.num_documento",
                "personas.direccion",
                "personas.email",
                "personas.telefono",
                "users.usuario"
            )
            ->where("ingresos.id", "=", $idIngreso)
            ->orderBy("ingresos.id", "desc")
            ->first();

        $detalles = DetalleIngreso::join(
            "articulos",
            "detalle_ingresos.idarticulo",
            "=",
            "articulos.id"
        )
            ->select(
                "detalle_ingresos.cantidad",
                "detalle_ingresos.precio",
                "articulos.nombre as articulo"
            )
            ->where("detalle_ingresos.idingreso", "=", $idIngreso)
            ->orderBy("detalle_ingresos.id", "desc")
            ->get();

        $pdf = PDF::loadView("pdf.boleta", [
            "ingreso" => $ingreso,
            "detalles" => $detalles,
        ]);

        return $pdf->download("venta.pdf"); // Devuelve el PDF
    }

    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $buscar = $request->buscar;

        $usuario = \Auth::user();
        $idrol = $usuario->idrol;
        $idsucursal = $usuario->idsucursal;

        $query = Ingreso::join("users", "ingresos.idusuario", "=", "users.id")
            ->join("sucursales", "users.idsucursal", "=", "sucursales.id")
            ->join("almacens", "ingresos.idalmacen", "=", "almacens.id")
            ->select(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.fecha_hora",
                "ingresos.impuesto",
                "ingresos.total",
                "ingresos.estado",
                "users.usuario",
                "sucursales.nombre as nombre_sucursal",
                "almacens.nombre_almacen as nombre_almacen",
                "descuento_global"
            );

        // Filtro por rol
        if ($idrol == 1) {
            $query->where('users.idsucursal', $idsucursal);
        }

        // Búsqueda global en múltiples campos
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where("ingresos.tipo_comprobante", "like", "%" . $buscar . "%")
                    ->orWhere("ingresos.serie_comprobante", "like", "%" . $buscar . "%")
                    ->orWhere("ingresos.num_comprobante", "like", "%" . $buscar . "%")
                    ->orWhere("ingresos.fecha_hora", "like", "%" . $buscar . "%")
                    ->orWhere("ingresos.total", "like", "%" . $buscar . "%")
                    ->orWhere("users.usuario", "like", "%" . $buscar . "%")
                    ->orWhere("sucursales.nombre", "like", "%" . $buscar . "%")
                    ->orWhere("almacens.nombre_almacen", "like", "%" . $buscar . "%");
            });
            $perPage = 11;
        } else {
            $perPage = 11;
        }

        $ingresos = $query->orderBy("ingresos.id", "desc")->paginate($perPage);

        return [
            "pagination" => [
                "total" => $ingresos->total(),
                "current_page" => $ingresos->currentPage(),
                "per_page" => $ingresos->perPage(),
                "last_page" => $ingresos->lastPage(),
                "from" => $ingresos->firstItem(),
                "to" => $ingresos->lastItem(),
            ],
            "ingresos" => $ingresos,
            'idrol' => \Auth::user()->idrol
        ];
    }


    public function obtenerCabecera(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $id = $request->id;

        $ingreso = Ingreso::join("users", "ingresos.idusuario", "=", "users.id")
            ->select(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.fecha_hora",
                "ingresos.impuesto",
                "ingresos.total",
                "ingresos.estado",
                "users.usuario"
            )
            ->where("ingresos.id", "=", $id)
            ->orderBy("ingresos.id", "desc")
            ->take(1)
            ->get();

        return ["ingreso" => $ingreso];
    }

    public function obtenerDetalles(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $id = $request->id;

        $detalles = DetalleIngreso::join(
            "articulos",
            "detalle_ingresos.idarticulo",
            "=",
            "articulos.id"
        )
            ->select(
                "articulos.codigo",
                "detalle_ingresos.cantidad",
                "detalle_ingresos.precio",
                "articulos.nombre as articulo",
                "articulos.unidad_envase as unidad_x_paquete",
                DB::raw("COALESCE(detalle_ingresos.tipo_compra, 'Caja') as tipo_compra")
            )
            ->where("detalle_ingresos.idingreso", "=", $id)
            ->orderBy("detalle_ingresos.id", "desc")
            ->get();

        return ["detalles" => $detalles];
    }

    public function registrarIngreso(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        try {
            DB::beginTransaction();

            $ultimaCaja = Caja::latest()->first();

            if (!$ultimaCaja || $ultimaCaja->estado != "1") {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "Debe tener una caja abierta",
                    ],
                    400
                );
            }

            if (!isset($request->form) || !is_array($request->form)) {
                throw new \Exception("Los datos del formulario no son válidos");
            }

            $ingreso = new Ingreso();
            $ingreso->idproveedor =
                $request->form["proveedorSeleccionado"]["id"] ?? null;
            $ingreso->idusuario = $request->usuario_actual_id ?? auth()->id();
            $ingreso->tipo_comprobante =
                $request->form["tipo_comprobante"]["nombre"] ??
                "No especificado";
            $ingreso->serie_comprobante =
                $request->form["serie_comprobante"] ?? "No especificado";
            $ingreso->num_comprobante =
                $request->form["num_comprobante"] ?? "No especificado";
            $ingreso->fecha_hora = now();
            $ingreso->impuesto = 0;
            $ingreso->total = $request->saldoTotalCompra ?? 0;
            $ingreso->tipoCompra = $request->tipoCompra["id"] ?? null;
            $ingreso->frecuencia_cuotas =
                $request->form_cuotas["frecuencia_pagos"] ?? 0;
            $ingreso->estado = true;
            $ingreso->idalmacen = $request->almacenSeleccionado["id"] ?? null;
            $ingreso->idcaja = $ultimaCaja->id;
            $ingreso->descuento_global = $request->descuento_global ?? 0;
            $ingreso->num_cuotas = $request->form_cuotas["num_cuotas"] ?? 0;
            $ingreso->cuota_inicial =
                $request->form_cuotas["cuota_inicial"] ?? 0;
            $ingreso->tipo_pago_cuota =
                $request->form_cuotas["tipoPagoCuotaSeleccionado"]["nombre"] ??
                "Ninguna";
            $ingreso->save();

            if ($ingreso->tipoCompra == 2) {
                if (!is_array($request->cuotaData)) {
                    throw new \Exception("Las cuotas no son un array válido");
                }

                foreach ($request->cuotaData as $cuotaObj) {
                    if (!is_array($cuotaObj)) {
                        throw new \Exception(
                            "Una de las cuotas no es un array válido"
                        );
                    }

                    $cuota = new IngresoCuota();
                    $cuota->idingreso = $ingreso->id;
                    $cuota->fecha_pago = $cuotaObj["fecha_pago"] ?? null;
                    $cuota->precio_cuota = $cuotaObj["precio_cuota"] ?? 0;
                    $cuota->total_cancelado = $cuotaObj["total_cancelado"] ?? 0;
                    $cuota->saldo_restante = $cuotaObj["saldo_restante"] ?? 0;
                    $cuota->fecha_cancelado =
                        $cuotaObj["fecha_cancelado"] ?? null;
                    $cuota->estado = $cuotaObj["estado"] ?? "Pendiente";
                    $cuota->save();
                }

                $ultimaCaja->comprasCredito += $ingreso->total;
            } else {
                $ultimaCaja->comprasContado += $ingreso->total;
            }
            $ultimaCaja->save();

            if (!is_array($request->array_articulos_completo)) {
                throw new \Exception("El array de artículos no es válido");
            }

            foreach ($request->array_articulos_completo as $articulo) {
                if (!is_array($articulo)) {
                    throw new \Exception(
                        "Uno de los artículos no es un array válido"
                    );
                }

                $detalle = new DetalleIngreso();
                $detalle->idingreso = $ingreso->id;
                $detalle->idarticulo = $articulo["id"] ?? null;
                $detalle->cantidad = $articulo["unidadesTotales"] ?? 0;
                $detalle->descuento = $articulo["descuento"] ?? 0;
                $detalle->precio = $articulo["subtotal"] ?? 0;
                $detalle->save();
            }

            DB::commit();

            return response()->json(
                [
                    "status" => "success",
                    "message" => "Compra registrada con exito",
                    "ingreso" => $ingreso,
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "status" => "error",
                    "message" =>
                        "Error al registrar la compra: " . $e->getMessage(),
                    "line" => $e->getLine(),
                    "file" => $e->getFile(),
                ],
                500
            );
        }
    }

    public function store(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $request->validate([
            'idalmacen' => 'required|integer|exists:almacens,id',
            'data' => 'required|array|min:1',
            'data.*.idarticulo' => 'required|integer|exists:articulos,id',
            'data.*.cantidad' => 'required|integer|min:1',
            'data.*.precio' => 'required|numeric|min:0',
            'data.*.fecha_vencimiento' => 'required|date',
            'total' => 'required|numeric|min:0'
        ]);

        try {

            DB::beginTransaction();

            $idalmacen = (int) $request->idalmacen;

            // Fecha efectiva de la operación.
            $fechaMovimiento = now()->setTimezone("America/La_Paz");

            // ==========================================
            // 1. OBTENER CAJA
            // ==========================================

            $ultimaCaja = Caja::latest()
                ->lockForUpdate()
                ->first();

            // ==========================================
            // 2. REGISTRAR INGRESO
            // ==========================================

            $ingreso = new Ingreso();
            $ingreso->idusuario = auth()->id();
            $ingreso->tipo_comprobante = $request->tipo_comprobante;
            $ingreso->serie_comprobante = $request->serie_comprobante;
            $ingreso->num_comprobante = $request->num_comprobante;
            $ingreso->fecha_hora = $fechaMovimiento;
            $ingreso->impuesto = $request->impuesto;
            $ingreso->total = $request->total;
            $ingreso->idalmacen = $idalmacen;
            $ingreso->estado = 1;
            $ingreso->idcaja = $ultimaCaja ? $ultimaCaja->id : null;
            $ingreso->descuento_global = 0;
            $ingreso->save();

            // Actualizar caja.
            if ($ultimaCaja) {
                $ultimaCaja->comprasContado += $request->total;
                $ultimaCaja->save();
            }

            // ==========================================
            // 3. BLOQUEAR ARTÍCULOS
            // ==========================================

            $idsArticulos = collect($request->data)
                ->pluck('idarticulo')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->unique()
                ->sort()
                ->values();

            foreach ($idsArticulos as $idArticulo) {

                Articulo::where('id', $idArticulo)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // ==========================================
            // 4. REGISTRAR DETALLES E INVENTARIO
            // ==========================================

            foreach ($request->data as $det) {

                $idarticulo = (int) $det['idarticulo'];
                $cantidad = (int) $det['cantidad'];

                // Determinar tipo de compra.
                $tipoCompra = !empty($det['es_paquete'])
                    ? 'Caja'
                    : 'Unidad';

                // Obtener artículo.
                $articulo = Articulo::where('id', $idarticulo)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Validar unidades por caja.
                if ($tipoCompra === 'Caja') {

                    $unidadEnvase = (int) $articulo->unidad_envase;

                    if (
                        $unidadEnvase <= 0 ||
                        $unidadEnvase != $articulo->unidad_envase
                    ) {
                        throw new \Exception(
                            'El artículo ID: ' . $idarticulo
                            . ' no tiene una cantidad válida de unidades por caja.'
                        );
                    }
                }

                // Calcular cantidad real en unidades.
                $cantidadReal = $tipoCompra === 'Caja'
                    ? $cantidad * $unidadEnvase
                    : $cantidad;

                // ==========================================
                // 5. OBTENER STOCK ANTERIOR
                // ==========================================

                $inventariosBloqueados = Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idalmacen)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $stockAnterior = (int) $inventariosBloqueados
                    ->sum('saldo_stock');

                // ==========================================
                // 6. REGISTRAR DETALLE DE INGRESO
                // ==========================================

                $detalleIngreso = DetalleIngreso::create([
                    'idingreso' => $ingreso->id,
                    'idarticulo' => $idarticulo,
                    'cantidad' => $cantidad,
                    'precio' => $det['precio'],
                    'descuento' => 0,
                    'tipo_compra' => $tipoCompra
                ]);

                // ==========================================
                // 7. ACTUALIZAR INVENTARIO
                // ==========================================

                $inventario = Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idalmacen)
                    ->whereDate(
                        'fecha_vencimiento',
                        $det['fecha_vencimiento']
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($inventario) {

                    $inventario->saldo_stock += $cantidadReal;
                    $inventario->cantidad += $cantidadReal;
                    $inventario->save();

                } else {

                    $inventario = Inventario::create([
                        'idalmacen' => $idalmacen,
                        'idarticulo' => $idarticulo,
                        'fecha_vencimiento' => $det['fecha_vencimiento'],
                        'saldo_stock' => $cantidadReal,
                        'cantidad' => $cantidadReal
                    ]);
                }

                // ==========================================
                // 8. OBTENER STOCK ACTUAL
                // ==========================================

                $stockActual = (int) Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idalmacen)
                    ->lockForUpdate()
                    ->get()
                    ->sum('saldo_stock');

                // Validar consistencia del movimiento.
                if (($stockActual - $stockAnterior) !== $cantidadReal) {

                    throw new \Exception(
                        'Inconsistencia en el ingreso de inventario '
                        . 'del artículo ID: ' . $idarticulo
                    );
                }

                // ==========================================
                // 9. REGISTRAR MOVIMIENTO HISTÓRICO
                // ==========================================

                $observacion = 'Ingreso de productos por compra. '
                    . 'Tipo: ' . $tipoCompra
                    . '. Cantidad: ' . $cantidad
                    . '. Unidades ingresadas: ' . $cantidadReal;

                MovimientoInventarioService::registrar(
                    $idarticulo,
                    $idalmacen,
                    MovimientoInventario::COMPRA,
                    $stockAnterior,
                    $stockActual,
                    'INGRESO',
                    $ingreso->id,
                    $detalleIngreso->id,
                    $observacion,
                    $ingreso->idusuario,
                    $fechaMovimiento
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'id' => $ingreso->id
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error("Error registro ingreso", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la compra'
            ], 500);
        }
    }


    public function desactivar(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $request->validate([
            'id' => 'required|integer|exists:ingresos,id'
        ]);

        try {

            DB::beginTransaction();

            // ==========================================
            // 1. OBTENER Y BLOQUEAR INGRESO
            // ==========================================

            $ingreso = Ingreso::where('id', $request->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Evitar doble anulación.
            if ((int) $ingreso->estado === 0) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'error' => 'El ingreso ya se encuentra anulado.'
                ], 400);
            }

            if ((int) $ingreso->estado !== 1) {
                throw new \Exception(
                    'El ingreso no se encuentra activo.'
                );
            }

            $idalmacen = (int) $ingreso->idalmacen;

            // ==========================================
            // 2. OBTENER DETALLES DEL INGRESO
            // ==========================================

            $detalles = DetalleIngreso::where(
                'idingreso',
                $ingreso->id
            )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($detalles->isEmpty()) {
                throw new \Exception(
                    'El ingreso no contiene detalles.'
                );
            }

            // ==========================================
            // 3. BLOQUEAR CAJA ORIGINAL
            // ==========================================

            $caja = null;

            if ($ingreso->idcaja) {

                $caja = Caja::where('id', $ingreso->idcaja)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // ==========================================
            // 4. BLOQUEAR ARTÍCULOS
            // ==========================================

            $idsArticulos = $detalles
                ->pluck('idarticulo')
                ->map(function ($idArticulo) {
                    return (int) $idArticulo;
                })
                ->unique()
                ->sort()
                ->values();

            foreach ($idsArticulos as $idArticulo) {

                Articulo::where('id', $idArticulo)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $fechaMovimiento = now()->setTimezone("America/La_Paz");

            // ==========================================
            // 5. REVERTIR INVENTARIO
            // ==========================================

            foreach ($detalles as $detalle) {

                $idarticulo = (int) $detalle->idarticulo;

                // Recuperar cantidad real ingresada.
                // Compatible con compras nuevas, actualizadas
                // y anteriores a la implementación del historial.
                $cantidadReal = $this->obtenerCantidadRealIngreso(
                    $detalle,
                    $ingreso
                );

                // Obtener inventarios del almacén de la compra.
                $inventarios = Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idalmacen)
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->get();

                // Stock total anterior.
                $stockAnterior = (int) $inventarios
                    ->sum('saldo_stock');

                // Validar disponibilidad de stock.
                if ($stockAnterior < $cantidadReal) {

                    throw new \Exception(
                        'No existe suficiente stock para anular la compra '
                        . 'del artículo ID: ' . $idarticulo
                        . '. Disponible: ' . $stockAnterior
                        . '. Requerido: ' . $cantidadReal
                    );
                }

                $cantidadRestante = $cantidadReal;

                // Distribuir el descuento entre los inventarios.
                foreach ($inventarios as $inventario) {

                    if ($cantidadRestante <= 0) {
                        break;
                    }

                    if ($inventario->saldo_stock <= 0) {
                        continue;
                    }

                    $cantidadADescontar = min(
                        $cantidadRestante,
                        (int) $inventario->saldo_stock
                    );

                    // Revertir saldo de inventario.
                    $inventario->saldo_stock -= $cantidadADescontar;

                    $inventario->save();

                    $cantidadRestante -= $cantidadADescontar;
                }

                // Verificar que se descontó toda la cantidad.
                if ($cantidadRestante > 0) {

                    throw new \Exception(
                        'No se pudo revertir completamente el inventario '
                        . 'del artículo ID: ' . $idarticulo
                    );
                }

                // ==========================================
                // 6. OBTENER STOCK POSTERIOR
                // ==========================================

                $stockActual = (int) Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idalmacen)
                    ->lockForUpdate()
                    ->get()
                    ->sum('saldo_stock');

                // Validar consistencia del movimiento.
                if (($stockAnterior - $stockActual) !== $cantidadReal) {

                    throw new \Exception(
                        'Inconsistencia al anular el inventario '
                        . 'del artículo ID: ' . $idarticulo
                    );
                }

                // ==========================================
                // 7. REGISTRAR MOVIMIENTO HISTÓRICO
                // ==========================================

                MovimientoInventarioService::registrar(
                    $idarticulo,
                    $idalmacen,
                    MovimientoInventario::ANULACION_COMPRA,
                    $stockAnterior,
                    $stockActual,
                    'INGRESO',
                    $ingreso->id,
                    $detalle->id,
                    'Reversión de inventario por anulación de compra. '
                    . 'Unidades revertidas: ' . $cantidadReal,
                    auth()->id(),
                    $fechaMovimiento
                );
            }

            // ==========================================
            // 8. REVERTIR MONTO DE COMPRA EN CAJA
            // ==========================================

            if ($caja) {

                $caja->comprasContado = round(
                    (float) $caja->comprasContado -
                    (float) $ingreso->total,
                    2
                );

                $caja->save();
            }

            // ==========================================
            // 9. MARCAR INGRESO COMO ANULADO
            // ==========================================

            $ingreso->estado = 0;
            $ingreso->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ingreso anulado y stock revertido correctamente.'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error('Error al anular ingreso', [
                'idingreso' => $request->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function PDFNotaCompra($id)
    {
        try {

            // Obtener cabecera
            $ingreso = Ingreso::join("users", "ingresos.idusuario", "=", "users.id")
                ->select(
                    "ingresos.id",
                    "ingresos.tipo_comprobante",
                    "ingresos.serie_comprobante",
                    "ingresos.num_comprobante",
                    "ingresos.fecha_hora",
                    "ingresos.impuesto",
                    "ingresos.total",
                    "ingresos.estado",
                    "users.usuario"
                )
                ->where("ingresos.id", $id)
                ->first();

            if (!$ingreso) {
                return "Ingreso no encontrado";
            }

            // Obtener detalles
            $detalles = DetalleIngreso::join("articulos", "detalle_ingresos.idarticulo", "=", "articulos.id")
                ->select(
                    "detalle_ingresos.cantidad",
                    "detalle_ingresos.precio",
                    "detalle_ingresos.tipo_compra",
                    "articulos.nombre as articulo",
                    "articulos.unidad_envase as unidad_x_paquete",
                    "articulos.codigo"
                )
                ->where("detalle_ingresos.idingreso", $id)
                ->orderBy("detalle_ingresos.id", "desc")
                ->get();

            // Enviar datos al PDF
            $data = [
                'ingreso' => $ingreso,
                'detalles' => $detalles
            ];

            $pdf = \PDF::loadView('pdf.nota-compra', $data)
                ->setPaper('A4', 'portrait');

            return $pdf->stream("nota_compra_{$id}.pdf");

        } catch (\Exception $e) {
            return "Error generando PDF: " . $e->getMessage();
        }
    }

    public function obtenerCompleto($id)
    {
        $ingreso = Ingreso::with('proveedor')
            ->where('id', $id)
            ->firstOrFail();

        $detalles = DB::table('detalle_ingresos as di')
            ->join('articulos as a', 'di.idarticulo', '=', 'a.id')
            ->select(
                'di.id',
                'di.idarticulo',
                'a.nombre as articulo',
                'di.cantidad',
                'di.precio',
                'di.descuento',
                'a.unidad_envase'
            )
            ->where('di.idingreso', $id)
            ->get();

        return response()->json([
            'ingreso' => $ingreso,
            'detalles' => $detalles
        ]);
    }

    public function obtenerIngreso($id)
    {
        $ingreso = Ingreso::with('detallesIngreso.articulo') // trae detalles y datos del artículo
            ->findOrFail($id); // más simple que where+firstOrFail

        // Preparar datos para frontend
        return [
            'id' => $ingreso->id,
            'idproveedor' => $ingreso->idproveedor,
            'tipo_comprobante' => $ingreso->tipo_comprobante,
            'serie_comprobante' => $ingreso->serie_comprobante,
            'num_comprobante' => $ingreso->num_comprobante,
            'impuesto' => $ingreso->impuesto,
            'total' => $ingreso->total,
            'idalmacen' => $ingreso->idalmacen,
            'detalles' => $ingreso->detallesIngreso->map(function ($detalle) {
                return [
                    'id' => $detalle->id,
                    'idarticulo' => $detalle->idarticulo,
                    'articulo' => $detalle->articulo->nombre ?? '',
                    'cantidad' => $detalle->cantidad,
                    'precio' => $detalle->precio,
                    'descuento' => $detalle->descuento,
                    'unidad_x_paquete' => $detalle->articulo->unidad_envase ?? 1,
                    'codigo' => $detalle->articulo->codigo ?? '',

                    'precio_uno' => $detalle->articulo->precio_uno ?? $detalle->precio,
                    'precio_dos' => $detalle->articulo->precio_dos ?? $detalle->precio,
                ];
            }),
        ];
    }
    public function actualizar(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $request->validate([
            'id' => 'required|integer|exists:ingresos,id',
            'idalmacen' => 'required|integer|exists:almacens,id',
            'total' => 'required|numeric|min:0',
            'data' => 'required|array|min:1',
            'data.*.idarticulo' => 'required|integer|exists:articulos,id',
            'data.*.cantidad' => 'required|integer|min:1',
            'data.*.precio' => 'required|numeric|min:0',
            'data.*.descuento' => 'nullable|numeric|min:0',
            'data.*.fecha_vencimiento' => 'nullable|date'
        ]);

        try {

            DB::beginTransaction();

            $id = (int) $request->id;

            // ==========================================
            // 1. OBTENER Y BLOQUEAR INGRESO
            // ==========================================

            $ingreso = Ingreso::where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $ingreso->estado !== 1) {
                throw new \Exception(
                    'No se puede actualizar un ingreso que no está activo.'
                );
            }

            // Conservar datos anteriores.
            $idAlmacenAnterior = (int) $ingreso->idalmacen;
            $idAlmacenNuevo = (int) $request->idalmacen;
            $totalAnterior = $ingreso->total;

            // Obtener detalles anteriores.
            $detallesAnteriores = $ingreso->detallesIngreso()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // ==========================================
            // 2. BLOQUEAR CAJA ORIGINAL
            // ==========================================

            $caja = null;

            if ($ingreso->idcaja) {

                $caja = Caja::where('id', $ingreso->idcaja)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // ==========================================
            // 3. BLOQUEAR ARTÍCULOS INVOLUCRADOS
            // ==========================================

            $idsArticulos = $detallesAnteriores
                ->pluck('idarticulo')
                ->merge(collect($request->data)->pluck('idarticulo'))
                ->map(function ($idArticulo) {
                    return (int) $idArticulo;
                })
                ->unique()
                ->sort()
                ->values();

            foreach ($idsArticulos as $idArticulo) {

                Articulo::where('id', $idArticulo)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // Bloquear inventarios de ambos almacenes.
            Inventario::whereIn('idarticulo', $idsArticulos->all())
                ->whereIn('idalmacen', [
                    $idAlmacenAnterior,
                    $idAlmacenNuevo
                ])
                ->orderBy('idarticulo')
                ->orderBy('idalmacen')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $fechaMovimiento = now()->setTimezone("America/La_Paz");

            // ==========================================
            // 4. REVERTIR INVENTARIO ANTERIOR
            // ==========================================

            foreach ($detallesAnteriores as $detalleAnterior) {

                $idarticulo = (int) $detalleAnterior->idarticulo;

                // Recuperar cantidad real ingresada originalmente.
                $cantidadReal = $this->obtenerCantidadRealIngreso(
                    $detalleAnterior,
                    $ingreso
                );

                // Buscar inventarios del almacén ORIGINAL.
                $inventarios = Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idAlmacenAnterior)
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->get();

                $stockAnterior = (int) $inventarios->sum('saldo_stock');

                if ($stockAnterior < $cantidadReal) {

                    throw new \Exception(
                        'No existe suficiente stock para revertir la compra '
                        . 'del artículo ID: ' . $idarticulo
                        . '. Disponible: ' . $stockAnterior
                        . '. Requerido: ' . $cantidadReal
                    );
                }

                $cantidadRestante = $cantidadReal;

                // Descontar de los inventarios disponibles.
                foreach ($inventarios as $inventario) {

                    if ($cantidadRestante <= 0) {
                        break;
                    }

                    if ($inventario->saldo_stock <= 0) {
                        continue;
                    }

                    $cantidadADescontar = min(
                        $cantidadRestante,
                        (int) $inventario->saldo_stock
                    );

                    $inventario->saldo_stock -= $cantidadADescontar;
                    $inventario->cantidad -= $cantidadADescontar;
                    $inventario->save();

                    $cantidadRestante -= $cantidadADescontar;
                }

                if ($cantidadRestante > 0) {

                    throw new \Exception(
                        'No se pudo revertir completamente el inventario '
                        . 'del artículo ID: ' . $idarticulo
                    );
                }

                // Obtener stock posterior a la reversión.
                $stockActual = (int) Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idAlmacenAnterior)
                    ->lockForUpdate()
                    ->get()
                    ->sum('saldo_stock');

                // Validar consistencia.
                if (($stockAnterior - $stockActual) !== $cantidadReal) {

                    throw new \Exception(
                        'Inconsistencia al revertir el inventario '
                        . 'del artículo ID: ' . $idarticulo
                    );
                }

                // Registrar salida por actualización de compra.
                MovimientoInventarioService::registrar(
                    $idarticulo,
                    $idAlmacenAnterior,
                    MovimientoInventario::ACTUALIZACION_COMPRA_SALIDA,
                    $stockAnterior,
                    $stockActual,
                    'INGRESO',
                    $ingreso->id,
                    $detalleAnterior->id,
                    'Reversión del detalle anterior por actualización de compra',
                    auth()->id(),
                    $fechaMovimiento
                );
            }

            // ==========================================
            // 5. ACTUALIZAR DATOS GENERALES
            // ==========================================

            $ingreso->idalmacen = $idAlmacenNuevo;
            $ingreso->tipo_comprobante = $request->tipo_comprobante;
            $ingreso->serie_comprobante = $request->serie_comprobante;
            $ingreso->num_comprobante = $request->num_comprobante;
            $ingreso->impuesto = $request->impuesto;
            $ingreso->total = $request->total;
            $ingreso->save();

            // ==========================================
            // 6. ACTUALIZAR MONTO DE COMPRAS EN CAJA
            // ==========================================

            if ($caja) {

                $diferenciaTotal = round(
                    (float) $request->total - (float) $totalAnterior,
                    2
                );

                $caja->comprasContado = round(
                    (float) $caja->comprasContado + $diferenciaTotal,
                    2
                );

                $caja->save();
            }

            // ==========================================
            // 7. ELIMINAR DETALLES ANTERIORES
            // ==========================================

            $ingreso->detallesIngreso()->delete();

            // ==========================================
            // 8. REGISTRAR NUEVOS DETALLES
            // ==========================================

            foreach ($request->data as $det) {

                $idarticulo = (int) $det['idarticulo'];
                $cantidadReal = (int) $det['cantidad'];

                // Conservamos tu actualización por unidad.
                $tipoCompra = 'Unidad';

                $fechaVencimiento = !empty($det['fecha_vencimiento'])
                    ? $det['fecha_vencimiento']
                    : '2099-12-31';

                // Obtener stock anterior del nuevo almacén.
                $stockAnterior = (int) Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idAlmacenNuevo)
                    ->lockForUpdate()
                    ->get()
                    ->sum('saldo_stock');

                // Crear nuevo detalle.
                $detalle = new DetalleIngreso();
                $detalle->idingreso = $ingreso->id;
                $detalle->idarticulo = $idarticulo;
                $detalle->cantidad = $cantidadReal;
                $detalle->precio = $det['precio'];
                $detalle->descuento = $det['descuento'] ?? 0;
                $detalle->tipo_compra = $tipoCompra;
                $detalle->save();

                // Buscar inventario existente.
                $inventario = Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idAlmacenNuevo)
                    ->whereDate('fecha_vencimiento', $fechaVencimiento)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($inventario) {

                    $inventario->saldo_stock += $cantidadReal;
                    $inventario->cantidad += $cantidadReal;
                    $inventario->save();

                } else {

                    $inventario = Inventario::create([
                        'idarticulo' => $idarticulo,
                        'idalmacen' => $idAlmacenNuevo,
                        'fecha_vencimiento' => $fechaVencimiento,
                        'saldo_stock' => $cantidadReal,
                        'cantidad' => $cantidadReal
                    ]);
                }

                // Obtener stock posterior.
                $stockActual = (int) Inventario::where(
                    'idarticulo',
                    $idarticulo
                )
                    ->where('idalmacen', $idAlmacenNuevo)
                    ->lockForUpdate()
                    ->get()
                    ->sum('saldo_stock');

                // Validar consistencia.
                if (($stockActual - $stockAnterior) !== $cantidadReal) {

                    throw new \Exception(
                        'Inconsistencia al actualizar el inventario '
                        . 'del artículo ID: ' . $idarticulo
                    );
                }

                // Registrar entrada por actualización de compra.
                MovimientoInventarioService::registrar(
                    $idarticulo,
                    $idAlmacenNuevo,
                    MovimientoInventario::ACTUALIZACION_COMPRA_ENTRADA,
                    $stockAnterior,
                    $stockActual,
                    'INGRESO',
                    $ingreso->id,
                    $detalle->id,
                    'Ingreso del nuevo detalle por actualización de compra',
                    auth()->id(),
                    $fechaMovimiento
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra actualizada correctamente'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error("Error al actualizar ingreso", [
                'idingreso' => $request->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function obtenerCantidadRealIngreso($detalle, $ingreso)
    {
        // Buscar la cantidad real registrada en el historial.
        $movimientos = MovimientoInventario::where(
            'tipo_referencia',
            'INGRESO'
        )
            ->where('idreferencia', $ingreso->id)
            ->where('iddetalle_referencia', $detalle->id)
            ->where('idarticulo', $detalle->idarticulo)
            ->where('idalmacen', $ingreso->idalmacen)
            ->whereIn('tipo_movimiento', [
                MovimientoInventario::COMPRA,
                MovimientoInventario::ACTUALIZACION_COMPRA_ENTRADA
            ])
            ->get();

        if ($movimientos->count() > 1) {
            throw new \Exception(
                'Existen movimientos duplicados para el detalle de ingreso ID: '
                . $detalle->id
            );
        }

        if ($movimientos->isNotEmpty()) {

            $cantidad = (int) $movimientos->first()->cantidad;

            if ($cantidad <= 0) {
                throw new \Exception(
                    'Cantidad histórica inválida para el detalle ID: '
                    . $detalle->id
                );
            }

            return $cantidad;
        }

        // Compras anteriores a la implementación del historial.
        $articulo = Articulo::findOrFail($detalle->idarticulo);

        $cantidad = (int) $detalle->cantidad;

        if ($detalle->tipo_compra === 'Caja') {

            $unidadEnvase = (int) $articulo->unidad_envase;

            if (
                $unidadEnvase <= 0 ||
                $unidadEnvase != $articulo->unidad_envase
            ) {

                throw new \Exception(
                    'Unidades por caja inválidas para el artículo ID: '
                    . $articulo->id
                );
            }

            $cantidad *= $unidadEnvase;
        }

        if ($cantidad <= 0) {
            throw new \Exception(
                'Cantidad inválida para el detalle de ingreso ID: '
                . $detalle->id
            );
        }

        return $cantidad;
    }

    public function comprobantesDim()
    {
        $comprobantes = \DB::table('ingresos')
            ->select('num_comprobante')
            ->where('tipo_comprobante', 'DIM')
            ->distinct()
            ->orderBy('num_comprobante', 'desc')
            ->get();

        return $comprobantes;
    }
}
