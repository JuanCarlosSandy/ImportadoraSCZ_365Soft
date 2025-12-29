<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    //
    protected $fillable = ['nombre_almacen', 'ubicacion', 'encargado', 'telefono', 'lugar', 'observacion', 'condicion'];
 
  protected $casts = [
    'encargado' => 'array',
];

    public function inventario()
    {
        return $this->hasMany('App\Inventario');
    }
public function encargados()
{
    return $this->belongsToMany(Persona::class, 'almacen_persona', 'almacen_id', 'persona_id')->withTimestamps();
}


}