<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditoVenta extends Model
{
    protected $table = 'credito_ventas';
    protected $fillable = [
        'idventa', 
        'idpersona',
        'numero_cuotas',
        'tiempo_dias_cuota',
        'estado'
    ];
    public $timestamps = false;

    // Agregar estas relaciones
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'idventa');
    }

    public function cliente()
    {
        return $this->belongsTo(Persona::class, 'idcliente');
    }

    public function cuotas()
    {
        return $this->hasMany(CuotasCredito::class, 'idcredito');
    }
}