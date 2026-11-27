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
use App\MovimientoInventario;
use App\Services\MovimientoInventarioService;
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

        $request->validate([
            'producto' => 'required|integer|exists:articulos,id',
            'idAlmacenSeleccionado' => 'required|integer|exists:almacens,id',
            'cantidad' => 'required|integer|min:1',
            'tipo_movimiento' => 'required|in:entrada,salida',
            'iddetalle' => 'required|integer'
        ]);

        DB::beginTransaction();

        $cambioEstadoControl = false;

        try {

            // Bloquear el detalle para evitar ajustes duplicados.
            $detalleControl = DetalleControlInventario::where(
                'id',
                $request->iddetalle
            )
                ->lockForUpdate()
                ->first();

            if (!$detalleControl) {
                throw new \Exception(
                    'No se encontró el detalle del control de inventario.'
                );
            }

            // Evitar procesar dos veces el mismo detalle.
            if ((int) $detalleControl->estado === 2) {
                throw new \Exception(
                    'Este producto ya fue verificado anteriormente.'
                );
            }

            // Registrar ajuste.
            $ajuste = new AjusteInvetario();
            $ajuste->cantidad = (int) $request->cantidad;
            $ajuste->producto = $request->producto;
            $ajuste->almacen = $request->idAlmacenSeleccionado;
            $ajuste->tipo_movimiento = $request->tipo_movimiento;
            $ajuste->idtipobajas = 1;
            $ajuste->save();

            $detalle = [
                'cantidad' => $ajuste->cantidad,
                'idarticulo' => $ajuste->producto,
                'tipo_movimiento' => $ajuste->tipo_movimiento,
                'idajuste' => $ajuste->id,
                'iddetalle_control' => $detalleControl->id
            ];

            // Actualizar inventario y registrar movimiento histórico.
            $this->actualizarInventario(
                $ajuste->almacen,
                $detalle
            );

            // Bloquear la cabecera del control.
            $control = ControlInventario::where(
                'id',
                $detalleControl->idcontrol
            )
                ->lockForUpdate()
                ->first();

            if (!$control) {
                throw new \Exception(
                    'No se encontró el control de inventario.'
                );
            }

            // Marcar detalle como verificado.
            $detalleControl->estado = 2;
            $detalleControl->save();

            // Verificar si todavía existen detalles pendientes.
            $existenPendientes = DetalleControlInventario::where(
                'idcontrol',
                $detalleControl->idcontrol
            )
                ->where('estado', 1)
                ->exists();

            // Actualizar cabecera si todos están verificados.
            if (!$existenPendientes) {

                $control->estado = 2;
                $control->save();

                $cambioEstadoControl = true;
            }

            DB::commit();

            return response()->json([
                'message' => 'Ajuste realizado',
                'control_actualizado' => $cambioEstadoControl
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function actualizarInventario($idalmacen, $detalle)
    {
        $idalmacen = (int) $idalmacen;
        $idarticulo = (int) $detalle['idarticulo'];
        $cantidad = (int) $detalle['cantidad'];

        // Bloquear artículo durante la operación.
        DB::table('articulos')
            ->where('id', $idarticulo)
            ->lockForUpdate()
            ->first();

        // Obtener y bloquear los registros de inventario.
        $inventarios = Inventario::where('idalmacen', $idalmacen)
            ->where('idarticulo', $idarticulo)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $inventario = $inventarios->first();

        // Stock total antes del movimiento.
        $stockAnterior = (int) $inventarios->sum('saldo_stock');

        // Si no existe inventario, crearlo.
        if (!$inventario) {

            $inventario = new Inventario();
            $inventario->idalmacen = $idalmacen;
            $inventario->idarticulo = $idarticulo;
            $inventario->fecha_vencimiento = '2099-12-31';

            if ($detalle['tipo_movimiento'] === 'entrada') {

                $inventario->saldo_stock = $cantidad;
                $inventario->cantidad = $cantidad;

            } else {

                // Conservamos tu comportamiento actual.
                $inventario->saldo_stock = -$cantidad;
                $inventario->cantidad = -$cantidad;
            }

        } else {

            // Actualizar inventario existente.
            if ($detalle['tipo_movimiento'] === 'entrada') {

                $inventario->saldo_stock += $cantidad;

            } else {

                $inventario->saldo_stock -= $cantidad;
            }
        }

        $inventario->save();

        // Obtener stock total después del movimiento.
        $stockActual = (int) Inventario::where('idalmacen', $idalmacen)
            ->where('idarticulo', $idarticulo)
            ->lockForUpdate()
            ->get()
            ->sum('saldo_stock');

        // Determinar tipo de movimiento.
        $tipoMovimiento = $detalle['tipo_movimiento'] === 'entrada'
            ? MovimientoInventario::AJUSTE_ENTRADA
            : MovimientoInventario::AJUSTE_SALIDA;

        // Registrar movimiento histórico.
        MovimientoInventarioService::registrar(
            $idarticulo,
            $idalmacen,
            $tipoMovimiento,
            $stockAnterior,
            $stockActual,
            'AJUSTE_INVENTARIO',
            $detalle['idajuste'],
            null,
            'Ajuste desde control de inventario. Detalle control ID: '
            . $detalle['iddetalle_control']
        );
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

        // ROL USUARIO LOGUEADO
        $rolUsuario = Auth::user()->idrol;

        // AGREGAR STOCK ACTUAL
        foreach ($control->detalles as $detalle) {

            $inventario = Inventario::where(
                'idalmacen',
                $control->idalmacen
            )
                ->where('idarticulo', $detalle->idarticulo)
                ->first();

            $detalle->stock_actual = $inventario
                ? $inventario->saldo_stock
                : 0;
        }

        $total = $control->detalles->count();

        $verificados = $control->detalles->where('estado', 2)->count();

        $pendientes = $control->detalles->where('estado', 1)->count();

        $anulados = $control->detalles->where('estado', 0)->count();

        $estadoGeneral = ($pendientes == 0)
            ? 'AJUSTADO'
            : 'PENDIENTE';

        $pdf = PDF::loadView('pdf.control_inventario', compact(
            'control',
            'total',
            'verificados',
            'pendientes',
            'anulados',
            'estadoGeneral',
            'rolUsuario'
        ));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download(
            'control_inventario_' . $control->id . '.pdf'
        );
    }

    public function exportExcel($id)
    {
        return Excel::download(new ControlInventarioExport($id), 'control_inventario.xlsx');
    }
}
