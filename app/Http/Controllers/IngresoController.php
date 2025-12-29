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
                "articulos.nombre as articulo"
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
                "articulos.unidad_envase as unidad_x_paquete"
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

        try {
            DB::beginTransaction();

            // 🔹 Caja (opcional)
            $ultimaCaja = Caja::latest()->first();

            // =====================
            // 1️⃣ REGISTRAR INGRESO
            // =====================
            $ingreso = new Ingreso();
            $ingreso->idusuario = auth()->id();
            $ingreso->tipo_comprobante = $request->tipo_comprobante;
            $ingreso->serie_comprobante = $request->serie_comprobante;
            $ingreso->num_comprobante = $request->num_comprobante;
            $ingreso->fecha_hora = now()->setTimezone("America/La_Paz");
            $ingreso->impuesto = $request->impuesto;
            $ingreso->total = $request->total;
            $ingreso->idalmacen = $request->idalmacen;
            $ingreso->estado = 1;
            $ingreso->idcaja = $ultimaCaja ? $ultimaCaja->id : null;
            $ingreso->descuento_global = 0;
            $ingreso->save();

            // 🔹 Actualizar caja
            if ($ultimaCaja) {
                $ultimaCaja->comprasContado += $request->total;
                $ultimaCaja->save();
            }

            // =====================
            // 2️⃣ DETALLE + INVENTARIO
            // =====================
            foreach ($request->data as $det) {

                // 🔹 Guardar detalle ingreso
                DetalleIngreso::create([
                    'idingreso' => $ingreso->id,
                    'idarticulo' => $det['idarticulo'],
                    'cantidad' => $det['cantidad'],
                    'precio' => $det['precio'],
                    'descuento' => 0
                ]);

                // 🔹 Artículo
                $articulo = Articulo::findOrFail($det['idarticulo']);

                // 🔹 Cantidad real (cajas → unidades)
                $cantidadReal = $det['cantidad'] * $articulo->unidad_envase;

                // 🔹 Buscar inventario existente
                $inventario = Inventario::where('idarticulo', $det['idarticulo'])
                    ->where('idalmacen', $request->idalmacen)
                    ->whereDate('fecha_vencimiento', $det['fecha_vencimiento'])
                    ->first();

                if ($inventario) {
                    $inventario->saldo_stock += $cantidadReal;
                    $inventario->cantidad += $cantidadReal;
                    $inventario->save();
                } else {
                    Inventario::create([
                        'idalmacen' => $request->idalmacen,
                        'idarticulo' => $det['idarticulo'],
                        'fecha_vencimiento' => $det['fecha_vencimiento'],
                        'saldo_stock' => $cantidadReal,
                        'cantidad' => $cantidadReal
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'id' => $ingreso->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error registro ingreso", ['error' => $e->getMessage()]);

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

        DB::beginTransaction();

        try {

            // 1️⃣ Obtener ingreso
            $ingreso = Ingreso::findOrFail($request->id);

            // ⛔ Evitar doble anulación
            if ($ingreso->estado == 0) {
                return response()->json([
                    'error' => 'El ingreso ya se encuentra anulado.'
                ], 400);
            }

            // 2️⃣ Obtener detalles del ingreso
            $detalles = DetalleIngreso::where('idingreso', $ingreso->id)->get();

            foreach ($detalles as $detalle) {

                // 3️⃣ Obtener artículo
                $articulo = Articulo::find($detalle->idarticulo);
                if (!$articulo)
                    continue;

                $unidadEnvase = $articulo->unidad_envase ?? 1;

                // 4️⃣ Cantidad real que se ingresó al inventario
                $cantidadReal = $detalle->cantidad * $unidadEnvase;

                // 5️⃣ Revertir inventario
                DB::table('inventarios')
                    ->where('idalmacen', $ingreso->idalmacen)
                    ->where('idarticulo', $detalle->idarticulo)
                    ->decrement('saldo_stock', $cantidadReal);
            }

            // 6️⃣ Marcar ingreso como anulado
            $ingreso->estado = 0;
            $ingreso->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ingreso anulado y stock revertido correctamente.'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('Error al anular ingreso', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => 'Error al anular el ingreso.'
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

    $id = $request->id; // <--- Obtenemos el id del ingreso desde el body
    if (!$id) {
        return response()->json(['success' => false, 'message' => 'ID del ingreso no proporcionado'], 400);
    }

    try {
        DB::beginTransaction();

        $ingreso = Ingreso::findOrFail($id);

        // 1️⃣ Revertir inventario actual
        foreach ($ingreso->detallesIngreso as $detalle) {
            $articulo = Articulo::find($detalle->idarticulo);
            if ($articulo) {
                $cantidadReal = $detalle->cantidad * $articulo->unidad_envase;

                $inventario = Inventario::where('idarticulo', $detalle->idarticulo)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($inventario) {
                    $inventario->saldo_stock -= $cantidadReal;
                    $inventario->cantidad -= $cantidadReal;
                    $inventario->save();
                }
            }
        }

        // 2️⃣ Actualizar datos generales
        $ingreso->idalmacen = $request->idalmacen;
        $ingreso->tipo_comprobante = $request->tipo_comprobante;
        $ingreso->serie_comprobante = $request->serie_comprobante;
        $ingreso->num_comprobante = $request->num_comprobante;
        $ingreso->impuesto = $request->impuesto;
        $ingreso->total = $request->total;
        $ingreso->save();

        // 3️⃣ Eliminar detalles anteriores
        $ingreso->detallesIngreso()->delete();

        // 4️⃣ Crear nuevos detalles y actualizar inventario
        foreach ($request->data as $det) {
            $detalle = new DetalleIngreso();
            $detalle->idingreso = $ingreso->id;
            $detalle->idarticulo = $det['idarticulo'];
            $detalle->cantidad = $det['cantidad'];
            $detalle->precio = $det['precio'];
            $detalle->descuento = $det['descuento'] ?? 0;
            $detalle->save();

            $articulo = Articulo::find($det['idarticulo']);
            if ($articulo) {
                $cantidadReal = $det['cantidad'] * $articulo->unidad_envase;

                $inventario = Inventario::firstOrCreate(
                    [
                        'idarticulo' => $det['idarticulo'],
                        'idalmacen' => $request->idalmacen,
                        'fecha_vencimiento' => '2099-12-31' // Fecha por defecto si no se maneja vencimiento,
                    ],
                    ['saldo_stock' => 0, 'cantidad' => 0]
                );

                $inventario->saldo_stock += $cantidadReal;
                $inventario->cantidad += $cantidadReal;
                $inventario->save();
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Compra actualizada correctamente'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Error al actualizar ingreso", ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}



}
