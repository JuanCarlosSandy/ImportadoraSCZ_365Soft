<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class DashboardController extends Controller
{


    public function __invoke(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $usuario = \Auth::user();
        $idsucursal = $usuario->idsucursal;
        $idrol = $usuario->idrol;

        // Consulta de ventas: sin JOIN con detalle_ventas
        $ventasQuery = DB::table('ventas as v')
            ->join('users as u', 'v.idusuario', '=', 'u.id')
            ->whereBetween('v.fecha_hora', [$fechaInicio, $fechaFin])
            ->where('v.estado', 1);

        if ($idrol != 4) {
            $ventasQuery->where('u.idsucursal', $idsucursal);
        }

        $ventas = $ventasQuery
            ->select(
                DB::raw('YEAR(v.fecha_hora) as anio'),
                DB::raw('MONTH(v.fecha_hora) as mes'),
                DB::raw('SUM(v.total) as total_ventas')
            )
            ->groupBy(
                DB::raw('YEAR(v.fecha_hora)'),
                DB::raw('MONTH(v.fecha_hora)')
            )
            ->get();

        // Consulta de costos
        $costosQuery = DB::table('ventas as v')
            ->join('detalle_ventas as dv', 'v.id', '=', 'dv.idventa')
            ->join('articulos as a', 'dv.idarticulo', '=', 'a.id')
            ->join('users as u', 'v.idusuario', '=', 'u.id')
            ->whereBetween('v.fecha_hora', [$fechaInicio, $fechaFin])
            ->where('v.estado', 1);

        if ($idrol != 4) {
            $costosQuery->where('u.idsucursal', $idsucursal);
        }

        $costos = $costosQuery
            ->select(
                DB::raw('YEAR(v.fecha_hora) as anio'),
                DB::raw('MONTH(v.fecha_hora) as mes'),
                DB::raw('SUM(dv.cantidad * a.precio_costo_unid) as total_costo')
            )
            ->groupBy(
                DB::raw('YEAR(v.fecha_hora)'),
                DB::raw('MONTH(v.fecha_hora)')
            )
            ->get();

        // Combinar resultados por año y mes
        $data = $ventas->map(function ($venta) use ($costos) {
            $costo = $costos->first(function ($item) use ($venta) {
                return $item->anio == $venta->anio
                    && $item->mes == $venta->mes;
            });

            $venta->total_costo = $costo
                ? $costo->total_costo
                : 0;

            $venta->utilidad = $venta->total_ventas
                - $venta->total_costo;

            return $venta;
        });

        return [
            'data' => $data->values(),
            'idrol' => $idrol,
        ];
    }
}