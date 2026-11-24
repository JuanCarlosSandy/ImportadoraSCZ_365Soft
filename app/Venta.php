<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'idcliente',
        'idusuario',
        'idtipo_venta',
        'idtipo_pago',
        'idbanco',
        'monto_qr',
        'monto_efectivo',
        'idalmacen',
        'tipo_comprobante',
        'serie_comprobante',
        'num_comprobante',
        'fecha_hora',
        'impuesto',
        'total',
        'descuento_total',
        'estado',
        'idcaja',
        'idsucursal'
    ];

    protected $casts = [
        'idcliente' => 'integer',
        'idusuario' => 'integer',
        'idtipo_venta' => 'integer',
        'idtipo_pago' => 'integer',
        'idbanco' => 'integer',
        'idalmacen' => 'integer',
        'idcaja' => 'integer',
        'idsucursal' => 'integer'
    ];

    public function setIdtipoVentaAttribute($value)
    {
        $value = (int) $value;

        $this->attributes['idtipo_venta'] = in_array(
            $value,
            [1, 2],
            true
        ) ? $value : 1;
    }

    public function caja()
    {
        return $this->belongsTo(
            'App\Caja',
            'idcaja',
            'id'
        );
    }

    public function detalles()
    {
        return $this->hasMany(
            DetalleVenta::class,
            'idventa',
            'id'
        );
    }

    public function creditoVenta()
    {
        return $this->hasOne(
            CreditoVenta::class,
            'idventa',
            'id'
        );
    }

    public function usuario()
    {
        return $this->belongsTo(
            'App\User',
            'idusuario',
            'id'
        );
    }

    public function persona()
    {
        return $this->belongsTo(
            'App\Persona',
            'idcliente',
            'id'
        );
    }

    public function cliente()
    {
        return $this->belongsTo(
            'App\Persona',
            'idcliente',
            'id'
        );
    }

    public function sucursal()
    {
        return $this->belongsTo(
            'App\Sucursal',
            'idsucursal',
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
}