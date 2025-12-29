<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VariableTemporal extends Model
{
    protected $fillable =[
        'idventainstitucional', 
        'nombre'
    ];

    public function ventaInstitucional()
    {
        return $this->belongsTo('App\VentasInstitucionales', 'id');
    }
}
