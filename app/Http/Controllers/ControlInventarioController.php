<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\ControlInventario;
use App\DetalleControlInventario;
use App\Inventario;
use App\AjusteInvetario;
use Barryvdh\DomPDF\Facade as PDF;
use App\Exports\ControlInventarioExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
class ControlInventarioController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->buscar;
        $criterio = $request->criterio;
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $idAlmacen = $request->idAlmacen;

        $user = auth()->user(); // 🔥 usuario logueado

        $query = ControlInventario::with('usuario', 'almacen');

        // 🔒 FILTRO POR USUARIO (SI NO ES ADMIN)
        if ($user->idrol != 4) {
            $query->where('idusuario', $user->id);
        }

        // 🔍 BUSCADOR
        if (!empty($buscar) && !empty($criterio)) {
            if ($criterio == 'usuario') {
                $query->whereHas('usuario', function ($q) use ($buscar) {
                    $q->where('name', 'like', '%' . $buscar . '%');
                });
            }
        }

        // 📅 FILTRO POR FECHAS
        if (!empty($fechaInicio)) {
            $query->whereDate('fechahora', '>=', $fechaInicio);
        }

        if (!empty($fechaFin)) {
            $query->whereDate('fechahora', '<=', $fechaFin);
        }

        // 🏬 FILTRO POR ALMACÉN
        if (!empty($idAlmacen)) {
            $query->where('idalmacen', $idAlmacen);
        }

        // 📊 PAGINACIÓN
        $controles = $query->orderBy('id', 'desc')->paginate(10);

        return response()->json([
            'pagination' => [
                'total' => $controles->total(),
                'current_page' => $controles->currentPage(),
                'per_page' => $controles->perPage(),
                'last_page' => $controles->lastPage(),
                'from' => $controles->firstItem(),
                'to' => $controles->lastItem(),
            ],
            'data' => $controles->items()
        ]);
    }

    public function verDetalle($id)
    {
        $control = ControlInventario::with(
            'usuario',
            'almacen',
            'detalles.articulo'
        )->findOrFail($id);

        // 🔥 Agregar stock actual del sistema
        foreach ($control->detalles as $det) {

            $inventario = Inventario::where('idarticulo', $det->idarticulo)
                ->where('idalmacen', $control->idalmacen)
                ->first();

            $det->stock_actual = $inventario ? $inventario->saldo_stock : 0;
        }

        return response()->json($control);
    }

    public function store(Request $request)
    {
        $request->validate([
            'idalmacen' => 'required|exists:almacens,id',
            'detalles' => 'required|array|min:1',
            'detalles.*.idarticulo' => 'required|exists:articulos,id',
            'detalles.*.stocksistema' => 'required|numeric',
            'detalles.*.stockfisico' => 'required|numeric'
        ]);
        try {
            DB::beginTransaction();

            // Crear control
            $control = new ControlInventario();
            $control->idusuario = auth()->id(); // o $request->idusuario
            $control->idalmacen = $request->idalmacen;
            $control->fechahora = now();
            $control->estado = 1;
            $control->save();

            // Guardar detalles
            foreach ($request->detalles as $det) {
                $detalle = new DetalleControlInventario();
                $detalle->idcontrol = $control->id;
                $detalle->idarticulo = $det['idarticulo'];
                $detalle->stocksistema = $det['stocksistema'];
                $detalle->stockfisico = $det['stockfisico'];
                $detalle->estado = 1;
                $detalle->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Control de inventario registrado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $control = ControlInventario::findOrFail($id);
            $control->delete();

            return response()->json([
                'message' => 'Control eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function cancelarDetalle($id)
    {
        try {

            $cambioEstadoControl = false;

            $detalle = DetalleControlInventario::findOrFail($id);

            $detalle->estado = 0; // CANCELADO
            $detalle->save();

            // 🔥 VALIDAR SI TODAVÍA EXISTEN PENDIENTES
            $existenPendientes = DetalleControlInventario::where('idcontrol', $detalle->idcontrol)
                ->where('estado', 1)
                ->exists();

            // 🔥 SI YA NO HAY PENDIENTES → CERRAR CONTROL
            if (!$existenPendientes) {

                $control = ControlInventario::find($detalle->idcontrol);

                if ($control) {
                    $control->estado = 2; // VERIFICADO
                    $control->save();

                    $cambioEstadoControl = true;
                }
            }

            return response()->json([
                'message' => 'Detalle cancelado correctamente',
                'control_actualizado' => $cambioEstadoControl
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    public function pasarEstado($id)
    {
        try {

            $cambioEstadoControl = false;

            $detalle = DetalleControlInventario::findOrFail($id);

            // 🔵 SIN DIFERENCIA
            $detalle->estado = 3;
            $detalle->save();

            // 🔥 VALIDAR SI TODAVÍA EXISTEN PENDIENTES
            $existenPendientes = DetalleControlInventario::where('idcontrol', $detalle->idcontrol)
                ->where('estado', 1)
                ->exists();

            // 🔥 SI YA NO HAY PENDIENTES → CERRAR CONTROL
            if (!$existenPendientes) {

                $control = ControlInventario::find($detalle->idcontrol);

                if ($control) {

                    $control->estado = 2; // VERIFICADO
                    $control->save();

                    $cambioEstadoControl = true;
                }
            }

            return response()->json([
                'message' => 'Estado actualizado correctamente',
                'control_actualizado' => $cambioEstadoControl
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    public function registrarAjuste(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        DB::beginTransaction();
        $cambioEstadoControl = false;
        try {
            $ajuste = new AjusteInvetario();
            $ajuste->cantidad = $request->cantidad;
            $ajuste->producto = $request->producto;
            $ajuste->almacen = $request->idAlmacenSeleccionado;
            $ajuste->tipo_movimiento = $request->tipo_movimiento;
            $ajuste->idtipobajas = 1;
            $ajuste->save();

            $detalle = [
                'cantidad' => $ajuste->cantidad,
                'idarticulo' => $ajuste->producto,
                'tipo_movimiento' => $request->tipo_movimiento
            ];

            $this->actualizarInventario($ajuste->almacen, $detalle);

            // 🔥 ACTUALIZAR DETALLE
            $detalleControl = DetalleControlInventario::find($request->iddetalle);

            if ($detalleControl) {
                $detalleControl->estado = 2; // VERIFICADO
                $detalleControl->save();

                // 🔥 VALIDAR SI TODOS LOS DETALLES YA ESTÁN VERIFICADOS
                $existenPendientes = DetalleControlInventario::where('idcontrol', $detalleControl->idcontrol)
                    ->where('estado', 1) // NO VERIFICADO
                    ->exists();

                // 👉 si NO hay pendientes
                if (!$existenPendientes) {
                    $control = ControlInventario::find($detalleControl->idcontrol);
                    if ($control) {
                        $control->estado = 2; // VERIFICADO
                        $control->save();
                        $cambioEstadoControl = true; // 🔥 IMPORTANTE
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Ajuste realizado', 'control_actualizado' => $cambioEstadoControl]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarInventario($idalmacen, $detalle)
    {
        $inventario = Inventario::where('idalmacen', $idalmacen)
            ->where('idarticulo', $detalle['idarticulo'])
            ->first();

        // 🔥 SI NO EXISTE, CREARLO
        if (!$inventario) {

            $inventario = new Inventario();
            $inventario->idalmacen = $idalmacen;
            $inventario->idarticulo = $detalle['idarticulo'];

            // 🔵 STOCK INICIAL SEGÚN TIPO MOVIMIENTO
            if ($detalle['tipo_movimiento'] == 'entrada') {
                $inventario->saldo_stock = $detalle['cantidad'];
                $inventario->cantidad = $detalle['cantidad'];

            } else {
                // si es salida, inicia en negativo o 0
                $inventario->saldo_stock = -$detalle['cantidad'];
                $inventario->cantidad = -$detalle['cantidad'];

            }

            $inventario->save();
            return;
        }

        // 🔵 SI YA EXISTE, ACTUALIZAR
        if ($detalle['tipo_movimiento'] == 'entrada') {
            $inventario->saldo_stock += $detalle['cantidad'];
        } else {
            $inventario->saldo_stock -= $detalle['cantidad'];
        }

        $inventario->save();
    }

    public function editarStockFisico(Request $request, $id)
    {
        try {

            $detalle = DetalleControlInventario::findOrFail($id);

            $detalle->stockfisico = $request->stockfisico;
            $detalle->save();

            return response()->json([
                'message' => 'Stock físico actualizado correctamente'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    public function generarPdf($id)
    {
        $control = ControlInventario::with(
            'usuario',
            'almacen',
            'detalles.articulo'
        )->findOrFail($id);

        // 🔥 ROL USUARIO LOGUEADO
        $rolUsuario = Auth::user()->idrol;

        // 🔥 agregar stock actual
        foreach ($control->detalles as $detalle) {

            $inventario = Inventario::where('idalmacen', $control->idalmacen)
                ->where('idarticulo', $detalle->idarticulo)
                ->first();

            $detalle->stock_actual = $inventario ? $inventario->saldo_stock : 0;
        }

        $total = $control->detalles->count();

        $verificados = $control->detalles->where('estado', 2)->count();
        $pendientes = $control->detalles->where('estado', 1)->count();
        $anulados = $control->detalles->where('estado', 0)->count();

        $estadoGeneral = ($pendientes == 0) ? 'AJUSTADO' : 'PENDIENTE';

        $pdf = PDF::loadView('pdf.control_inventario', compact(
            'control',
            'total',
            'verificados',
            'pendientes',
            'anulados',
            'estadoGeneral',
            'rolUsuario'
        ));

        return $pdf->download('control_inventario_' . $control->id . '.pdf');
    }

    public function exportExcel($id)
    {
        return Excel::download(new ControlInventarioExport($id), 'control_inventario.xlsx');
    }
}
