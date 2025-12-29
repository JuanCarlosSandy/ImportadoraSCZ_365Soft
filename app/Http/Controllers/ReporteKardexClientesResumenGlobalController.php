<?php

namespace App\Http\Controllers;

use App\DetalleVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteKardexClientesResumenGlobalController extends Controller
{
  public function ventasPorCliente(Request $request)
  {

    if (!$request->ajax()) {
      return redirect('/');
    }

    $vendedorId = $request->vendedorId;
    $sucursalId = $request->sucursalId;
    $clienteId = $request->clienteId;
    $fechaInicio = $request->fechaInicio;
    $fechaFin = $request->fechaFin;
    $condicion = $request->condicion;

    if ($clienteId == 'todos') {
      return ['detalles' => []];
    }

    $detalles = DetalleVenta::join('ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
      ->join('articulos', 'articulos.id', '=', 'detalle_ventas.idarticulo')
      ->join('users', 'users.id', '=', 'ventas.idusuario')
      ->join('personas', 'personas.id', '=', 'ventas.idcliente')
      ->join('sucursales', 'sucursales.id', '=', 'users.idsucursal')
      ->join('tipo_ventas', 'tipo_ventas.id', '=', 'ventas.idtipo_venta')
      ->join('tipo_pagos', 'tipo_pagos.id', '=', 'ventas.idtipo_pago');

    if ($fechaInicio && $fechaFin) {
      $detalles->whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);
    }

    if ($clienteId != 'todos') {
      $detalles->where('ventas.idcliente', '=', $clienteId);
    }

    if ($sucursalId != 'todos') {
      $detalles->where('sucursales.id', '=', $sucursalId);
    }

    if ($vendedorId != 'todos') {
      $detalles->where('ventas.idusuario', '=', $vendedorId);
    }

    $detalles = $detalles
      ->select(
        'ventas.id as idventa',
        'ventas.idcliente',
        'ventas.idusuario',
        'ventas.num_comprobante',
        'ventas.tipo_comprobante',
        'ventas.fecha_hora',
        'ventas.estado',
        'ventas.impuesto',
        'ventas.total as total_con_descuento',
        'detalle_ventas.cantidad as unidades_vendidas',
        'detalle_ventas.precio as precio_total_venta',
        'detalle_ventas.descuento',
        'articulos.codigo',
        'articulos.nombre',
        'tipo_ventas.nombre_tipo_ventas',
        'tipo_pagos.nombre_tipo_pago',
        //'users.usuario as nombre_vendedor',
        'personas.nombre as nombre_cliente'
      )
      ->addSelect(DB::raw('ROUND(detalle_ventas.precio / detalle_ventas.cantidad, 2) as precio_por_unidad'))
      ->addSelect(DB::raw('(SELECT IFNULL(SUM(numero_cuotas), 0) FROM credito_ventas WHERE idventa = ventas.id AND estado = "Pendiente") as total_cuotas'))
      ->addSelect(DB::raw('(SELECT IFNULL(SUM(total), 0) FROM credito_ventas WHERE idventa = ventas.id AND estado = "Pendiente") as saldo_pendiente'))
      ->addSelect(DB::raw('(SELECT IFNULL(MIN(proximo_pago), 0) FROM credito_ventas WHERE idventa = ventas.id AND estado = "Pendiente") as proximo_pago'))
      ->addSelect(DB::raw('(SELECT nombre FROM personas WHERE personas.id = ventas.idusuario) as nombre_vendedor'));

    if ($condicion == 1) {
      $detalles = $detalles->orderBy('ventas.id', 'ASC')->get();

      return ['detalles' => $detalles];
    }

    if ($condicion == 0) {
      $detalles = $detalles->orderBy('ventas.id', 'ASC')->paginate(5);

      return [
        'pagination' => [
          'total' => $detalles->total(),
          'current_page' => $detalles->currentPage(),
          'per_page' => $detalles->perPage(),
          'last_page' => $detalles->lastPage(),
          'from' => $detalles->firstItem(),
          'to' => $detalles->lastItem(),
        ],
        'detalles' => $detalles
      ];
    }
  }
}

