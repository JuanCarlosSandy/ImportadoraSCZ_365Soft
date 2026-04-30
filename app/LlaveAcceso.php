<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LlaveAcceso extends Model
{
     protected $table = 'llave_acceso';

    protected $fillable = [
        'llave',
        'idusuario',
        'fechafin'
    ];

    // Relación: pertenece a un usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario');
    }
}
