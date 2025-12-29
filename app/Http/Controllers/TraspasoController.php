<?php

namespace App\Http\Controllers;

use App\DetalleTraspaso;
use App\Inventario;
use App\Traspaso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TraspasoController extends Controller
{
    //----lisTado or filtro de fecha--
   public function index(Request $request)
{
    if (!$request->ajax()) return redirect('/');

    $fechaInicio = $request->fechaInicio;
    $fechaFin = $request->fechaFin;

    $traspasos = Traspaso::select(
            'traspasos.id', 
            'traspasos.almacen_origen', 
            'traspasos.almacen_destino', 
            'traspasos.fecha_traspaso', 
            'traspasos.idusuario', 
            'traspasos.created_at',
            'ao.nombre_almacen as nombre_almacen_origen',
            'ad.nombre_almacen as nombre_almacen_destino',
            'personas.nombre as nombre_usuario' // ← Aquí sacamos el nombre del usuario
        )
        ->join('almacens as ao', 'traspasos.almacen_origen', '=', 'ao.id')
        ->join('almacens as ad', 'traspasos.almacen_destino', '=', 'ad.id')
        ->join('users', 'traspasos.idusuario', '=', 'users.id')
        ->join('personas', 'users.id', '=', 'personas.id') // ← Join con personas
        ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
        ->paginate(100);

    return [
        'pagination' => [
            'total'        => $traspasos->total(),
            'current_page' => $traspasos->currentPage(),
            'per_page'     => $traspasos->perPage(),
            'last_page'    => $traspasos->lastPage(),
            'from'         => $traspasos->firstItem(),
            'to'           => $traspasos->lastItem(),
        ],
        'traspasos' => $traspasos
    ];
}

    //--registrando datos de Trapaso luego pasando datosa  inventario para registrar---
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
    
            // Crear el traspaso
            $traspasos = new Traspaso();
            $traspasos->tipo_traspaso = $request->tipo_traspaso;
            $traspasos->idusuario = \Auth::user()->id;
            $traspasos->almacen_origen = $request->almacen_origen;
            $traspasos->almacen_destino = $request->almacen_destino;
            $traspasos->fecha_traspaso = $request->fecha_traspaso;
            $traspasos->save();
    
            // Obtener los detalles del traspaso
            $detalles = $request->data;
    
            foreach ($detalles as $detalle) {
    $cantidadRestante = $detalle['cantidad_traspaso'];

    // Inventarios origen (múltiples, ordenados por fecha de vencimiento)
    $inventariosOrigen = Inventario::where('idalmacen', $detalle['idalmacen'])
        ->where('idarticulo', $detalle['idarticulo'])
        ->where('saldo_stock', '>', 0)
        ->orderBy('fecha_vencimiento', 'asc')
        ->get();

    foreach ($inventariosOrigen as $inventarioOrigen) {
        if ($cantidadRestante <= 0) break;

        $cantidadADescontar = min($cantidadRestante, $inventarioOrigen->saldo_stock);
        $inventarioOrigen->saldo_stock -= $cantidadADescontar;
        $inventarioOrigen->save();

        // Buscar o crear inventario en el destino con la misma fecha de vencimiento
        $inventarioDestino = Inventario::where('idalmacen', $detalle['idalmacendes'])
            ->where('idarticulo', $detalle['idarticulo'])
            ->where('fecha_vencimiento', $inventarioOrigen->fecha_vencimiento)
            ->first();

        if ($inventarioDestino) {
            $inventarioDestino->saldo_stock += $cantidadADescontar;
            $inventarioDestino->cantidad += $cantidadADescontar;
        } else {
            $inventarioDestino = new Inventario();
            $inventarioDestino->idalmacen = $detalle['idalmacendes'];
            $inventarioDestino->idarticulo = $detalle['idarticulo'];
            $inventarioDestino->saldo_stock = $cantidadADescontar;
            $inventarioDestino->cantidad = $cantidadADescontar;
            $inventarioDestino->fecha_vencimiento = $inventarioOrigen->fecha_vencimiento;
        }
        $inventarioDestino->save();

        // Registrar detalle del traspaso por lote
        $detalletraspaso = new DetalleTraspaso();
        $detalletraspaso->idtraspaso = $traspasos->id;
        $detalletraspaso->idinventario = $inventarioDestino->id;
        $detalletraspaso->cantidad_traspaso = $cantidadADescontar;
        $detalletraspaso->save();

        $cantidadRestante -= $cantidadADescontar;
    }

    if ($cantidadRestante > 0) {
        throw new Exception('Stock insuficiente para completar el traspaso del artículo: ' . $detalle['idarticulo']);
    }
}

    
            DB::commit();
    
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar traspaso', ['exception' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    

    //---listado por id lo que se traspaso--
    public function indexPorID(Request $request){
        if (!$request->ajax()) return redirect('/');
 
        $idtraspaso = $request->idtraspaso;
        $detalletrasp = DetalleTraspaso::join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id') 
            ->join('traspasos as t', 'detalle_traspasos.idtraspaso', '=', 't.id')
            ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')

        ->select(
            'detalle_traspasos.id',
            'detalle_traspasos.cantidad_traspaso',
            'inventarios.saldo_stock',
            'inventarios.fecha_vencimiento',
            'articulos.nombre as nombre_producto',
            'articulos.unidad_envase',
            'articulos.precio_costo_unid',
            'proveedores.contacto',
        )
        ->where('detalle_traspasos.idtraspaso','=',$idtraspaso)->get();   
        return [ 'detalletrasp' => $detalletrasp];
    }
    
}
