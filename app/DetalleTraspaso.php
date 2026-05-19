<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleTraspaso extends Model
{
    protected $table = 'detalle_traspasos';
    protected $fillable = [
        'idtraspaso', 
        'idinventario',
        'cantidad_traspaso',
        'stock_actual_destino',
    ];
    public $timestamps = false;
}
