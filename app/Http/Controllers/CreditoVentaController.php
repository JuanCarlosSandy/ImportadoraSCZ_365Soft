<?php

namespace App\Http\Controllers;
use PDF;
use App\Venta;
use Illuminate\Support\Facades\Log;
use App\CreditoVenta;
use App\CuotasCredito;
use App\TransaccionesCaja;
use App\Caja;
use App\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CreditoVentaController extends Controller
{

    public function generarReciboGeneral($idCredito)
    {
        $credito = CreditoVenta::with(['cliente', 'venta', 'cuotas'])
            ->findOrFail($idCredito);

        $todasLasCuotas = $credito->cuotas()->orderBy('fecha_pago')->get();

        $cuotasPagadas = $todasLasCuotas->filter(function ($item) {
            return $item->estado === 'Pagado';
        });

        $cuotasRestantes = $todasLasCuotas->filter(function ($item) {
            return $item->estado !== 'Pagado' && $item->fecha_pago >= Carbon::today();
        });

        $pdf = PDF::loadView('pdf.recibo_general_creditos', [
            'credito' => $credito,
            'cliente' => $credito->cliente,
            'venta' => $credito->venta,
            'cuotasPagadas' => $cuotasPagadas,
            'totalCuotas' => $todasLasCuotas->count(),
            'cuotasRestantes' => $cuotasRestantes
        ]);

        return $pdf->download('recibo_general_' . $idCredito . '.pdf');
    }
    public function generarReciboCuota($idCuota)
    {
        $cuota = CuotasCredito::with(['creditoVenta.cliente', 'creditoVenta.venta', 'creditoVenta.cuotas'])
            ->findOrFail($idCuota);



        $todasLasCuotas = $cuota->creditoVenta->cuotas()->orderBy('fecha_pago')->get();
        $numeroCuotaActual = $todasLasCuotas->search(function ($item) use ($cuota) {
            return $item->id === $cuota->id;
        }) + 1;

        $cuotasRestantes = $todasLasCuotas->filter(function ($item) {
            return $item->estado !== 'Pagado' && $item->fecha_pago >= Carbon::today();
        });

        $pdf = PDF::loadView('pdf.recibo_credito', [
            'cuota' => $cuota,
            'credito' => $cuota->creditoVenta,
            'cliente' => $cuota->creditoVenta->cliente,
            'venta' => $cuota->creditoVenta->venta,
            'numeroCuotaActual' => $numeroCuotaActual,
            'totalCuotas' => $todasLasCuotas->count(),
            'cuotasRestantes' => $cuotasRestantes
        ]);

        return $pdf->download('recibo_cuota_' . $idCuota . '.pdf');
    }

    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        $perPage = $request->input('per_page', 10);
        $filtroAvanzado = $request->filtro_avanzado;

        $creditosQuery = CreditoVenta::query()
            ->join('ventas', 'credito_ventas.idventa', '=', 'ventas.id')
            ->join('personas as clientes', 'credito_ventas.idcliente', '=', 'clientes.id')
            ->join('personas as vendedores', 'ventas.idusuario', '=', 'vendedores.id')
            ->select(
                'credito_ventas.id',
                'credito_ventas.numero_cuotas',
                'credito_ventas.tiempo_dias_cuota',
                'credito_ventas.total',
                'credito_ventas.estado',
                'credito_ventas.proximo_pago',

                'ventas.tipo_comprobante',
                'ventas.num_comprobante',
                'ventas.fecha_hora',
                'ventas.total as totalVenta',
                'clientes.nombre as nombre_cliente',
                'vendedores.nombre as nombre_vendedor'
            );


        // Aplicar filtro de búsqueda
        if ($buscar && $criterio) {
            if ($criterio === 'nombre_cliente') {
                $creditosQuery->where('clientes.nombre', 'like', '%' . $buscar . '%');
            } elseif ($criterio === 'nombre_vendedor') {
                $creditosQuery->where('vendedores.nombre', 'like', '%' . $buscar . '%');
            } elseif ($criterio === 'proximos_pago') {
                // Lógica para manejar la búsqueda por pagos cercanos
            }
        }
        Log::info($filtroAvanzado);
        // Aplicar filtro avanzado
        if ($filtroAvanzado) {
            if ($filtroAvanzado === 'pagos_atrasados') {
                // Calcular la fecha actual
                $fechaActual = now();

                // Aplicar el filtro para los pagos que ya vencieron
                $creditosQuery->where('credito_ventas.proximo_pago', '<', $fechaActual)
                    ->where('credito_ventas.estado', '!=', 'Completado');
            } elseif ($filtroAvanzado === 'pagos_hoy') {
                // Calcular la fecha actual
                $fechaActual = now();

                // Aplicar el filtro para los pagos que deben realizarse hoy
                $creditosQuery->whereDate('credito_ventas.proximo_pago', $fechaActual)
                    ->where('credito_ventas.estado', '!=', 'Completado');
            } elseif ($filtroAvanzado === 'pagos_cercanos') {
                $fechaActual = now();

                // Definir el rango de días para considerar como "cercanos"
                $diasCercanos = 7; // Por ejemplo, considerar los pagos de los próximos 7 días como cercanos

                // Calcular la fecha límite para los pagos cercanos
                $fechaLimite = $fechaActual->copy()->addDays($diasCercanos);

                // Aplicar el filtro para los pagos que están dentro del rango de días cercanos
                $creditosQuery->where('credito_ventas.proximo_pago', '<=', $fechaLimite)
                    ->where('credito_ventas.estado', '!=', 'Completado');
            } elseif ($filtroAvanzado === 'pagos_completados') {
                // Aplicar el filtro para los pagos que ya están completados
                $creditosQuery->where('credito_ventas.estado', '=', 'Completado');
            }
        }

        $creditosPaginated = $creditosQuery->orderBy('credito_ventas.id', 'desc')
            ->paginate($perPage);

        return [
            'pagination' => [
                'total' => $creditosPaginated->total(),
                'current_page' => $creditosPaginated->currentPage(),
                'per_page' => $creditosPaginated->perPage(),
                'last_page' => $creditosPaginated->lastPage(),
                'from' => $creditosPaginated->firstItem(),
                'to' => $creditosPaginated->lastItem(),
            ],
            'creditos' => $creditosPaginated
        ];
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'idventa' => 'required|integer|exists:ventas,id',
                'idcliente' => 'required|integer|exists:personas,id',
                'cuotas' => 'required|array|min:1',
                'cuotas.*.fecha_pago' => 'required|date',
                'cuotas.*.precio_cuota' => 'required|numeric|min:0',
                'cuotas.*.saldo_restante' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'estado' => 'required|string|in:activo,inactivo'
            ]);

            $credito = new CreditoVenta();
            $credito->idventa = $request->idventa;
            $credito->idcliente = $request->idcliente;
            $credito->numero_cuotas = count($request->cuotas);
            $credito->tiempo_dias_cuota = $request->tiempo_dias_cuota;
            $credito->total = $request->total;
            $credito->estado = $request->estado;
            $credito->save();
            foreach ($request->cuotas as $cuotaData) {
                $cuota = new CuotasCredito();
                $cuota->idcredito = $credito->id;
                $cuota->fecha_pago = $cuotaData['fecha_pago'];
                $cuota->fecha_cancelado = null;
                $cuota->precio_cuota = $cuotaData['precio_cuota'];
                $cuota->total_cancelado = 0;
                $cuota->saldo_restante = $cuotaData['saldo_restante'];
                $cuota->estado = $cuotaData['estado'];
                $cuota->save();
            }
            DB::commit();

            return $credito;
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages(['error' => 'Error al procesar la solicitud. Inténtalo de nuevo más tarde.']);
        }
    }

    public function obtenerCuotasCredito(Request $request)
    {
        try {
            $request->validate([
                'id_credito' => 'required|integer|exists:credito_ventas,id',
            ]);

            $idCredito = $request->id_credito;

            $cuotas = CuotasCredito::where('idcredito', $idCredito)
                ->leftJoin('personas', 'cuotas_credito.idcobrador', '=', 'personas.id')
                ->select('cuotas_credito.*', DB::raw('IFNULL(personas.nombre, "Pendiente") as nombre_cobrador'))
                ->get();

            return $cuotas;
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['error' => 'Error al obtener las cuotas de crédito. Inténtalo de nuevo más tarde.']);
        }
    }


    public function registrarPagoCuota(Request $request)
    {
        try {
            DB::beginTransaction();

            if (!$this->validarCajaAbierta()) {
                return ['id' => -1, 'caja_validado' => 'Debe tener una caja abierta'];
            }

            $request->validate([
                'id_credito' => 'required|integer|exists:credito_ventas,id',
                'numero_cuota' => 'required|integer',
                'monto_pago' => 'required|numeric|min:0',
            ]);
            $idCredito = $request->id_credito;
            $numeroCuota = $request->numero_cuota;
            $montoPago = $request->monto_pago;
            $valorCuotaAnterior = $request->cuota_anterior;
            $credito = CreditoVenta::findOrFail($idCredito);

            $idVenta = $credito->idventa;
            $credito->total -= $montoPago;
            if ($credito->total <= 0) {
                Venta::where('id', $idVenta)->update(['estado' => 'Registrado']);
                $credito->estado = 'Completado';
            }
            $credito->save();

            $cuota = CuotasCredito::where('idcredito', $idCredito)
                ->where('numero_cuota', $numeroCuota)
                ->firstOrFail();

            $cuota->fecha_cancelado = now();
            $cuota->idcobrador = \Auth::user()->id;

            $cuota->estado = 'Pagado';
            $cuota->saldo_restante = $valorCuotaAnterior - $montoPago;
            $cuota->save();
            $this->actualizarCaja($request);
            DB::commit();

            return $cuota;
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages(['error' => 'Error al procesar la solicitud. Inténtalo de nuevo más tarde.']);
        }
    }
    private function validarCajaAbierta()
    {
        $ultimaCaja = Caja::latest()->first();
        return $ultimaCaja && $ultimaCaja->estado == '1';
    }

    private function actualizarCaja($request)
    {

        $ultimaCaja = Caja::latest()->first();

        if ($request->tipo_pago == 1) {
            // Actualizar caja en cuota y cuota efectivo
            $ultimaCaja->PagoCuotaEfectivo += $request->monto_pago;
            $ultimaCaja->saldoCaja += $request->monto_pago;

        }
        $ultimaCaja->cuotasventasCredito += $request->monto_pago;

        $ultimaCaja->save();
    }

    public function obtenerCreditoYCuotas(Request $request)
    {
        $creditoVenta = CreditoVenta::where('idventa', $request->idventa)->first();

        if (!$creditoVenta) {
            return response()->json(['error' => 'No se encontró ningún crédito de venta para el idventa proporcionado.'], 404);
        }

        $cuotasCredito = CuotasCredito::where('idcredito', $creditoVenta->id)->get();

        return response()->json([
            'creditoVenta' => $creditoVenta,
            'cuotasCredito' => $cuotasCredito
        ], 200);
    }

    public function pdf(Request $request, $id)
    {
        // Obtener la venta y los detalles del cliente
        $venta = CreditoVenta::join('personas', 'credito_ventas.idcliente', '=', 'personas.id')
            ->join('ventas', 'credito_ventas.idventa', '=', 'ventas.id')
            ->select(
                'credito_ventas.id',
                'credito_ventas.total',
                'personas.nombre',
                'personas.tipo_documento',
                'personas.num_documento',
                'personas.direccion',
                'personas.email',
                'personas.telefono',
                'ventas.created_at'
            )
            ->where('credito_ventas.id', '=', $id)
            ->first();

        Log::info("Estos son es de venta\n");
        Log::info($venta);


        // Obtener las cuotas pagadas
        $cuotasPagadas = CuotasCredito::where('idcredito', $id)
            ->where('estado', 'Pagado')
            ->select(
                'numero_cuota',
                'fecha_cancelado as fecha_pago',
                'precio_cuota as monto_pagado'
            )
            ->get();
        Log::info("Estos son las cuotas pagadas\n");
        Log::info($cuotasPagadas);

        // Obtener las cuotas pendientes
        $cuotasPendientes = CuotasCredito::where('idcredito', $id)
            ->where('estado', '!=', 'Pagado')
            ->select(
                'numero_cuota',
                'fecha_pago as fecha_vencimiento',
                'precio_cuota as monto_pendiente'
            )
            ->get();
        Log::info("Estos son las cuotas pendientes\n");
        Log::info($cuotasPendientes);

        // Calcular el total pagado
        $totalPagado = $cuotasPagadas->sum('monto_pagado');
        Log::info("Este es el total pagado\n");
        Log::info($totalPagado);


        // Generar el PDF usando la vista
        $pdf = \PDF::loadView('pdf.pagocuotas', [
            'venta' => $venta,
            'cuotasPagadas' => $cuotasPagadas,
            'cuotasPendientes' => $cuotasPendientes,
            'totalPagado' => $totalPagado
        ]);

        // Descargar el PDF
        return $pdf->download('comprobante_pago_' . $id . '.pdf');
    }
    public function actualizarCuotas(Request $request)
    {
        DB::beginTransaction();

        try {
            $usuario = \Auth::user();
            $cuotasInput = $request->cuotas;

            if (!$cuotasInput || count($cuotasInput) == 0) {
                return ['success' => false, 'message' => 'No se enviaron cuotas'];
            }

            $idcredito = $cuotasInput[0]['idcredito'];
            $venta = Venta::findOrFail($idcredito);
            $totalVenta = floatval($venta->total);

            $cuotasBD = CuotasCredito::where('idcredito', $idcredito)->get();
            $caja = Caja::where('idsucursal', $usuario->idsucursal)->latest()->first();

            foreach ($cuotasInput as $cuotaNueva) {

                $cuota = $cuotasBD->firstWhere('id', $cuotaNueva['id']);
                if (!$cuota)
                    continue;

                $montoViejo = floatval($cuota->precio_cuota);
                $tipoPagoViejo = intval($cuota->idtipo_pago);

                $montoNuevo = floatval($cuotaNueva['precio_cuota']);
                $tipoPagoNuevo = intval($cuotaNueva['idtipo_pago']);
                $bancoNuevo = $cuotaNueva['idbanco'] ?? null;

                if ($caja) {

                    // =========================
                    // CASO ESPECIAL GASTO
                    // =========================
                    if ($tipoPagoViejo == 4) {
                        $diferencia = $montoNuevo - $montoViejo;

                        // Ajustar caja
                        $caja->salidas += $diferencia;            // si monto aumenta, sale más dinero
                        $caja->saldototalventas -= $diferencia;   // refleja que sale dinero de caja

                        // Actualizar transacción existente
                        TransaccionesCaja::where('idcuota_credito', $cuota->id)
                            ->update([
                                'importe' => $montoNuevo,
                                'idbanco' => $bancoNuevo,
                                'tipo_pago' => $bancoNuevo ? 7 : 1
                            ]);
                            
                        $cuota->precio_cuota = $montoNuevo;
                        $cuota->idbanco = $bancoNuevo;
                        $cuota->save();

                        continue;
                    }

                    // =========================
                    // REVERTIR/Aplicar otros tipos de pago (1 y 7)
                    // =========================
                    if (in_array($tipoPagoViejo, [1, 7])) {
                        if ($tipoPagoViejo == 1) {
                            $caja->ventasContado -= $montoViejo;
                            $caja->saldototalventas -= $montoViejo;
                            $caja->saldoCaja -= $montoViejo;
                        } elseif ($tipoPagoViejo == 7) {
                            $caja->ventasQR -= $montoViejo;
                            $caja->saldototalventas -= $montoViejo;
                        }
                    }

                    if (in_array($tipoPagoNuevo, [1, 7])) {
                        if ($tipoPagoNuevo == 1) {
                            $caja->ventasContado += $montoNuevo;
                            $caja->saldototalventas += $montoNuevo;
                            $caja->saldoCaja += $montoNuevo;
                        } elseif ($tipoPagoNuevo == 7) {
                            $caja->ventasQR += $montoNuevo;
                            $caja->saldototalventas += $montoNuevo;
                        }
                    }
                }

                // =========================
                // GUARDAR CUOTA
                // =========================
                $cuota->precio_cuota = $montoNuevo;
                $cuota->idtipo_pago = $tipoPagoNuevo;
                $cuota->idbanco = $bancoNuevo;
                $cuota->fecha_pago = $cuotaNueva['fecha_pago'] ?? $cuota->fecha_pago;
                $cuota->save();
            }

            if ($caja)
                $caja->save();


            // =========================
            // RECÁLCULO GLOBAL
            // =========================
            $totalGastos = CuotasCredito::where('idcredito', $idcredito)
                ->where('idtipo_pago', 4)
                ->sum('precio_cuota');

            $totalPagos = CuotasCredito::where('idcredito', $idcredito)
                ->whereIn('idtipo_pago', [1, 7])
                ->sum('precio_cuota');

            $totalDescuentos = CuotasCredito::where('idcredito', $idcredito)
                ->where('idtipo_pago', 5)
                ->sum('descuento');

            $saldo = ($totalVenta + $totalGastos) - $totalPagos - $totalDescuentos;

            $ultimaCuota = CuotasCredito::where('idcredito', $idcredito)
                ->orderByDesc('numero_cuota')
                ->first();

            if ($ultimaCuota) {
                $ultimaCuota->saldo_restante = $saldo;

                if ($saldo < 0) {
                    $ultimaCuota->estado = 'Saldo a Favor';
                } elseif ($saldo == 0) {
                    $ultimaCuota->estado = 'Cancelado';
                } else {
                    $ultimaCuota->estado = 'Pendiente';
                }

                $ultimaCuota->save();
            }

            $venta->estado = ($saldo <= 0) ? 1 : 2;
            $venta->save();
            $this->actualizarSaldoFavorPersona($venta, $saldo);

            DB::commit();

            return ['success' => true, 'message' => 'Cuotas actualizadas correctamente'];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
private function actualizarSaldoFavorPersona($venta, $saldo)
{
    $persona = Persona::find($venta->idcliente);
    if (!$persona) return;

    if ($saldo < 0) {
        // Tiene saldo a favor (se guarda en positivo)
        $persona->saldo_favor = abs($saldo);
    } else {
        // Ya no tiene saldo a favor
        $persona->saldo_favor = 0;
    }

    $persona->save();
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
     * Eliminar una cuota y descontar de caja según el tipo de pago
     */
    public function eliminarCuota(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'idcuota' => 'required|integer|exists:cuotas_credito,id'
            ]);

            $cuota = CuotasCredito::findOrFail($request->idcuota);
            $idcredito = $cuota->idcredito;
            $montoCuota = floatval($cuota->precio_cuota);
            $tipoPago = intval($cuota->idtipo_pago);

            // No permitir eliminar cuotas de liquidación (idtipo_pago = 5)
            if ($tipoPago === 5) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar una cuota de liquidación.'
                ];
            }

            // =====================================================
            // VERIFICAR SI EL SALDO A FAVOR YA FUE UTILIZADO
            // =====================================================
            $venta = Venta::find($idcredito);
            if (!$venta) {
                return [
                    'success' => false,
                    'message' => 'No se encontró la venta asociada'
                ];
            }
            
            $persona = Persona::find($venta->idcliente);
            $saldoActualCredito = $this->calcularSaldoCredito($idcredito);
            
            // Si actualmente hay saldo a favor (saldo negativo)
            if ($saldoActualCredito < 0 && $persona) {
                $saldoFavorActual = abs($saldoActualCredito);
                $saldoFavorPersona = floatval($persona->saldo_favor ?? 0);
                
                // Si el saldo a favor en la persona es menor que el calculado,
                // significa que ya fue utilizado parcial o totalmente
                if ($saldoFavorPersona < $saldoFavorActual) {
                    $saldoUsado = $saldoFavorActual - $saldoFavorPersona;
                    DB::rollback();
                    return [
                        'success' => false,
                        'message' => "No se puede eliminar esta cuota. El saldo a favor de {$saldoFavorActual} ya fue utilizado parcialmente ({$saldoUsado}) en otra venta."
                    ];
                }
            }

            $usuario = \Auth::user();

            // =============================================
            // DESCONTAR DE CAJA SEGÚN TIPO DE PAGO
            // =============================================
            $ultimaCaja = Caja::where('idsucursal', $usuario->idsucursal)
                ->where('estado', '1')
                ->latest()
                ->first();

            if ($ultimaCaja) {

                // =============================================
// CASO ESPECIAL: ES UN GASTO (idtipo_pago = 4)
// =============================================
                if ($tipoPago == 4) {

                    // Ajustar caja
                    $ultimaCaja->salidas -= $montoCuota;
                    $ultimaCaja->saldoCaja += $montoCuota;
                    $ultimaCaja->saldototalventas += $montoCuota;
                    $ultimaCaja->save();

                    // Eliminar transacción de caja asociada a esta cuota
                    TransaccionesCaja::where('idcuota_credito', $cuota->id)->delete();

                    // Eliminar gasto (cuota)
                    $cuota->delete();

                    // 🔥 RECALCULAR CRÉDITO Y SALDO A FAVOR
                    $this->renumerarYRecalcularCuotas($idcredito, $persona, $saldoActualCredito);

                    DB::commit();

                    return [
                        'success' => true,
                        'message' => 'Gasto eliminado correctamente.',
                    ];
                }

                if ($tipoPago == 1) {
                    // Tipo pago 1 = Efectivo: restar de ventasContado
                    $ultimaCaja->ventasContado -= $montoCuota;
                    $ultimaCaja->saldoCaja -= $montoCuota;
                    $ultimaCaja->saldototalventas -= $montoCuota;
                } elseif ($tipoPago == 7) {
                    // Tipo pago 7 = QR/Banco: restar de ventasQR
                    $ultimaCaja->ventasQR -= $montoCuota;
                    $ultimaCaja->saldototalventas -= $montoCuota;
                }

                $ultimaCaja->save();
            }

            // =============================================
            // ELIMINAR LA CUOTA
            // =============================================
            $cuota->delete();

            // =============================================
            // RENUMERAR Y RECALCULAR SALDOS
            // =============================================
            $this->renumerarYRecalcularCuotas($idcredito, $persona, $saldoActualCredito);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Cuota eliminada correctamente.',
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error al eliminar cuota: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar la cuota.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Renumerar cuotas y recalcular saldos restantes
     * También actualiza el saldo_favor de la persona
     */
    private function renumerarYRecalcularCuotas($idcredito, $persona = null, $saldoAnterior = null)
    {
        // ============================
        // 1️⃣ RENÚMERAR CUOTAS
        // ============================
        $cuotas = CuotasCredito::where('idcredito', $idcredito)
            ->orderBy('fecha_pago', 'asc')
            ->get();

        $numero = 1;
        foreach ($cuotas as $c) {
            $c->numero_cuota = $numero++;
            $c->save();
        }

        // ============================
        // 2️⃣ OBTENER VENTA
        // ============================
        $venta = Venta::find($idcredito);
        if (!$venta)
            return;
        
        // Si no se pasó la persona, obtenerla
        if (!$persona) {
            $persona = Persona::find($venta->idcliente);
        }

        $totalVenta = floatval($venta->total);

        // ============================
        // 3️⃣ TOTALES REALES
        // ============================

        // Gastos
        $totalGastos = CuotasCredito::where('idcredito', $idcredito)
            ->where('idtipo_pago', 4)
            ->sum('precio_cuota');

        // Pagos
        $totalPagos = CuotasCredito::where('idcredito', $idcredito)
            ->whereIn('idtipo_pago', [1, 7])
            ->sum('precio_cuota');

        // Descuentos
        $totalDescuentos = CuotasCredito::where('idcredito', $idcredito)
            ->where('idtipo_pago', 5)
            ->sum('descuento');

        // ============================
        // 4️⃣ SALDO REAL (PUEDE SER NEGATIVO)
        // ============================
        $saldoReal = ($totalVenta + $totalGastos) - $totalPagos - $totalDescuentos;

        // =====================================================
        // 5️⃣ MANEJAR SALDO A FAVOR EN LA TABLA PERSONAS
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

        // ============================
        // 6️⃣ ACTUALIZAR ÚLTIMA CUOTA
        // ============================
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

        // ============================
        // 7️⃣ ESTADO DE LA VENTA
        // ============================
        if ($saldoReal <= 0) {
            $venta->estado = 1; // Cancelada
        } else {
            $venta->estado = 2; // Pendiente
        }

        $venta->save();
    }



}
