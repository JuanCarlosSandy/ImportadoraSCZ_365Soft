<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    protected $fillable =[
        'idempresa', 'nombre', 'pais', 'simbolo', 'tipo_cambio', 'activo'
    ];
    public function empresa(){
        return $this->belongsTo('App\Empresa');
    }
}
