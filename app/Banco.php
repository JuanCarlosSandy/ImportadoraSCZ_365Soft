<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $table = 'bancos';

    protected $fillable = [
        'nombre_cuenta',

        'nombre_banco',
        'numero_cuenta',
        'tipo_cuenta'
    ];


}
