<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MontoBonificacion extends Model
{
    // Nombre de la tabla (si no sigue la convención plural)
    protected $table = 'montoBonificacion';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'monto',
        'fecha_actualizacion',
        'es_acumulativo',
        'periodo_meses', // 🔹 nuevo campo
        'fecha_inicio',
    ];

    // Opcional: si quieres manejar 'es_acumulativo' como booleano
    protected $casts = [
        'es_acumulativo' => 'boolean',
        'fecha_actualizacion' => 'date',
    ];
}
