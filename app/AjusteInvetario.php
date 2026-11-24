<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjusteInvetario extends Model
{
    protected $table = 'ajuste_invetarios';

    protected $fillable = [
        'producto',
        'almacen',
        'cantidad',
        'tipo_movimiento',
        'idtipobajas'
    ];

    protected $casts = [
        'producto' => 'integer',
        'cantidad' => 'integer',
        'idtipobajas' => 'integer'
    ];

    public function articulo()
    {
        return $this->belongsTo(
            'App\Articulo',
            'producto',
            'id'
        );
    }

    public function almacenRelacionado()
    {
        return $this->belongsTo(
            'App\Almacen',
            'almacen',
            'id'
        );
    }
}