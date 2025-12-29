<?php

namespace App\Http\Controllers;

use App\CuotasCredito;
use App\Venta;
use App\Caja;
use App\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CuotasCreditoController extends Controller
{
    // Mostrar una lista de las cuotas de crédito
    public function index()
    {
        // Devolver todas las cuotas de crédito 
        $cuotas = CuotasCredito::all();
        return $cuotas;
    }

    // Almacenar una nueva cuota de crédito en la base de datos
    public function store(Request $request)
    {
        // Validar y guardar una nueva cuota de crédito en la base de datos
        $request->validate([
            'idcredito' => 'required|integer|exists:credito_ventas,id',
            'fecha_pago' => 'required|date',
            'fecha_cancelado' => 'nullable|date',
            'precio_cuota' => 'required|numeric|min:0',
            'total_cancelado' => 'required|numeric|min:0',
            'saldo_restante' => 'required|numeric|min:0', // Se cambió 'saldo' por 'saldo_restante'
            'estado' => 'required|string|in:pendiente,pagado,atrasado'
        ]);

        $cuota = new CuotasCredito();
        $cuota->idcredito = $request->idcredito;
        $cuota->fecha_pago = $request->fecha_pago;
        $cuota->fecha_cancelado = $request->fecha_cancelado;
        $cuota->precio_cuota = $request->precio_cuota;
        $cuota->total_cancelado = $request->total_cancelado;
        $cuota->saldo_restante = $request->saldo_restante; // Se cambió 'saldo' por 'saldo_restante'
        $cuota->estado = $request->estado;
        $cuota->save();

        return $cuota;
    }

    // Mostrar los detalles de una cuota de crédito específica
    public function show(CuotasCredito $cuota)
    {
        // Devolver la cuota de crédito solicitada 
        return $cuota;
    }

    // Actualizar una cuota de crédito existente en la base de datos
    public function update(Request $request, CuotasCredito $cuota)
    {
        // Validar y actualizar la cuota de crédito existente en la base de datos
        $request->validate([
            'idcredito' => 'required|integer|exists:credito_ventas,id',
            'fecha_pago' => 'required|date',
            'fecha_cancelado' => 'nullable|date',
            'precio_cuota' => 'required|numeric|min:0',
            'total_cancelado' => 'required|numeric|min:0',
            'saldo_restante' => 'required|numeric|min:0', // Se cambió 'saldo' por 'saldo_restante'
            'estado' => 'required|string|in:pendiente,pagado,atrasado'
        ]);

        $cuota->idcredito = $request->idcredito;
        $cuota->fecha_pago = $request->fecha_pago;
        $cuota->fecha_cancelado = $request->fecha_cancelado;
        $cuota->precio_cuota = $request->precio_cuota;
        $cuota->total_cancelado = $request->total_cancelado;
        $cuota->saldo_restante = $request->saldo_restante; // Se cambió 'saldo' por 'saldo_restante'
        $cuota->estado = $request->estado;
        $cuota->save();

        return $cuota;
    }

    // Eliminar una cuota de crédito existente de la base de datos
    public function destroy(CuotasCredito $cuota)
    {
        // Eliminar la cuota de crédito existente de la base de datos
        $cuota->delete();
        return $cuota;
    }
    public function actualizarCuota(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $cuota = CuotasCredito::findOrFail($id);
            $idcredito = $cuota->idcredito;
            
            // Obtener la venta y el cliente
            $venta = Venta::find($idcredito);
            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la venta asociada'
                ], 404);
            }
            
            $persona = Persona::find($venta->idcliente);
            
            // =====================================================
            // VERIFICAR SI EL SALDO A FAVOR YA FUE UTILIZADO
            // =====================================================
            // Obtener el saldo actual antes de modificar
            $saldoActualCredito = $this->calcularSaldoCredito($idcredito);
            
            // Si actualmente hay saldo a favor (saldo negativo)
            if ($saldoActualCredito < 0) {
                $saldoFavorActual = abs($saldoActualCredito);
                $saldoFavorPersona = floatval($persona->saldo_favor ?? 0);
                
                // Si el saldo a favor en la persona es menor que el calculado,
                // significa que ya fue utilizado parcial o totalmente
                if ($saldoFavorPersona < $saldoFavorActual) {
                    $saldoUsado = $saldoFavorActual - $saldoFavorPersona;
                    return response()->json([
                        'success' => false,
                        'message' => "No se puede modificar esta cuota. El saldo a favor de {$saldoFavorActual} ya fue utilizado parcialmente ({$saldoUsado}) en otra venta."
                    ], 400);
                }
            }
            
            // Obtener valores anteriores para ajuste de caja
            $montoViejo = floatval($cuota->precio_cuota);
            $tipoPagoViejo = intval($cuota->idtipo_pago);
            
            // Valores nuevos
            $montoNuevo = $request->has('precio_cuota') ? floatval($request->precio_cuota) : $montoViejo;
            $tipoPagoNuevo = $request->has('idtipo_pago') ? intval($request->idtipo_pago) : $tipoPagoViejo;

            $usuario = \Auth::user();
            $caja = Caja::where('idsucursal', $usuario->idsucursal)
                ->where('estado', '1')
                ->latest()
                ->first();

            // =========================
            // REVERTIR CAJA (SOLO PAGOS)
            // =========================
            if ($caja && in_array($tipoPagoViejo, [1, 7])) {
                if ($tipoPagoViejo == 1) {
                    $caja->ventasContado -= $montoViejo;
                    $caja->saldototalventas -= $montoViejo;
                    $caja->saldoCaja -= $montoViejo;
                }
                if ($tipoPagoViejo == 7) {
                    $caja->ventasQR -= $montoViejo;
                    $caja->saldototalventas -= $montoViejo;
                }
            }

            // =========================
            // APLICAR CAJA NUEVA
            // =========================
            if ($caja && in_array($tipoPagoNuevo, [1, 7])) {
                if ($tipoPagoNuevo == 1) {
                    $caja->ventasContado += $montoNuevo;
                    $caja->saldototalventas += $montoNuevo;
                    $caja->saldoCaja += $montoNuevo;
                }
                if ($tipoPagoNuevo == 7) {
                    $caja->ventasQR += $montoNuevo;
                    $caja->saldototalventas += $montoNuevo;
                }
            }

            if ($caja) $caja->save();

            // =========================
            // ACTUALIZAR CUOTA
            // =========================
            if ($request->has('fecha_pago')) {
                $cuota->fecha_pago = $request->fecha_pago;
            }
            if ($request->has('precio_cuota')) {
                $cuota->precio_cuota = $request->precio_cuota;
            }
            if ($request->has('idtipo_pago')) {
                $cuota->idtipo_pago = $request->idtipo_pago;
            }
            if ($request->has('idbanco')) {
                $cuota->idbanco = $request->idbanco;
            }
            $cuota->save();

            // =========================
            // RECÁLCULO GLOBAL DEL SALDO Y SALDO A FAVOR
            // =========================
            $this->recalcularSaldoCredito($idcredito, $persona, $saldoActualCredito);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuota actualizada correctamente',
                'cuota' => $cuota
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar cuota: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cuota: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular el saldo del crédito sin modificar nada
     */
    private function calcularSaldoCredito($idcredito)
    {
        $venta = Venta::find($idcredito);
        if (!$venta) return 0;

        $totalVenta = floatval($venta->total);

        $totalGastos = CuotasCredito::where('idcredito', $idcredito)
            ->where('idtipo_pago', 4)
            ->sum('precio_cuota');

        $totalPagos = CuotasCredito::where('idcredito', $idcredito)
            ->whereIn('idtipo_pago', [1, 7])
            ->sum('precio_cuota');

        $totalDescuentos = CuotasCredito::where('idcredito', $idcredito)
            ->where('idtipo_pago', 5)
            ->sum('descuento');

        return ($totalVenta + $totalGastos) - $totalPagos - $totalDescuentos;
    }

    /**
     * Recalcular el saldo restante del crédito después de modificar/eliminar cuotas
     * También actualiza el saldo_favor de la persona
     */
    private function recalcularSaldoCredito($idcredito, $persona = null, $saldoAnterior = null)
    {
        // Obtener la venta
        $venta = Venta::find($idcredito);
        if (!$venta) return;

        // Si no se pasó la persona, obtenerla
        if (!$persona) {
            $persona = Persona::find($venta->idcliente);
        }

        $totalVenta = floatval($venta->total);

        // Gastos (idtipo_pago = 4)
        $totalGastos = CuotasCredito::where('idcredito', $idcredito)
            ->where('idtipo_pago', 4)
            ->sum('precio_cuota');

        // Pagos efectivo y banco (idtipo_pago = 1, 7)
        $totalPagos = CuotasCredito::where('idcredito', $idcredito)
            ->whereIn('idtipo_pago', [1, 7])
            ->sum('precio_cuota');

        // Descuentos (idtipo_pago = 5)
        $totalDescuentos = CuotasCredito::where('idcredito', $idcredito)
            ->where('idtipo_pago', 5)
            ->sum('descuento');

        // Calcular saldo real (puede ser negativo = saldo a favor)
        $saldoReal = ($totalVenta + $totalGastos) - $totalPagos - $totalDescuentos;

        // =====================================================
        // MANEJAR SALDO A FAVOR EN LA TABLA PERSONAS
        // =====================================================
        if ($persona) {
            $saldoFavorAnterior = 0;
            $saldoFavorNuevo = 0;
            
            // Si antes había saldo a favor
            if ($saldoAnterior !== null && $saldoAnterior < 0) {
                $saldoFavorAnterior = abs($saldoAnterior);
            }
            
            // Si ahora hay saldo a favor
            if ($saldoReal < 0) {
                $saldoFavorNuevo = abs($saldoReal);
            }
            
            // Calcular la diferencia
            $diferenciaSaldoFavor = $saldoFavorNuevo - $saldoFavorAnterior;
            
            // Actualizar el saldo_favor de la persona
            $saldoFavorActual = floatval($persona->saldo_favor ?? 0);
            $nuevoSaldoFavor = $saldoFavorActual + $diferenciaSaldoFavor;
            
            // No permitir saldo negativo
            if ($nuevoSaldoFavor < 0) {
                $nuevoSaldoFavor = 0;
            }
            
            $persona->saldo_favor = $nuevoSaldoFavor;
            $persona->save();
        }

        // Actualizar la última cuota con el saldo real
        $ultimaCuota = CuotasCredito::where('idcredito', $idcredito)
            ->orderByDesc('numero_cuota')
            ->first();

        if ($ultimaCuota) {
            $ultimaCuota->saldo_restante = $saldoReal;

            if ($saldoReal == 0) {
                $ultimaCuota->estado = 'Cancelado';
            } elseif ($saldoReal < 0) {
                $ultimaCuota->estado = 'Saldo a Favor';
            } else {
                $ultimaCuota->estado = 'Pendiente';
            }

            $ultimaCuota->save();
        }

        // Actualizar estado de la venta
        if ($saldoReal <= 0) {
            $venta->estado = 1; // Cancelada/Pagada
        } else {
            $venta->estado = 2; // Pendiente
        }
        $venta->save();
    }
}
