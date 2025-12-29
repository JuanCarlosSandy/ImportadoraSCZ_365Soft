<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PromocionArticulo extends Model
{
    //
    protected $table = 'promociones_articulos';

    protected $fillable = [
        'idpromociones',
        'idarticulos',
        'cantidad',
    ];
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulos', 'id');
    }
}
