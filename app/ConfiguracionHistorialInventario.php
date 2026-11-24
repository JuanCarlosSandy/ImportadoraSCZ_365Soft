<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionHistorialInventario extends Model
{
    protected $table = 'configuracion_historial_inventario';

    protected $fillable = [
        'fecha_corte',
        'inicializado',
        'total_registros'
    ];

    protected $casts = [
        'inicializado' => 'boolean',
        'total_registros' => 'integer'
    ];

    protected $dates = [
        'fecha_corte'
    ];
}