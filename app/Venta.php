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

    // ✅ Mutador: Solo permite 1 o 2, si es otro valor o está vacío, asigna 1
    public function setIdtipoVentaAttribute($value)
    {
        // Si está vacío o no es 1 ni 2, asignar 1
        if (empty($value) || ($value !== 1 && $value !== 2)) {
            $this->attributes['idtipo_venta'] = 1;
        } else {
            $this->attributes['idtipo_venta'] = $value;
        }
    }

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
