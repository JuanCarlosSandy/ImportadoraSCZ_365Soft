<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticulosVariable extends Model
{
    protected $table = 'articulo_variables';
    protected $fillable = [
        'idvariable', 
        'idarticulo',
        'cantidad',
        'precio',
        'descuento'
    ];
    public $timestamps = false;
}
