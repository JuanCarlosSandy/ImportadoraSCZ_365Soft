<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $fillable = ['id','nombre','usuario','tipo_documento','num_documento','complemento_id','direccion','telefono','email','fotografia','estado','saldo_favor'];

    public function provedor()
    {
        return $this->hasOne('App\Proveedor');
    }

    public function user()
    {
        return $this->hasOne('App\User');
    }
 public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'idventa');
    }
    public function creditosVenta()
    {
        return $this->hasMany(CreditoVenta::class, 'idpersona');
    }

}
