<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Oferta extends Model
{
    protected $table = 'ofertas';

    protected $fillable = [
        'codigo', 'precio', 'porcentaje', 'fecha_final', 'estado', 'tipo_promocion', 'nombre'
    ];
}
