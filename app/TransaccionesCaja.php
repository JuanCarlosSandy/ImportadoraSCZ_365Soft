<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransaccionesCaja extends Model
{
    protected $fillable = [
        'idcaja',
        'idusuario',
        'fecha',
        'transaccion',
        'importe',
        'tipo_pago',
        'idbanco',
        'idcuota_credito'
    ];

    public function caja(){
        return $this->belongsTo('App\Caja');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }
}
