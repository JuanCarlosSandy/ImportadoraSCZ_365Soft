<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'idcliente',
        'idusuario',
        'tipo_comprobante',
        'num_comprobante',
        'fecha_hora',
        'impuesto',
        'total',
        'estado',
        'idcaja',
        'idtipo_venta',
        'descuento_total',
        'idtipo_pago',
        'idalmacen',
        'idsucursal',
        'idbanco',
    ];

    public function caja()
    {
        return $this->belongsTo('App\Caja', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'idventa');
    }

    public function creditoVenta()
    {
        return $this->hasOne(CreditoVenta::class, 'idventa');
    }

    
    // RELACIONES QUE DEBES AGREGAR:
    public function usuario()
    {
        return $this->belongsTo('App\User', 'idusuario');
    }
    public function persona()
{
    return $this->belongsTo('App\Persona', 'idpersona');
}

    public function cliente()
    {
        return $this->belongsTo('App\Persona', 'idcliente');
    }

    public function sucursal()
    {
        // Si tienes el id de sucursal en el usuario, puedes acceder así:
        return $this->usuario ? $this->usuario->sucursal() : null;
        // O si tienes un campo idalmacen que es la sucursal:
        // return $this->belongsTo('App\Sucursal', 'idalmacen');
    }
}