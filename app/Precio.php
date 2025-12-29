<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Precio extends Model
{
    
    protected $table = 'precios';
    public $timestamps = false;

    protected $fillable = [
        'nombre_precio',
        'porcentaje'
    ];


    // Relación con el modelo Articulo
    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
