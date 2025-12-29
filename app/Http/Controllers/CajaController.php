<?php

namespace App\Http\Controllers;

use App\Caja;
use App\TransaccionesCaja;
use Illuminate\Http\Request;
use App\ArqueoCaja;
use App\User;
use Illuminate\Support\Facades\DB;
use PDF;
class CajaController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

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
                'total'        => $cajas->total(),
                'current_page' => $cajas->currentPage(),
                'per_page'     => $cajas->perPage(),
                'last_page'    => $cajas->lastPage(),
                'from'         => $cajas->firstItem(),
                'to'           => $cajas->lastItem(),
            ],
            'cajas' => $cajas
        ];
    }


    public function store(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $caja = new Caja();
        $caja->idsucursal = \Auth::user()->idsucursal;
        $caja->idusuario = \Auth::user()->id;
        $caja->fechaApertura = now()->setTimezone('America/La_Paz');
        $caja->saldoInicial = $request->saldoInicial;
        $caja->saldoCaja = $request->saldoInicial;
        
        $caja->estado = '1';
        $caja->save();
    }

    public function depositar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $caja = Caja::findOrFail($request->id);
        $caja->depositos = ($request->depositos)+($caja->depositos);
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
        if (!$request->ajax()) return redirect('/');

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
        if (!$request->ajax()) return redirect('/');
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
    if (!$request->ajax()) return redirect('/');

    $caja = Caja::findOrFail($request->id);
    $caja->fechaCierre = now()->setTimezone('America/La_Paz');
    $caja->estado = '0';

    // Calcular diferencia entre saldo en caja y saldo declarado en el cierre
    $diferencia = $request->saldoFaltante - $caja->saldototalventas;

    if ($diferencia > 0) {
        // Si el saldo declarado es mayor al saldo en caja → HAY FALTANTE
        $caja->saldoFaltante = 0; // Asegurar que no se registre faltante
        $caja->saldoSobrante = abs($diferencia);
    } elseif ($diferencia < 0) {
        // Si el saldo en caja es mayor al declarado → HAY SOBRANTE
        $caja->saldoSobrante = 0; // Asegurar que no se registre sobrante
        $caja->saldoFaltante = $diferencia;

    } else {
        // Si no hay diferencia, ambos quedan en 0
        $caja->saldoFaltante = 0;
        $caja->saldoSobrante = 0;
    }

    $caja->save();
}

public function generarReporte($idCaja, Request $request)
{
    $tipo = $request->query('tipo', 'completo'); // efectivo, banco o completo
    $caja = Caja::findOrFail($idCaja);
    $idsucursal = $caja->idsucursal;

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
        'tipo' => 'saldo_inicial'
    ];

    // =========================
    // VENTAS AL CONTADO
    // =========================
    $ventas = \DB::table('ventas')
        ->where('idtipo_venta', 1)
        ->where('idsucursal', $idsucursal)
        ->where('estado', '<>', 0) // 👈 solo ventas activas
        ->get();

    foreach ($ventas as $venta) {
        $tipo_pago = $venta->idtipo_pago == 1 ? 'efectivo' : ($venta->idtipo_pago == 7 ? 'banco' : 'otros');
        $banco = null;

        if ($venta->idtipo_pago == 7 && $venta->idbanco) {
            $b = \DB::table('bancos')->find($venta->idbanco);
            if ($b) $banco = ['id' => $b->id, 'nombre_banco' => $b->nombre_banco];
        }

        $cliente = \DB::table('personas')->find($venta->idcliente);
        $nombreCliente = $cliente->nombre ?? 'Cliente desconocido';

        if ($tipo === 'efectivo' && $tipo_pago !== 'efectivo') continue;
        if ($tipo === 'banco' && $tipo_pago !== 'banco') continue;

        $historial[] = [
            'fecha' => $venta->fecha_hora,
            'detalle' => 'Cobro Venta N° ' . $venta->num_comprobante . ' - ' . $nombreCliente,
            'tipo_pago' => $tipo_pago,
            'monto' => floatval($venta->total),
            'idbanco' => $banco['id'] ?? null,
            'nombre_banco' => $banco['nombre_banco'] ?? null,
            'tipo' => 'venta'
        ];
    }

    // =========================
// CUOTAS DE CRÉDITO
// =========================
$cuotas = \DB::table('cuotas_credito')
    ->whereIn('idtipo_pago', [1, 7])
    ->where('idcaja', $caja->id)
    ->get();

foreach ($cuotas as $cuota) {

    $tipo_pago = $cuota->idtipo_pago == 1 ? 'efectivo' : 'banco';
    $banco = null;

    if ($cuota->idtipo_pago == 7 && $cuota->idbanco) {
        $b = \DB::table('bancos')->find($cuota->idbanco);
        if ($b) {
            $banco = [
                'id' => $b->id,
                'nombre_banco' => $b->nombre_banco
            ];
        }
    }

    // =========================
    // 👉 COBRO ADELANTADO (saldo a favor)
    // =========================
    if ($cuota->numero_cuota == 0 && is_null($cuota->idcredito)) {

        $cliente = \DB::table('personas')->find($cuota->idcliente);
        $nombreCliente = $cliente->nombre ?? 'Cliente desconocido';

        if ($tipo === 'efectivo' && $tipo_pago !== 'efectivo') continue;
        if ($tipo === 'banco' && $tipo_pago !== 'banco') continue;

        $historial[] = [
            'fecha' => $cuota->fecha_pago,
            'detalle' => 'Cobro Adelantado - ' . $nombreCliente,
            'tipo_pago' => $tipo_pago,
            'monto' => floatval($cuota->precio_cuota), // ✅ suma
            'idbanco' => $banco['id'] ?? null,
            'nombre_banco' => $banco['nombre_banco'] ?? null,
            'tipo' => 'cuota'
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

    if ($tipo === 'efectivo' && $tipo_pago !== 'efectivo') continue;
    if ($tipo === 'banco' && $tipo_pago !== 'banco') continue;

    $historial[] = [
        'fecha' => $cuota->fecha_pago,
        'detalle' => 'Cobro Cuota N° ' . $numComprobante . ' - ' . $nombreCliente,
        'tipo_pago' => $tipo_pago,
        'monto' => floatval($cuota->precio_cuota),
        'idbanco' => $banco['id'] ?? null,
        'nombre_banco' => $banco['nombre_banco'] ?? null,
        'tipo' => 'cuota'
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

    $tipo_pago = $trans->tipo_pago == 1 ? 'efectivo' : ($trans->tipo_pago == 7 ? 'banco' : 'otros');
    $banco = null;

    if ($trans->tipo_pago == 7 && $trans->idbanco) {
        $b = \DB::table('bancos')->find($trans->idbanco);
        if ($b) {
            $banco = [
                'id' => $b->id,
                'nombre_banco' => $b->nombre_banco
            ];
        }
    }

    if ($tipo === 'efectivo' && $tipo_pago !== 'efectivo') continue;
    if ($tipo === 'banco' && $tipo_pago !== 'banco') continue;

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
        'idbanco' => $banco['id'] ?? null,
        'nombre_banco' => $banco['nombre_banco'] ?? null,
        'tipo' => 'transaccion'
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
$historial = $historial->map(function($item) use (&$saldoActual) {

    // Determinar el monto a sumar o restar según el tipo
    $monto = $item['monto'];

if ($item['tipo'] === 'transaccion') {

    // 👉 Todo lo que DEBE RESTAR del saldo
    if (
        stripos($item['detalle'], 'egreso') !== false ||
        stripos($item['detalle'], 'gasto') !== false ||
        stripos($item['detalle'], 'Anulación de venta crédito') !== false
    ) {
        $monto = -abs($monto);
    } else {
        $monto = abs($monto);
    }
}

    // Saldo inicial
    if ($item['tipo'] === 'saldo_inicial') {
        $saldoActual = $monto;
    } else {
        $saldoActual += $monto;
    }

    $item['saldo_actual'] = $saldoActual;

    return $item;
});

$pdf = Pdf::loadView('pdf.caja', compact('caja', 'historial', 'tipo'))
          ->setPaper('letter', 'portrait'); // carta, vertical
          
return $pdf->download("reporte_caja_{$caja->id}.pdf");}




}
