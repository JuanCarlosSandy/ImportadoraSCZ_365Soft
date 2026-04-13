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

        // =========================
        // BASE QUERY (VENTAS + COSTO)
        // =========================
        $baseQuery = DB::table('ventas as v')
            ->join('detalle_ventas as dv', 'v.id', '=', 'dv.idventa')
            ->join('articulos as a', 'dv.idarticulo', '=', 'a.id')
            ->join('users as u', 'v.idusuario', '=', 'u.id')
            ->select(
                DB::raw('MONTH(v.fecha_hora) as mes'),
                DB::raw('YEAR(v.fecha_hora) as anio'),

                // 💰 TOTAL VENTAS
                DB::raw('SUM(v.total) as total_ventas'),

                // 📦 COSTO DE VENTAS
                DB::raw('SUM(dv.cantidad * a.precio_costo_unid) as total_costo'),

                // 📊 UTILIDAD BRUTA
                DB::raw('SUM(v.total) - SUM(dv.cantidad * a.precio_costo_unid) as utilidad')
            )
            ->whereBetween('v.fecha_hora', [$fechaInicio, $fechaFin])
            ->where('v.estado', '=', 1); // 🔒 solo ventas válidas

        // 🔒 Filtro por sucursal
        if ($idrol != 4) {
            $baseQuery->where('u.idsucursal', '=', $idsucursal);
        }

        $data = $baseQuery
            ->groupBy(DB::raw('MONTH(v.fecha_hora)'), DB::raw('YEAR(v.fecha_hora)'))
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();

        return [
            'data' => $data,
            'idrol' => $idrol,
        ];
    }
}