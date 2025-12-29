<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjusteInvetario extends Model
{
    protected $fillable = ['producto','almacen','cantidad','idtipobajas'];
}
