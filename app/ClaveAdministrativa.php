<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClaveAdministrativa extends Model
{
    protected $table = 'clave_administrativa';

    protected $fillable = [
        'clave'
    ];
}