<?php
 
namespace App;
 
use Illuminate\Database\Eloquent\Model;
 
class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';
    protected $fillable = [
        'idventa', 
        'idarticulo',
        'cantidad',
        'precio',
        'descuento',
        'modo_venta'
    ];
    public $timestamps = false;
     public function producto()
{
    return $this->belongsTo(Articulo::class, 'idarticulo');
}
}