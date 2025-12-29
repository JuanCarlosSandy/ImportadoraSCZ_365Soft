<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IngresoCuota extends Model
{
    protected $table = 'ingresos_cuotas';

    protected $fillable = [
        'idingreso',
        'fecha_pago',
        'precio_cuota',
        'total_cancelado',
        'saldo_restante',
        'fecha_cancelado',
        'estado'
    ];

    protected $dates = [
        'fecha_pago',
        'fecha_cancelado'
    ];

    // Relación con Ingreso
    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'idingreso');
    }
}
