<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ingreso extends Model
{
    protected $table = 'ingresos';

    protected $fillable = [
        'idusuario',
        'tipo_comprobante',
        'serie_comprobante',
        'num_comprobante',
        'fecha_hora',
        'impuesto',
        'total',
        'tipoCompra',
        'num_cuotas',
        'frecuencia_cuotas',
        'cuota_inicial',
        'tipo_pago_cuota',
        'estado',
        'idalmacen',
        'idcaja',
        'descuento_global'
    ];

    protected $casts = [
        'idusuario' => 'integer',
        'idalmacen' => 'integer',
        'idcaja' => 'integer',
        'estado' => 'integer',
        'tipoCompra' => 'integer',
        'num_cuotas' => 'integer',
        'frecuencia_cuotas' => 'integer'
    ];

    protected $dates = [
        'fecha_hora'
    ];

    public function usuario()
    {
        return $this->belongsTo(
            'App\User',
            'idusuario',
            'id'
        );
    }

    public function proveedor()
    {
        return $this->belongsTo('App\Proveedor');
    }

    public function caja()
    {
        return $this->belongsTo(
            'App\Caja',
            'idcaja',
            'id'
        );
    }

    public function almacen()
    {
        return $this->belongsTo(
            'App\Almacen',
            'idalmacen',
            'id'
        );
    }

    public function detallesIngreso()
    {
        return $this->hasMany(
            DetalleIngreso::class,
            'idingreso',
            'id'
        );
    }

    public function cuotas()
    {
        return $this->hasMany(
            IngresoCuota::class,
            'idingreso',
            'id'
        );
    }
}