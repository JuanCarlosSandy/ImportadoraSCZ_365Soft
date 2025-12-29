<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FacturaInstitucional extends Model
{
    protected $fillable =[
        'idcliente',
        'idventainstitucional',
        'numeroFactura',
        'cuf',
        'fechaEmision',
        'codigoMetodoPago',
        'montoTotal',
        'montoTotalSujetoIva',
        'descuentoAdicional',
        'productos',
        'estado'
    ];

    public function persona(){
        return $this->belongsTo('App\Persona');
    }

    public function ventas_institucionales(){
        return $this->belongsTo('App\VentasInstitucionales');
    }
}
