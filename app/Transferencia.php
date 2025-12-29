<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Banco;
use App\Venta;
use App\User;

class Transferencia extends Model
{
    protected $table = 'transferencias';

    protected $fillable = [
        'monto',
        'fecha_transferencia',
        'numero_operacion',
        'imagen_comprobante',
        'id_banco',
        'id_venta',
        'tipo_operacion',
        'id_usuario',
        'moneda',
        'nombre_remitente',
        'verificado',
        'concepto',
    ];

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'id_banco');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
