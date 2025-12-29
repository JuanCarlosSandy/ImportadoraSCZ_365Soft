<?php

namespace App\Http\Controllers;

use App\Ingreso;
use App\IngresoCuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngresoCuotaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $ingresos = Ingreso::join(
            "personas as proveedor",
            "ingresos.idproveedor",
            "=",
            "proveedor.id"
        )
            ->join("users", "ingresos.idusuario", "=", "users.id")
            ->join("personas as usuario", "users.id", "=", "usuario.id")
            ->join("almacens", "ingresos.idalmacen", "=", "almacens.id")
            ->leftJoin("ingresos_cuotas", function ($join) {
                $join
                    ->on("ingresos.id", "=", "ingresos_cuotas.idingreso")
                    ->where("ingresos_cuotas.estado", "=", "Pendiente");
            })
            ->select(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.total",
                "ingresos.num_cuotas",
                "ingresos.frecuencia_cuotas",
                "ingresos.cuota_inicial",
                "ingresos.tipo_pago_cuota as tipo_pago_cuota_inicial",
                "ingresos.idalmacen",
                "ingresos.descuento_global",
                "almacens.nombre_almacen",
                "proveedor.nombre as proveedor",
                "usuario.nombre as usuario",
                DB::raw(
                    'CASE WHEN SUM(ingresos_cuotas.precio_cuota) IS NULL THEN "Pagado" ELSE "Pendiente" END as estado_pago'
                ),
                DB::raw(
                    "COALESCE(SUM(ingresos_cuotas.precio_cuota), 0) as saldo_restante"
                )
            )
            ->groupBy(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.total",
                "ingresos.num_cuotas",
                "ingresos.frecuencia_cuotas",
                "ingresos.cuota_inicial",
                "ingresos.tipo_pago_cuota",
                "ingresos.idalmacen",
                "ingresos.descuento_global",
                "almacens.nombre_almacen",
                "proveedor.nombre",
                "usuario.nombre"
            )
            ->orderBy("ingresos.id", "desc")
            ->where("ingresos.tipoCompra", "=", 2)
            ->get();

        $ingresos = $ingresos->map(function ($ingreso) {
            $ingreso->saldo_restante = number_format(
                $ingreso->saldo_restante,
                2,
                ".",
                ""
            );
            return $ingreso;
        });

        return response()->json(
            [
                "status" => "success",
                "message" => "Ingresos obtenidos con éxito",
                "ingresos" => $ingresos,
            ],
            200
        );
    }

    public function getCuotasByIngreso(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        try {
            $id = $request->id;
            $cuotas = IngresoCuota::where("idingreso", $id)
                ->select(
                    "id",
                    "fecha_pago",
                    "precio_cuota",
                    "total_cancelado",
                    "saldo_restante",
                    "fecha_cancelado",
                    "tipo_pago_cuota",
                    "estado"
                )
                ->get();

            return response()->json(
                [
                    "status" => "success",
                    "message" => "Cuotas obtenidas con exito",
                    "cuotas" => $cuotas,
                ],
                200
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    "status" => "error",
                    "message" => $e->getMessage(),
                    "line" => $e->getLine(),
                    "file" => $e->getFile(),
                ],
                500
            );
        }
    }

    public function pagarCuota(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        try {
            DB::beginTransaction();

            /*if (!isset($request->form) || !is_array($request->form)) {
                throw new \Exception("Los datos del formulario no son válidos");
            }*/

            $cuota = IngresoCuota::findOrFail($request->id);

            if ($cuota->estado === "Pagado") {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "Esta cuota ya ha sido pagada",
                    ],
                    400
                );
            }

            if ($request->form["pago_actual"] != $cuota->precio_cuota) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" =>
                            "El monto del pago debe ser igual al precio de la cuota",
                    ],
                    400
                );
            }

            $cuota->total_cancelado = $request->form["pago_actual"];
            $cuota->saldo_restante = 0;
            $cuota->fecha_cancelado = $request->form["fecha_pago"];
            $cuota->tipo_pago_cuota =
                $request->form["tipo_pago_cuota"]["nombre"];
            $cuota->estado = "Pagado";
            $cuota->save();

            $ingreso = Ingreso::findOrFail($cuota->idingreso);
            $todasCuotasPagadas =
                $ingreso->cuotas()->where("estado", "!=", "Pagado")->counr() ===
                0;

            if ($todasCuotasPagadas) {
                $ingreso->estado_pagado = "Pagado";
                $ingreso->save();
            }

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Cuota pagada exitosamente",
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(
                [
                    "status" => "error",
                    "message" =>
                        "Error al procesar el pago: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\IngresoCuota  $ingresoCuota
     * @return \Illuminate\Http\Response
     */
    public function show(IngresoCuota $ingresoCuota)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\IngresoCuota  $ingresoCuota
     * @return \Illuminate\Http\Response
     */
    public function edit(IngresoCuota $ingresoCuota)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\IngresoCuota  $ingresoCuota
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, IngresoCuota $ingresoCuota)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\IngresoCuota  $ingresoCuota
     * @return \Illuminate\Http\Response
     */
    public function destroy(IngresoCuota $ingresoCuota)
    {
        //
    }
}
