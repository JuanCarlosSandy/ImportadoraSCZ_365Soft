<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleControlInventario extends Model
{
     protected $table = 'detalle_controlinventario';

    protected $fillable = [
        'idcontrol',
        'idarticulo',
        'stocksistema',
        'stockfisico',
        'estado'
    ];

    // Relación: pertenece a control inventario
    public function control()
    {
        return $this->belongsTo(ControlInventario::class, 'idcontrol');
    }

    // Relación: pertenece a artículo
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo');
    }
}
