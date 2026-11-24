<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'idarticulo',
        'idalmacen',
        'idusuario',
        'tipo_movimiento',
        'cantidad',
        'stock_anterior',
        'stock_actual',
        'tipo_referencia',
        'idreferencia',
        'iddetalle_referencia',
        'observacion',
        'fecha_movimiento'
    ];

    protected $casts = [
        'idarticulo' => 'integer',
        'idalmacen' => 'integer',
        'idusuario' => 'integer',
        'cantidad' => 'integer',
        'stock_anterior' => 'integer',
        'stock_actual' => 'integer',
        'idreferencia' => 'integer',
        'iddetalle_referencia' => 'integer'
    ];

    protected $dates = [
        'fecha_movimiento'
    ];

    // Tipos de movimientos

    const STOCK_INICIAL = 'STOCK_INICIAL';

    const COMPRA = 'COMPRA';
const ANULACION_COMPRA = 'ANULACION_COMPRA';

const ACTUALIZACION_COMPRA_ENTRADA = 'ACTUALIZACION_COMPRA_ENTRADA';
const ACTUALIZACION_COMPRA_SALIDA = 'ACTUALIZACION_COMPRA_SALIDA';

    const VENTA = 'VENTA';
    const ANULACION_VENTA = 'ANULACION_VENTA';

    const AJUSTE_ENTRADA = 'AJUSTE_ENTRADA';
    const AJUSTE_SALIDA = 'AJUSTE_SALIDA';

    const TRASPASO_ENTRADA = 'TRASPASO_ENTRADA';
    const TRASPASO_SALIDA = 'TRASPASO_SALIDA';

    const ANULACION_TRASPASO_ENTRADA = 'ANULACION_TRASPASO_ENTRADA';
    const ANULACION_TRASPASO_SALIDA = 'ANULACION_TRASPASO_SALIDA';

    public function articulo()
    {
        return $this->belongsTo(
            'App\Articulo',
            'idarticulo',
            'id'
        );
    }

    public function almacen()
    {
        return $this->belongsTo(
            'App\Almacen',
            'idalmacen',
            'id'
        );
    }

    public function usuario()
    {
        return $this->belongsTo(
            'App\User',
            'idusuario',
            'id'
        );
    }
}