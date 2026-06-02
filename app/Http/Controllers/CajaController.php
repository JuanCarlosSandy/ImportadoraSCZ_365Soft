<?php

namespace App\Http\Controllers;

use App\Caja;
use App\TransaccionesCaja;
use Illuminate\Http\Request;
use App\ArqueoCaja;
use App\User;
use App\Venta;
use App\DetalleVenta;
use App\Inventario;
use App\HistorialInventario;
use Illuminate\Support\Facades\DB;
use PDF;
class CajaController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        $query = Caja::join('sucursales', 'cajas.idsucursal', '=', 'sucursales.id')
            ->join('users', 'cajas.idusuario', '=', 'users.id');

        if ($buscar == '') {
            $query->select(
                'cajas.id',
                'cajas.idsucursal',
                'sucursales.nombre as nombre_sucursal',
                'cajas.idusuario',
                'users.usuario as usuario',
                'cajas.fechaApertura',
                'cajas.fechaCierre',
                'saldoInicial',
                'depositos',
                'salidas',
                'ventas',
                'ventasContado',
                'ventasQR',
                'ventasTarjeta',
                'ventasCredito',
                'pagosEfectivoVentas',
                'pagosEfecivocompras',
                'compras',
                'comprasContado',
                'saldoFaltante',
                'saldoSobrante',
                'monto_arqueo',
                'PagoCuotaEfectivo',
                'saldoCaja',
                'saldototalventas',
                'estado',
                'cuotasventasCredito'
            );
        } else {
            $query->select(
                'cajas.id',
                'cajas.idsucursal',
                'sucursales.nombre as nombre_sucursal',
                'cajas.idusuario',
                'users.usuario as usuario',
                'cajas.fechaApertura',
                'cajas.fechaCierre',
                'cajas.saldoInicial',
                'cajas.depositos',
                'cajas.salidas',
                'cajas.ventas',
                'cajas.ventasContado',
                'cajas.ventasQR',
                'cajas.ventasTarjeta',
                'cajas.ventasCredito',
                'cajas.pagosEfectivoVentas',
                'cajas.pagosEfecivocompras',
                'cajas.compras',
                'cajas.comprasContado',
                'cajas.saldoFaltante',
                'cajas.saldoSobrante',
                'cajas.monto_arqueo',
                'cajas.PagoCuotaEfectivo',
                'cajas.saldoCaja',
                'cajas.saldototalventas',
                'cajas.estado',
                'cajas.cuotasventasCredito'
            )->where('cajas.' . $criterio, 'like', '%' . $buscar . '%');
        }

        // Condición para mostrar todas las sucursales si el rol es 4
        if (\Auth::user()->idrol != 4) {
            $query->where('cajas.idsucursal', '=', \Auth::user()->idsucursal);
        }

        $cajas = $query->orderBy('cajas.id', 'desc')->paginate(6);

        return [
            'pagination' => [
                'total' => $cajas->total(),
                'current_page' => $cajas->currentPage(),
                'per_page' => $cajas->perPage(),
                'last_page' => $cajas->lastPage(),
                'from' => $cajas->firstItem(),
                'to' => $cajas->lastItem(),
            ],
            'cajas' => $cajas
        ];
    }


    public function store(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $usuario = \Auth::user();
        $idSucursalAsignada = $usuario->idsucursal;

        if ($usuario->idrol == 4) {
            $request->validate([
                'idsucursal' => 'required|exists:sucursales,id',
            ], [
                'idsucursal.required' => 'El SuperAdmin debe especificar una sucursal.',
                'idsucursal.exists' => 'La sucursal seleccionada no es válida o no existe.'
            ]);
            $idSucursalAsignada = $request->idsucursal;
        }

        $caja = new Caja();
        $caja->idsucursal = $idSucursalAsignada;
        $caja->idusuario = $usuario->id;
        $caja->fechaApertura = now()->setTimezone('America/La_Paz');
        $caja->saldoInicial = $request->saldoInicial;
        $caja->saldoCaja = $request->saldoInicial;
        $caja->estado = '1';
        $caja->save();

        return response()->json(['message' => 'Caja registrada correctamente']);
    }

    public function depositar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $caja = Caja::findOrFail($request->id);
        $caja->depositos = ($request->depositos) + ($caja->depositos);
        $caja->saldoCaja += $request->depositos;
        $caja->saldototalventas += $request->depositos;
        $caja->save();

        $transacciones = new TransaccionesCaja();
        $transacciones->idcaja = $request->id;
        $transacciones->idusuario = \Auth::user()->id;
        $transacciones->fecha = now()->setTimezone('America/La_Paz');
        $transacciones->transaccion = $request->transaccion;
        $transacciones->importe = ($request->depositos);
        // 🔹 Lógica para tipo_pago y idbanco
        if (!empty($request->idbanco)) {
            $transacciones->tipo_pago = 7;      // Pago por banco
            $transacciones->idbanco = $request->idbanco;
        } else {
            $transacciones->tipo_pago = 1;      // Pago en efectivo
            $transacciones->idbanco = null;
        }
        $transacciones->save();
    }

    public function retirar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $caja = Caja::findOrFail($request->id);
        $caja->salidas += floatval($request->salidas);
        $caja->saldoCaja -= floatval($request->salidas);
        $caja->saldototalventas -= floatval($request->salidas);
        $caja->save();

        $transacciones = new TransaccionesCaja();
        $transacciones->idcaja = $request->id;
        $transacciones->idusuario = \Auth::user()->id;
        $transacciones->fecha = now()->setTimezone('America/La_Paz');
        $transacciones->transaccion = $request->transaccion;
        $transacciones->importe = floatval($request->salidas);

        // 🔹 Lógica para tipo_pago y idbanco
        if (!empty($request->idbanco)) {
            $transacciones->tipo_pago = 7;      // Pago por banco
            $transacciones->idbanco = $request->idbanco;
        } else {
            $transacciones->tipo_pago = 1;      // Pago en efectivo
            $transacciones->idbanco = null;
        }

        $transacciones->save();
    }

    public function arqueoCaja(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $arqueoCaja = new ArqueoCaja();
        $arqueoCaja->idcaja = $request->idcaja;
        $arqueoCaja->idusuario = \Auth::user()->id;
        $arqueoCaja->billete200 = $request->billete200;
        $arqueoCaja->billete100 = $request->billete100;
        $arqueoCaja->billete50 = $request->billete50;
        $arqueoCaja->billete20 = $request->billete20;
        $arqueoCaja->billete10 = $request->billete10;
        $arqueoCaja->moneda5 = $request->moneda5;
        $arqueoCaja->moneda2 = $request->moneda2;
        $arqueoCaja->moneda1 = $request->moneda1;
        $arqueoCaja->moneda050 = $request->moneda050;
        $arqueoCaja->moneda020 = $request->moneda020;
        $arqueoCaja->moneda010 = $request->moneda010;

        $arqueoCaja->save();
    }

    public function cerrar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $caja = Caja::findOrFail($request->id);
        $caja->fechaCierre = now()->setTimezone('America/La_Paz');
        $caja->estado = '0';

        // Guardar el monto de arqueo (el monto que realizas del arqueo)
        $caja->monto_arqueo = $request->saldoFaltante;

        // Calcular diferencia: monto arqueo (lo que contaste) - saldo caja (lo que debería haber en caja)
        $montoArqueo = floatval($request->saldoFaltante);
        $saldoCaja = floatval($caja->saldoCaja);
        $diferencia = $montoArqueo - $saldoCaja;

        if ($diferencia > 0) {
            // Si contaste más que lo que dice el sistema → HAY SOBRANTE
            $caja->saldoFaltante = 0; // Asegurar que no se registre faltante
            $caja->saldoSobrante = abs($diferencia);
        } elseif ($diferencia < 0) {
            // Si contaste menos que lo que dice el sistema → HAY FALTANTE
            $caja->saldoSobrante = 0; // Asegurar que no se registre sobrante
            $caja->saldoFaltante = abs($diferencia);

        } else {
            // Si no hay diferencia, ambos quedan en 0
            $caja->saldoFaltante = 0;
            $caja->saldoSobrante = 0;
        }

        $caja->save();

        // Obtener artículos únicos vendidos en la caja
        $articulosVendidos = DetalleVenta::join('ventas', 'ventas.id', '=', 'detalle_ventas.idventa')
            ->where('ventas.idcaja', $caja->id)
            ->select(
                'detalle_ventas.idarticulo',
                'ventas.idalmacen'
            )
            ->distinct()
            ->get();

        foreach ($articulosVendidos as $item) {

            $inventario = Inventario::where('idarticulo', $item->idarticulo)
                ->where('idalmacen', $item->idalmacen)
                ->first();

            HistorialInventario::create([
                'idcaja' => $caja->id,
                'idarticulo' => $item->idarticulo,
                'stock_historico' => $inventario ? $inventario->saldo_stock : 0
            ]);
        }
    }

    public function generarReporte($idCaja, Request $request)
    {
        $tipo = trim(strtolower($request->query('tipo', 'completo'))); // efectivo, qr o completo
        $caja = Caja::findOrFail($idCaja);
        $idsucursal = $caja->idsucursal;

        $stocksHistoricos = \DB::table('historial_inventario')
            ->where('idcaja', $caja->id)
            ->pluck('stock_historico', 'idarticulo')
            ->toArray();

        $historial = [];

        // =========================
        // SALDO INICIAL
        // =========================
        $historial[] = [
            'fecha' => $caja->fecha_apertura,
            'detalle' => 'Saldo Inicial',
            'tipo_pago' => 'efectivo',
            'monto' => floatval($caja->saldoInicial),
            'idbanco' => null,
            'nombre_banco' => null,
            'tipo' => 'saldo_inicial',
            'productos' => '',
        ];

        // =========================
        // VENTAS AL CONTADO
        // =========================
        $ventas = \DB::table('ventas')
            ->where('idtipo_venta', 1)
            ->where('idcaja', $caja->id)  // 🔥 FILTRAR POR CAJA ESPECÍFICA
            ->where('estado', '<>', 0) // 👈 solo ventas activas
            ->get();

        foreach ($ventas as $venta) {
            // =========================
            // OBTENER CODIGOS PRODUCTOS
            // =========================

            $productosVendidos = \DB::table('detalle_ventas as dv')
                ->join('articulos as a', 'a.id', '=', 'dv.idarticulo')
                ->where('dv.idventa', $venta->id)
                ->select(
                    'a.id',
                    'a.codigo',
                    'a.nombre',
                    'dv.cantidad'
                )
                ->get();

            $productosTexto = '';

            foreach ($productosVendidos as $producto) {

                $nombreCorto = mb_strimwidth($producto->nombre, 0, 20, '...');

                $productosTexto .=
                    $producto->codigo .
                    ' - ' .
                    $nombreCorto .
                    ' (x' .
                    $producto->cantidad .
                    ')' . "\n";
            }

            if ($caja->estado == 0) {

                $stockHistoricoTexto = '';

                foreach ($productosVendidos as $producto) {

                    $stockHistoricoTexto .=
                        ($stocksHistoricos[$producto->id] ?? 'N/D')
                        . "\n";
                }

            } else {

                $stockHistoricoTexto = 'Caja abierta todavía';
            }
            $tipo_pago = $venta->idtipo_pago == 1 ? 'efectivo' : ($venta->idtipo_pago == 7 ? 'qr' : ($venta->idtipo_pago == 13 ? 'compuesto' : 'otros'));

            $cliente = \DB::table('personas')->find($venta->idcliente);
            $nombreCliente = $cliente->nombre ?? 'Cliente desconocido';

            // 👉 Manejo especial para ventas compuestas
            if ($venta->idtipo_pago == 13) {
                // Si es compuesto
                if ($tipo === 'completo') {
                    // Reporte completo: mostrar la venta como compuesto sin desglosar
                    $historial[] = [
                        'fecha' => $venta->fecha_hora,
                        'detalle' => 'Venta N° ' . $venta->num_comprobante . ' - ' . $nombreCliente,
                        'tipo_pago' => 'compuesto',
                        'monto' => floatval($venta->total),
                        'idbanco' => null,
                        'nombre_banco' => null,
                        'tipo' => 'venta',
                        'productos' => $productosTexto,
                        'stock_historico' => $stockHistoricoTexto,
                    ];
                } elseif ($tipo === 'qr' && floatval($venta->monto_qr) > 0) {
                    // Solo QR
                    $historial[] = [
                        'fecha' => $venta->fecha_hora,
                        'detalle' => 'Venta N° ' . $venta->num_comprobante . ' - ' . $nombreCliente,
                        'tipo_pago' => 'qr',
                        'monto' => floatval($venta->monto_qr),
                        'idbanco' => null,
                        'nombre_banco' => null,
                        'tipo' => 'venta',
                        'productos' => $productosTexto,
                        'stock_historico' => $stockHistoricoTexto,
                    ];
                } elseif ($tipo === 'efectivo' && floatval($venta->monto_efectivo) > 0) {
                    // Solo Efectivo
                    $historial[] = [
                        'fecha' => $venta->fecha_hora,
                        'detalle' => 'Venta N° ' . $venta->num_comprobante . ' - ' . $nombreCliente,
                        'tipo_pago' => 'efectivo',
                        'monto' => floatval($venta->monto_efectivo),
                        'idbanco' => null,
                        'nombre_banco' => null,
                        'tipo' => 'venta',
                        'productos' => $productosTexto,
                        'stock_historico' => $stockHistoricoTexto,
                    ];
                }
            } else {
                // Ventas normales (no compuestas)
                if ($tipo !== 'completo' && $tipo_pago !== $tipo)
                    continue;

                $historial[] = [
                    'fecha' => $venta->fecha_hora,
                    'detalle' => 'Venta N° ' . $venta->num_comprobante . ' - ' . $nombreCliente,
                    'tipo_pago' => $tipo_pago,
                    'monto' => floatval($venta->total),
                    'idbanco' => null,
                    'nombre_banco' => null,
                    'tipo' => 'venta',
                    'productos' => $productosTexto,
                    'stock_historico' => $stockHistoricoTexto,
                ];
            }
        }

        // =========================
        // CUOTAS DE CRÉDITO
        // =========================
        $cuotas = \DB::table('cuotas_credito')
            ->whereIn('idtipo_pago', [1, 7])
            ->where('idcaja', $caja->id)
            ->get();

        foreach ($cuotas as $cuota) {

            $tipo_pago = $cuota->idtipo_pago == 1 ? 'efectivo' : 'qr';

            // =========================
            // 👉 COBRO ADELANTADO (saldo a favor)
            // =========================
            if ($cuota->numero_cuota == 0 && is_null($cuota->idcredito)) {

                $cliente = \DB::table('personas')->find($cuota->idcliente);
                $nombreCliente = $cliente->nombre ?? 'Cliente desconocido';

                // Filtrado por tipo de reporte
                if ($tipo !== 'completo' && $tipo_pago !== $tipo)
                    continue;

                $historial[] = [
                    'fecha' => $cuota->fecha_pago,
                    'detalle' => 'Cobro Adelantado - ' . $nombreCliente,
                    'tipo_pago' => $tipo_pago,
                    'monto' => floatval($cuota->precio_cuota), // ✅ suma
                    'idbanco' => null,
                    'nombre_banco' => null,
                    'tipo' => 'cuota',
                    'productos' => '',
                ];

                continue;
            }

            // =========================
            // 👉 COBRO NORMAL DE CUOTA
            // =========================
            $ventaRelacionada = \DB::table('ventas')
                ->where('id', $cuota->idcredito)
                ->where('estado', '<>', 0) // 👈 solo ventas activas
                ->first();
            // Si la venta está anulada, no se toma en cuenta la cuota
            if (!$ventaRelacionada) {
                continue;
            }
            $numComprobante = $ventaRelacionada->num_comprobante ?? 'N/A';
            $idCliente = $ventaRelacionada->idcliente ?? null;
            $cliente = \DB::table('personas')->find($idCliente);
            $nombreCliente = $cliente->nombre ?? 'Cliente desconocido';

            // Filtrado por tipo de reporte
            if ($tipo !== 'completo' && $tipo_pago !== $tipo)
                continue;

            $historial[] = [
                'fecha' => $cuota->fecha_pago,
                'detalle' => 'Cobro Cuota N° ' . $numComprobante . ' - ' . $nombreCliente,
                'tipo_pago' => $tipo_pago,
                'monto' => floatval($cuota->precio_cuota),
                'idbanco' => null,
                'nombre_banco' => null,
                'tipo' => 'cuota',
                'productos' => '',
            ];
        }

        // =========================
        // TRANSACCIONES DE CAJA
        // =========================
        $transacciones = \DB::table('transacciones_cajas')
            ->where('idcaja', $caja->id)
            ->where(function ($q) {
                $q->where('transaccion', '<>', 'Anulación de venta')
                    ->orWhere('transaccion', 'Anulación de venta crédito');
            })
            ->get();

        foreach ($transacciones as $trans) {

            $tipo_pago = $trans->tipo_pago == 1 ? 'efectivo' : ($trans->tipo_pago == 7 ? 'qr' : ($trans->tipo_pago == 13 ? 'compuesto' : 'otros'));

            // Filtrado por tipo de reporte
            if ($tipo !== 'completo' && $tipo_pago !== $tipo)
                continue;

            $monto = floatval($trans->importe);

            // 👉 Todo lo que debe RESTAR
            if (
                stripos($trans->transaccion, 'egreso') !== false ||
                stripos($trans->transaccion, 'gasto') !== false ||
                stripos($trans->transaccion, 'Anulación de venta crédito') !== false
            ) {
                $monto = -abs($monto);
            } else {
                $monto = abs($monto);
            }

            $historial[] = [
                'fecha' => $trans->fecha,
                'detalle' => $trans->transaccion,
                'tipo_pago' => $tipo_pago,
                'monto' => $monto,
                'idbanco' => null,
                'nombre_banco' => null,
                'tipo' => 'transaccion',
                'productos' => '',
            ];
        }

        // =========================
        // ORDENAR TODO POR FECHA
        // =========================
        $historial = collect($historial)->sortBy('fecha')->values();

        // =========================
        // CALCULAR SALDO ACUMULADO
        // =========================
        $saldoActual = 0;
        $historial = $historial->map(function ($item) use (&$saldoActual) {

            // Saldo inicial
            if ($item['tipo'] === 'saldo_inicial') {
                $saldoActual = $item['monto'];
            } elseif (stripos($item['detalle'], 'Anulación de venta') !== false) {
                // 👉 Las anulaciones NO afectan el saldo actual, se ignoran completamente
                // El saldo permanece igual
            } else {
                // Determinar el monto a sumar o restar según el tipo
                $monto = $item['monto'];

                if ($item['tipo'] === 'transaccion') {
                    // 👉 Todo lo que DEBE RESTAR del saldo
                    if (
                        stripos($item['detalle'], 'egreso') !== false ||
                        stripos($item['detalle'], 'gasto') !== false
                    ) {
                        $monto = -abs($monto);
                    } else {
                        $monto = abs($monto);
                    }
                }

                $saldoActual += $monto;
            }

            $item['saldo_actual'] = $saldoActual;

            return $item;
        });

        // =========================
        // RESUMEN DE CAJA
        // =========================

        // Cobros efectivo
        $ventasContado = collect($historial)
            ->whereIn('tipo', ['venta', 'cuota'])
            ->where('tipo_pago', 'efectivo')
            ->sum('monto');

        // Cobros QR
        $ventasQR = collect($historial)
            ->whereIn('tipo', ['venta', 'cuota'])
            ->where('tipo_pago', 'qr')
            ->sum('monto');

        // Cobros Totales
        $cobrosTotales = $ventasContado + $ventasQR;

        // Depósitos extras
        $depositos = collect($historial)
            ->where('tipo', 'transaccion')
            ->filter(function ($item) {
                return (
                    stripos($item['detalle'], 'deposito') !== false ||
                    stripos($item['detalle'], 'depósito') !== false
                );
            })
            ->sum('monto');

        // Salidas extras
        $salidas = collect($historial)
            ->where('tipo', 'transaccion')
            ->filter(function ($item) {
                return (
                    stripos($item['detalle'], 'egreso') !== false ||
                    stripos($item['detalle'], 'gasto') !== false
                );
            })
            ->sum('monto');

        $salidas = abs($salidas);

        // Saldo caja real guardado en la caja
        $saldoCaja = floatval($caja->saldoCaja ?? 0);

        // Monto arqueo
        $montoArqueo = floatval($caja->monto_arqueo ?? 0);

        // Datos reales guardados en caja
        $saldoFaltante = floatval($caja->saldoFaltante ?? 0);
        $saldoSobrante = floatval($caja->saldoSobrante ?? 0);

        $resumenCaja = [
            'fechaApertura' => $caja->fecha_apertura,
            'fechaCierre' => $caja->fecha_cierre,
            'saldoInicial' => floatval($caja->saldoInicial),
            'ventasContado' => $ventasContado,
            'ventasQR' => $ventasQR,
            'saldototalventas' => $cobrosTotales,
            'depositos' => $depositos,
            'salidas' => $salidas,
            'saldoCaja' => $saldoCaja,
            'saldoFaltante' => $saldoFaltante,
            'saldoSobrante' => $saldoSobrante,
            'monto_arqueo' => $montoArqueo,
        ];

        $pdf = Pdf::loadView('pdf.caja', compact('caja', 'historial', 'tipo', 'resumenCaja'))
            ->setPaper('letter', 'portrait'); // carta, vertical

        $tipoReporte = [
            'completo' => 'Completo',
            'qr' => 'QR',
            'efectivo' => 'Efectivo',
        ][$tipo] ?? 'Completo';

        $fechaGeneracion = date('Ymd_His');
        $nombreArchivo = "ReporteCaja_{$tipoReporte}_{$fechaGeneracion}.pdf";

        return $pdf->download($nombreArchivo);
    }
}
