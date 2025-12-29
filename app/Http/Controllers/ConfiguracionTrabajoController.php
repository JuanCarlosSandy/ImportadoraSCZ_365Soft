<?php

namespace App\Http\Controllers;

use App\Almacen;
use App\Precio;
use Illuminate\Http\Request;
use App\ConfiguracionTrabajo;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class ConfiguracionTrabajoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $configuracionTrabajo = ConfiguracionTrabajo::select(
                'configuracion_trabajos.*',
                'monedas_venta.simbolo as simbolo_moneda_venta',
                'monedas_compra.simbolo as simbolo_moneda_compra',
                'monedas_principal.simbolo as simbolo_moneda_principal',

                'monedas_venta.tipo_cambio as valor_moneda_venta',
                'monedas_compra.tipo_cambio as valor_moneda_compra',
                'monedas_principal.tipo_cambio as valor_moneda_principal'
            )
                ->join('monedas as monedas_venta', 'configuracion_trabajos.idMonedaVenta', '=', 'monedas_venta.id')
                ->join('monedas as monedas_compra', 'configuracion_trabajos.idMonedaCompra', '=', 'monedas_compra.id')
                ->join('monedas as monedas_principal', 'configuracion_trabajos.idMonedaPrincipal', '=', 'monedas_principal.id')


                ->first();

            return response()->json(['configuracionTrabajo' => $configuracionTrabajo]);
        } catch (\Exception $e) {
            Log::error('Error al obtener configuración de trabajo: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $configuracionTrabajo = ConfiguracionTrabajo::first();

        /*Log::info('coniguracion', [
            'data' => $configuracionTrabajo 
        ]);*/

        return ['configuracionTrabajo' => $configuracionTrabajo];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        try {
            DB::beginTransaction();
            Log::info('coniguracion', [
                'saldoNegativo' => $request->idMonedaCompra
            ]);
            $configuracionTrabajo = ConfiguracionTrabajo::findOrFail($request->id);
            $configuracionTrabajo->idMonedaCompra = $request->idMonedaCompra;
            $configuracionTrabajo->idMonedaVenta = $request->idMonedaVenta;
            $configuracionTrabajo->idMonedaPrincipal = $request->idMonedaPrincipal;
            Log::info($configuracionTrabajo);
            $configuracionTrabajo->gestion = $request->selectedYear;
            $configuracionTrabajo->almacenPredeterminado = $request->almacenPredeterminado;
            $configuracionTrabajo->codigoProductos = $request->codigoProducto;
            $configuracionTrabajo->maximoDescuento = $request->maximoDescuento;
            $configuracionTrabajo->valuacionInventario = $request->valuacionInventario;
            $configuracionTrabajo->backupAutomatico = $request->backupAutomatico;
            $configuracionTrabajo->rutaBackup = $request->rutaBackup;
            $configuracionTrabajo->saldosNegativos = $request->saldosNegativos;
            $configuracionTrabajo->separadorDecimales = $request->separadorDecimales;
            $configuracionTrabajo->mostrarCostos = $request->mostrarCostos;
            $configuracionTrabajo->mostrarProveedores = $request->mostrarProveedor;
            $configuracionTrabajo->mostrarSaldosStock = $request->mostrarSaldosStock;
            $configuracionTrabajo->actualizarIva = $request->actualizarIVA;
            $configuracionTrabajo->permitirDevolucion = $request->devolucion;
            $configuracionTrabajo->editarNroDoc = $request->editarNroDoc;
            $configuracionTrabajo->registroClienteObligatorio = $request->registroClienteObligatorio;
            $configuracionTrabajo->buscarClientePorCodigo = $request->buscarClientePorCodigo;
            $configuracionTrabajo->permitir_bonificacion = $request->habilitarBonificacion;
            $configuracionTrabajo->permitir_descuento = $request->habilitarDescuentos;
            $configuracionTrabajo->permitir_ofertas = $request->habilitarOfertas;
            $configuracionTrabajo->permitir_cambioprecio = $request->habilitarcambioprecio;

            $configuracionTrabajo->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar configuración: ' . $e->getMessage());
        }
    }
    // FUNCION INNECESARIA 
    // public function obtenerSaldosNegativos()
    // {
    //     try {
    //         $saldosNegativos = ConfiguracionTrabajo::value('saldosNegativos');
    //         return response()->json(['saldosNegativos' => $saldosNegativos]);
    //     } catch (\Exception $e) {
    //         Log::error('Error al obtener saldos negativos: ' . $e->getMessage());
    //         return response()->json(['error' => 'Error interno del servidor'], 500);
    //     }
    // }

    public function obtenerIva()
    {
        try {
            $iva = ConfiguracionTrabajo::value('actualizarIva');
            return response()->json(['actualizarIva' => $iva]);
        } catch (\Exception $e) {
            Log::error('Error al obtener iva: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getPorcentajes()
    {
        try {
            // Recupera todos los registros de la tabla precios
            $precios = Precio::select('id', 'nombre_precio', 'porcentage')->get();

            return response()->json($precios, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los porcentajes.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    public function putPorcentajes(Request $request)
    {
        try {
            $data = $request->all();
            $registrosActualizados = [];

            foreach ($data as $item) {
                // Buscamos el registro por nombre_precio
                $precio = Precio::where('nombre_precio', $item['nombre_precio'])->first();

                if ($precio) {
                    $precio->porcentage = $item['porcentage']; // 👈 campo correcto
                    $precio->updated_at = now();               // 👈 actualiza manualmente (por si timestamps no lo hace)
                    $precio->save();

                    // Agregamos el registro actualizado al array
                    $registrosActualizados[] = $precio;
                }
            }

            return response()->json([
                'message' => 'Porcentajes actualizados correctamente.',
                'data' => $registrosActualizados
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar los porcentajes.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function listarAlmacen()
    {
        $almacenes = Almacen::all();
        
        return response()->json($almacenes);
    }

    public function obtenerAlmacenPredeterminado()
    {
        $configuracion = ConfiguracionTrabajo::first();
        //dd($configuracion->almacenPredeterminado);
        if ($configuracion) {
            return response()->json(['almacen_predeterminado_id' => $configuracion->almacenPredeterminado]);
        } else {
            return response()->json(['almacen_predeterminado_id' => null], 404);
        }
    }
}
