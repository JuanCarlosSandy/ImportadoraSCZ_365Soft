<?php
 
namespace App;
 
use Illuminate\Database\Eloquent\Model;
 
class CuotasCredito extends Model
{
    protected $table = 'cuotas_credito';
    protected $fillable = [
        'idcredito',
        'idcobrador',
        'numero_cuota',
        'fecha_pago',
        'fecha_cancelado',
        'precio_cuota',
        'saldo_restante',
        'estado',
        'idtipo_pago',
        'idcaja',
        'idbanco',
        'descuento',
        'idcliente'
    ];
    public $timestamps = false;

    public function creditoVenta()
{
    return $this->belongsTo(CreditoVenta::class, 'idcredito');
}
}
