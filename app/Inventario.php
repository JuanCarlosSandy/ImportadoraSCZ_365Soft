<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventarios';

    protected $fillable = [
        'idalmacen',
        'idarticulo',
        'fecha_vencimiento',
        'saldo_stock',
        'cantidad'
    ];

    protected $casts = [
        'idalmacen' => 'integer',
        'idarticulo' => 'integer',
        'saldo_stock' => 'integer',
        'cantidad' => 'integer'
    ];

    public function almacen()
    {
        return $this->belongsTo(
            'App\Almacen',
            'idalmacen',
            'id'
        );
    }

    public function articulo()
    {
        return $this->belongsTo(
            'App\Articulo',
            'idarticulo',
            'id'
        );
    }

    public function detallesTraspasos()
    {
        return $this->hasMany(
            'App\DetalleTraspaso',
            'idinventario',
            'id'
        );
    }
}