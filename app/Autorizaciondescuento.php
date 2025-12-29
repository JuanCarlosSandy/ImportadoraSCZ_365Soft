<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autorizaciondescuento extends Model
{
    protected $table = 'autorizaciones_descuento';

    protected $fillable = [
        'idusuario',
        'puede_descontar',
    ];

    // Relación opcional: si quieres traer también los datos del usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario');
    }
}
