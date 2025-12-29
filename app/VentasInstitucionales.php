<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VentasInstitucionales extends Model
{
    protected $fillable =[
        'idventa',
        'estado'
    ];

    public function venta(){
        return $this->belongsTo('App\Venta', 'id');
    }

    public function variablesTemporales()
    {
        return $this->hasMany('App\VariableTemporal', 'idventainstitucional');
    }
}
