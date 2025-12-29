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

        // === INGRESOS ===
        $ingresosQuery = DB::table('ingresos as i')
            ->join('users as u', 'i.idusuario', '=', 'u.id')
            ->select(
                DB::raw('MONTH(i.fecha_hora) as mes'),
                DB::raw('YEAR(i.fecha_hora) as anio'),
                DB::raw('SUM(i.total) as total')
            )
            ->whereBetween('i.fecha_hora', [$fechaInicio, $fechaFin]);

        if ($idrol != 4) {
            $ingresosQuery->where('u.idsucursal', '=', $idsucursal);
        }

        $ingresos = $ingresosQuery
            ->groupBy(DB::raw('MONTH(i.fecha_hora)'), DB::raw('YEAR(i.fecha_hora)'))
            ->get();

        // === VENTAS ===
        $ventasQuery = DB::table('ventas as v')
            ->join('users as u', 'v.idusuario', '=', 'u.id')
            ->select(
                DB::raw('MONTH(v.fecha_hora) as mes'),
                DB::raw('YEAR(v.fecha_hora) as anio'),
                DB::raw('SUM(v.total) as total')
            )
            ->whereBetween('v.fecha_hora', [$fechaInicio, $fechaFin]);

        if ($idrol != 4) {
            $ventasQuery->where('u.idsucursal', '=', $idsucursal);
        }

        $ventas = $ventasQuery
            ->groupBy(DB::raw('MONTH(v.fecha_hora)'), DB::raw('YEAR(v.fecha_hora)'))
            ->get();

        // ✅ Agregamos el idrol del usuario a la respuesta
        return [
            'ingresos' => $ingresos,
            'ventas' => $ventas,
            'idrol' => $idrol, // <-- Aquí está
        ];
    }
}