<?php

namespace App\Http\Controllers;

use App\DetalleVenta;
use Illuminate\Http\Request;


class ReporteKardexClientesDetalladoGlobalController extends Controller
{ 
    public function articulosPorCliente(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $vendedorId = $request->vendedorId;
        $sucursalId = $request->sucursalId;
        $clienteId = $request->clienteId;
        $tipo_comprobante = $request->tipo_comprobante;
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;

        $detalles = DetalleVenta::join('articulos','articulos.id','=','detalle_ventas.idarticulo')
            ->join('ventas','ventas.id','=','detalle_ventas.idventa')
            ->join('users', 'ventas.idusuario','=','users.id')
            ->join('sucursales', 'sucursales.id','=','users.idsucursal')
            ->join('inventarios', 'inventarios.idarticulo','=','articulos.id');

        if ($fechaInicio && $fechaFin) {
            $detalles->whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);
        }

        if ($sucursalId != 'todos') {
            $detalles->where('sucursales.id','=',$sucursalId);
        }

        if ($vendedorId != 'todos') {
            $detalles->where('ventas.idusuario','=',$vendedorId);
        }

        if ($clienteId != 'todos') {
            $detalles->where('ventas.idcliente','=',$clienteId);
        }

        if ($tipo_comprobante != 'todos') {
            $detalles->where('ventas.tipo_comprobante','=',$tipo_comprobante);
        }

        /*$detalles = $detalles
            ->select(

            )
            ->get();
            */
        return ['detalles' => $detalles->get()];
    }
}