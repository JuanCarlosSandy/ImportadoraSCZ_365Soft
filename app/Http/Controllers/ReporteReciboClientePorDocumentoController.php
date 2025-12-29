<?php

namespace App\Http\Controllers;

use App\DetalleVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteReciboClientePorDocumentoController extends Controller
{
  public function clientesPorDocumento(Request $request)
  {

    if (!$request->ajax()) {
      return redirect('/');
    }

    $vendedorId = $request->vendedorId;
    $sucursalId = $request->sucursalId;
    $tipoPagoId = $request->tipoPagoId;
    $clienteId = $request->clienteId;
    $estado = $request->estado;
    $fechaInicio = $request->fechaInicio;
    $fechaFin = $request->fechaFin;
    $tipoCambio = $request->tipoCambio;
    $condicion = $request->condicion;

    if ($clienteId == 'todos') {
      return ['detalles' => []];
    }

    $detalles = DetalleVenta::join('ventas', 'ventas.id', '=', 'detalle_ventas.idventa')
      ->join('personas', 'personas.id', '=', 'ventas.idcliente')
      ->join('users', 'users.id', '=', 'ventas.idusuario')
      ->join('sucursales', 'sucursales.id', '=', 'users.idsucursal')
      ->join('tipo_pagos', 'tipo_pagos.id', '=', 'ventas.idtipo_pago')
      ->join('tipo_ventas', 'tipo_ventas.id', '=', 'ventas.idtipo_venta');

    if ($clienteId != 'todos') {
      $detalles->where('ventas.idcliente', '=', $clienteId);
    }

    if ($fechaInicio && $fechaFin) {
      $detalles->whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);
    }

    if ($tipoPagoId != 'todos') {
      $detalles->where('ventas.idtipo_pago', '=', $tipoPagoId);
    }

    if ($estado != 'todos') {
      $detalles->where('ventas.estado', '=', $estado);
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
        'ventas.tipo_comprobante',
        'ventas.num_comprobante',
        'ventas.estado',
        'ventas.fecha_hora',
        'ventas.impuesto',
        'ventas.total as venta_total',
        'detalle_ventas.precio as venta_final',
        'detalle_ventas.descuento',
        //'users.usuario as nombre_vendedor',
        'personas.nombre as nombre_cliente',
        'tipo_ventas.nombre_tipo_ventas',
        'tipo_pagos.nombre_tipo_pago',
        'sucursales.nombre as nombre_sucursal',
        DB::raw("ROUND(detalle_ventas.precio * $tipoCambio, 2) as importe_calculado")
      )
      ->addSelect(DB::raw('(SELECT nombre FROM personas WHERE personas.id = ventas.idusuario) as nombre_vendedor'));

    if ($condicion == 1) {
      $detalles = $detalles->orderBy('ventas.id', 'ASC')->get();

      return ['detalles' => $detalles];
    }

    if ($condicion == 0) {
      $detalles = $detalles->orderBy('ventas.id', 'ASC')->paginate(10);

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

