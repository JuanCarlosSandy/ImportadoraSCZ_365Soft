<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleIngreso extends Model
{
    protected $table = 'detalle_ingresos';

    protected $fillable = [
        'idingreso',
        'idarticulo',
        'cantidad',
        'precio',
        'descuento',
        'tipo_compra'
    ];

    protected $casts = [
        'idingreso' => 'integer',
        'idarticulo' => 'integer',
        'cantidad' => 'integer'
    ];

    public $timestamps = false;

    public function ingreso()
    {
        return $this->belongsTo(
            Ingreso::class,
            'idingreso',
            'id'
        );
    }

    public function articulo()
    {
        return $this->belongsTo(
            Articulo::class,
            'idarticulo',
            'id'
        );
    }
}