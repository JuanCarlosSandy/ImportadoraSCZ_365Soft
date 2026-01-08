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
    public $timestamps = false;

    // 🔹 Relación con Ingreso
    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'idingreso');
    }

    // 🔹 Relación con Articulo
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo');
    }
}
