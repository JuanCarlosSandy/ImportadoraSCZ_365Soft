<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';

    protected $fillable = [
        'idventa',
        'idarticulo',
        'cantidad',
        'precio',
        'descuento',
        'modo_venta'
    ];

    protected $casts = [
        'idventa' => 'integer',
        'idarticulo' => 'integer',
        'cantidad' => 'integer'
    ];

    public $timestamps = false;

    public function venta()
    {
        return $this->belongsTo(
            Venta::class,
            'idventa',
            'id'
        );
    }

    public function producto()
    {
        return $this->belongsTo(
            Articulo::class,
            'idarticulo',
            'id'
        );
    }
}