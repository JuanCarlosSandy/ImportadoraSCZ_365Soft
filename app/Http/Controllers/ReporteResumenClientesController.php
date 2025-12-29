<?php

namespace App\Http\Controllers;

use App\CreditoVenta;
use App\Persona;
use App\Sucursales;
use App\User;
use App\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteResumenClientesController extends Controller
{ 
    
    public function clientesPorVendedor(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $vendedorId = $request->vendedorId;
        $sucursalId = $request->sucursalId;
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;

        $clientes = Venta::join('personas', 'ventas.idcliente','=','personas.id')
            ->join('users', 'ventas.idusuario','=','users.id')
            ->join('sucursales', 'sucursales.id','=','users.idsucursal');

        if ($fechaInicio && $fechaFin) {
            $clientes->whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);
        }

        if ($sucursalId != 'todos') {
            $clientes->where('sucursales.id','=',$sucursalId);

            $sucursal = Sucursales::where('sucursales.id','=',$sucursalId)
                ->select('sucursales.nombre as nombre')
                ->get();

            $sucursalId = $sucursal[0]->nombre;
        }

        if ($vendedorId != 'todos') {
            $clientes->where('users.id','=',$vendedorId);

            $vendedor = Persona::where('personas.id','=',$vendedorId)
                ->select('personas.nombre as nombre')
                ->get();

            $vendedorId = $vendedor[0]->nombre;
        }

        $clientes = $clientes
            ->leftJoin('credito_ventas', function ($join) {
                $join->on('personas.id','=','credito_ventas.idcliente')
                    ->where('credito_ventas.estado', '=', 'Pendiente');
            })
            ->select(
                'personas.id as cliente_id',
                'personas.nombre as cliente_nombre',
                'personas.num_documento',
                DB::raw('SUM(ventas.total) as total_ventas'),
                DB::raw('COALESCE(MAX(credito_ventas.total), 0) as total_deuda'),
                DB::raw('COALESCE(MAX(credito_ventas.numero_cuotas), 0) as numero_cuotas'),
                DB::raw('CASE 
                            WHEN COALESCE(MAX(credito_ventas.total), 0) = 0 THEN SUM(ventas.total)
                            ELSE SUM(ventas.total) - COALESCE(MAX(credito_ventas.total), 0)
                        END as cobros')
            )
            ->groupBy('personas.id', 'personas.nombre', 'personas.num_documento')
            ->orderBy('personas.id', 'asc')
            ->get();

        return ['clientes' => $clientes,
                'filtros' => [$vendedorId, $sucursalId]
            ];
    }
}