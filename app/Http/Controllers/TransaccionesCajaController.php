<?php

namespace App\Http\Controllers;

use App\Caja;
use App\Ingreso;
use App\TransaccionesCaja;
use App\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TransaccionesCajaController extends Controller
{
     public function index(Request $request, $id)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;

        if ($buscar==''){
            $transaccionesCajas = TransaccionesCaja::join('cajas', 'transacciones_cajas.idcaja', '=', 'cajas.id')
                ->join('users', 'transacciones_cajas.idusuario', '=', 'users.id')
                ->select('transacciones_cajas.id', 'transacciones_cajas.idcaja', 'transacciones_cajas.idusuario', 'users.usuario as usuario', 'transacciones_cajas.fecha', 'transacciones_cajas.transaccion', 'transacciones_cajas.importe', 'cajas.fechaApertura')
                ->where('transacciones_cajas.idcaja', '=', $id)
                ->orderBy('transacciones_cajas.id', 'desc')->paginate(8);
        }


        $ventas = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
                ->join('users', 'ventas.idusuario', '=', 'users.id')
                ->select(
                    'ventas.id',
                    'ventas.tipo_comprobante',
                    'ventas.serie_comprobante',
                    'ventas.num_comprobante',
                    'ventas.fecha_hora',
                    'ventas.impuesto',
                    'ventas.total',
                    'ventas.estado',
                    'personas.nombre',
                    'users.usuario'
                )
                ->orderBy('ventas.id', 'desc')
                ->where('idcaja', $id)
                ->paginate(8);

        return [
            'pagination' => [
                 'total'        => $transaccionesCajas->total(),
                 'current_page' => $transaccionesCajas->currentPage(),
                 'per_page'     => $transaccionesCajas->perPage(),
                 'last_page'    => $transaccionesCajas->lastPage(),
                 'from'         => $transaccionesCajas->firstItem(),
                 'to'           => $transaccionesCajas->lastItem(),
            ],
            'transacciones' => $transaccionesCajas,
            'ventas'=> $ventas,
            // 'ventas' => [
                // 'data'=> $ventas,
                // 'pagination' => [
                //     'total'        => $ventas->total(),
                //     'current_page' => $ventas->currentPage(),
                //     'per_page'     => $ventas->perPage(),
                //     'last_page'    => $ventas->lastPage(),
                //     'from'         => $ventas->firstItem(),
                //     'to'           => $ventas->lastItem(),
                // ],
            // ]

        ];
    }
}
