<?php

namespace App\Services;

use App\Inventario;
use App\MovimientoInventario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

class MovimientoInventarioService
{
    /**
     * Obtener el stock total de un producto en un almacén.
     */
    public static function obtenerStock($idarticulo, $idalmacen)
    {
        return (int) Inventario::where('idarticulo', $idarticulo)
            ->where('idalmacen', $idalmacen)
            ->sum('saldo_stock');
    }

    /**
     * Registrar un movimiento de inventario.
     *
     * Este método no modifica la tabla inventarios.
     * Solamente registra el movimiento realizado.
     */
    public static function registrar(
        $idarticulo,
        $idalmacen,
        $tipoMovimiento,
        $stockAnterior,
        $stockActual,
        $tipoReferencia = null,
        $idReferencia = null,
        $idDetalleReferencia = null,
        $observacion = null,
        $idusuario = null,
        $fechaMovimiento = null
    ) {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException(
                'El movimiento debe registrarse dentro de una transacción.'
            );
        }

        $stockAnterior = (int) $stockAnterior;
        $stockActual = (int) $stockActual;

        // Calcular automáticamente la diferencia de stock
        $cantidad = $stockActual - $stockAnterior;

       $tiposEntrada = [
    MovimientoInventario::COMPRA,
    MovimientoInventario::ANULACION_VENTA,
    MovimientoInventario::AJUSTE_ENTRADA,
    MovimientoInventario::TRASPASO_ENTRADA,
    MovimientoInventario::ANULACION_TRASPASO_SALIDA,
    MovimientoInventario::ACTUALIZACION_COMPRA_ENTRADA
];

       $tiposSalida = [
    MovimientoInventario::VENTA,
    MovimientoInventario::ANULACION_COMPRA,
    MovimientoInventario::AJUSTE_SALIDA,
    MovimientoInventario::TRASPASO_SALIDA,
    MovimientoInventario::ANULACION_TRASPASO_ENTRADA,
    MovimientoInventario::ACTUALIZACION_COMPRA_SALIDA
];

        $tiposPermitidos = array_merge(
            $tiposEntrada,
            $tiposSalida,
            [MovimientoInventario::STOCK_INICIAL]
        );

        if (!in_array($tipoMovimiento, $tiposPermitidos, true)) {
            throw new InvalidArgumentException(
                'Tipo de movimiento de inventario no válido.'
            );
        }

        // Validar que las entradas incrementen el stock
        if (in_array($tipoMovimiento, $tiposEntrada, true)
            && $cantidad <= 0) {

            throw new InvalidArgumentException(
                'Un movimiento de entrada debe incrementar el stock.'
            );
        }

        // Validar que las salidas disminuyan el stock
        if (in_array($tipoMovimiento, $tiposSalida, true)
            && $cantidad >= 0) {

            throw new InvalidArgumentException(
                'Un movimiento de salida debe disminuir el stock.'
            );
        }

        if ($tipoMovimiento === MovimientoInventario::STOCK_INICIAL
            && $stockAnterior !== 0) {

            throw new InvalidArgumentException(
                'El stock inicial debe partir de cero.'
            );
        }

        return MovimientoInventario::create([
            'idarticulo' => $idarticulo,
            'idalmacen' => $idalmacen,

            'idusuario' => $idusuario !== null
                ? $idusuario
                : Auth::id(),

            'tipo_movimiento' => $tipoMovimiento,

            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $stockActual,

            'tipo_referencia' => $tipoReferencia,
            'idreferencia' => $idReferencia,
            'iddetalle_referencia' => $idDetalleReferencia,

            'observacion' => $observacion,

            'fecha_movimiento' => $fechaMovimiento
                ?: Carbon::now()
        ]);
    }
}