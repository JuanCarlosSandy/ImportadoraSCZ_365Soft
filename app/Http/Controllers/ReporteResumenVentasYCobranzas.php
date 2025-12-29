<?php

namespace App\Http\Controllers;

use App\DetalleVenta;
use App\TipoPago;
use App\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteResumenVentasYCobranzas extends Controller
{
    public function ventasYcobranzas(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $vendedorId = $request->input('vendedorId');

        if ($vendedorId === 'todos') {
            $vendedores = DB::table('users')->pluck('id');
        } else {
            $vendedores = [$vendedorId];
        }

        $resultados = [];

        foreach ($vendedores as $id) {
            $ventas = DB::table('tipo_pagos')
                ->leftJoin('ventas', function ($join) use ($id) {
                    $join->on('tipo_pagos.id', '=', 'ventas.idtipo_pago')
                        ->where('ventas.idusuario', '=', $id)
                        ->where('ventas.estado', '=', 'Registrado');
                })
                ->select(
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Efectivo" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_Efectivo'),
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Tarjeta" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_Tarjeta'),
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Transaccion QR" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_TransaccionQR'),
                    DB::raw('COALESCE(SUM(ventas.total), 0) AS total_ventas')
                )->first();

            $cobranzas = DB::table('tipo_pagos')
                ->leftJoin('ventas', function ($join) use ($id) {
                    $join->on('tipo_pagos.id', '=', 'ventas.idtipo_pago')
                        ->where('ventas.idusuario', '=', $id)
                        ->where('ventas.estado', '=', 'Pendiente');
                })
                ->select(
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Efectivo" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_Efectivo'),
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Tarjeta" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_Tarjeta'),
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Transaccion QR" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_TransaccionQR'),
                    DB::raw('COALESCE(SUM(ventas.total), 0) AS total_cobranzas')
                )->first();

            $resultados[] = [
                'vendedorId' => $id,
                'ventas' => [
                    'tipo_pago_Efectivo' => $ventas->tipo_pago_Efectivo,
                    'tipo_pago_Tarjeta' => $ventas->tipo_pago_Tarjeta,
                    'tipo_pago_TransaccionQR' => $ventas->tipo_pago_TransaccionQR,
                    'total_ventas' => $ventas->total_ventas,
                ],
                'cobranzas' => [
                    'tipo_pago_Efectivo' => $cobranzas->tipo_pago_Efectivo,
                    'tipo_pago_Tarjeta' => $cobranzas->tipo_pago_Tarjeta,
                    'tipo_pago_TransaccionQR' => $cobranzas->tipo_pago_TransaccionQR,
                    'total_cobranzas' => $cobranzas->total_cobranzas,
                ]
            ];
        }

        return $resultados;
    }





    public function ventasYcobranzas2(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $vendedorId = $request->input('vendedorId');

        // Si el vendedorId es 'todos', obtenemos todos los vendedores
        if ($vendedorId === 'todos') {
            $vendedores = DB::table('users')->pluck('id');
        } else {
            $vendedores = [$vendedorId];
        }

        $resultados = [];

        foreach ($vendedores as $id) {
            $vendedor = DB::table('users')->where('id', $id)->first();

            $ventas = DB::table('tipo_pagos')
                ->leftJoin('ventas', function ($join) use ($id) {
                    $join->on('tipo_pagos.id', '=', 'ventas.idtipo_pago')
                        ->where('ventas.idusuario', '=', $id)
                        ->where('ventas.estado', '=', 'Registrado');
                })
                ->select(
                    'users.nombre as nombre_vendedor',
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Efectivo" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_Efectivo'),
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Tarjeta" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_Tarjeta'),
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Transaccion QR" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_TransaccionQR'),
                    DB::raw('COALESCE(SUM(ventas.total), 0) AS total_ventas')
                )->first();

            $cobranzas = DB::table('tipo_pagos')
                ->leftJoin('ventas', function ($join) use ($id) {
                    $join->on('tipo_pagos.id', '=', 'ventas.idtipo_pago')
                        ->where('ventas.idusuario', '=', $id)
                        ->where('ventas.estado', '=', 'Pendiente');
                })
                ->select(
                    'users.nombre as nombre_vendedor',
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Efectivo" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_Efectivo'),
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Tarjeta" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_Tarjeta'),
                    DB::raw('IFNULL(SUM(CASE WHEN tipo_pagos.nombre_tipo_pago = "Transaccion QR" THEN ventas.total ELSE 0 END), 0) AS tipo_pago_TransaccionQR'),
                    DB::raw('COALESCE(SUM(ventas.total), 0) AS total_cobranzas')
                )->first();

            $resultados[] = [
                'vendedorId' => $id,
                'nombre_vendedor' => $vendedor->nombre,
                'ventas' => [
                    'tipo_pago_Efectivo' => $ventas->tipo_pago_Efectivo,
                    'tipo_pago_Tarjeta' => $ventas->tipo_pago_Tarjeta,
                    'tipo_pago_TransaccionQR' => $ventas->tipo_pago_TransaccionQR,
                    'total_ventas' => $ventas->total_ventas,
                ],
                'cobranzas' => [
                    'tipo_pago_Efectivo' => $cobranzas->tipo_pago_Efectivo,
                    'tipo_pago_Tarjeta' => $cobranzas->tipo_pago_Tarjeta,
                    'tipo_pago_TransaccionQR' => $cobranzas->tipo_pago_TransaccionQR,
                    'total_cobranzas' => $cobranzas->total_cobranzas,
                ]
            ];
        }

        return $resultados;
    }
}
