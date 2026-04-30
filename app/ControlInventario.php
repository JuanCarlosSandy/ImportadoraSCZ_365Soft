<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ControlInventario extends Model
{
    protected $table = 'controlinventario';

    protected $fillable = [
        'idusuario',
        'fechahora',
        'estado',
        'idalmacen'
    ];

    // Relación: pertenece a usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario');
    }

    // Relación: pertenece a almacén
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen');
    }

    // Relación: tiene muchos detalles
    public function detalles()
    {
        return $this->hasMany(DetalleControlInventario::class, 'idcontrol');
    }
}
