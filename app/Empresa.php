<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'nombre',
        'direccion',
        'telefono',
        'email',
        'nit',
        'razonSocial',
        'token_siat',
        'logo',
        'licencia'
    ];

    public function sucursal()
    {
        return $this->belongsTo('App\Sucursales');
    }
}