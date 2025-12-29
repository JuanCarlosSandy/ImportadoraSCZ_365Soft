<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class tipoVenta extends Model
{
    //
    protected $table = 'tipo_ventas';
    //protected $fillable = ['id', 'nombre_tipo_ventas', 'descripcion_tipo_ventas', 'condicion'];
    protected $fillable = ['id', 'nombre_tipo_ventas'];
}
