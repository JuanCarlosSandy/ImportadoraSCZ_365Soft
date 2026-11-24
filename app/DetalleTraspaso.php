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
        'stock_actual_destino'
    ];

    protected $casts = [
        'idtraspaso' => 'integer',
        'idinventario' => 'integer',
        'cantidad_traspaso' => 'integer',
        'stock_actual_destino' => 'integer'
    ];

    public $timestamps = false;

    public function traspaso()
    {
        return $this->belongsTo(
            'App\Traspaso',
            'idtraspaso',
            'id'
        );
    }

    public function inventario()
    {
        return $this->belongsTo(
            'App\Inventario',
            'idinventario',
            'id'
        );
    }
}