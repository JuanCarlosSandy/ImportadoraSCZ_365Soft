<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistorialInventario extends Model
{
    protected $table = 'historial_inventario';

    protected $fillable = [
        'idcaja',
        'idarticulo',
        'stock_historico'
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'idcaja');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo');
    }
}


