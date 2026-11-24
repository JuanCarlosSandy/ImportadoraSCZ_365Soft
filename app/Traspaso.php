<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Traspaso extends Model
{
    protected $table = 'traspasos';

    protected $fillable = [
        'tipo_traspaso',
        'idusuario',
        'almacen_origen',
        'almacen_destino',
        'fecha_traspaso',
        'estado'
    ];

    protected $casts = [
        'idusuario' => 'integer',
        'estado' => 'integer'
    ];

    protected $dates = [
        'fecha_traspaso'
    ];

    public function usuario()
    {
        return $this->belongsTo(
            'App\User',
            'idusuario',
            'id'
        );
    }

    public function detalles()
    {
        return $this->hasMany(
            'App\DetalleTraspaso',
            'idtraspaso',
            'id'
        );
    }

    public function almacenOrigen()
    {
        return $this->belongsTo(
            'App\Almacen',
            'almacen_origen',
            'id'
        );
    }

    public function almacenDestino()
    {
        return $this->belongsTo(
            'App\Almacen',
            'almacen_destino',
            'id'
        );
    }
}