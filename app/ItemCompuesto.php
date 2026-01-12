<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemCompuesto extends Model
{
    protected $table = 'itemcompuesto';

    protected $fillable = [
        'idarticulo',
        'iditem',
        'cantidad'
    ];

    // Relación con el artículo principal
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo');
    }

    // Relación con el artículo que es el item
    public function item()
    {
        return $this->belongsTo(Articulo::class, 'iditem');
    }
}
