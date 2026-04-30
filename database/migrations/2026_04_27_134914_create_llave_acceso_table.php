<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLlaveAccesoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('llave_acceso', function (Blueprint $table) {
            $table->increments('id');
            // Agregar el campo idsucursal después del campo idusuario (puedes cambiar la posición si deseas)
            $table->integer('idusuario')->unsigned()->nullable();
            // Crear la relación foránea con la tabla sucursales
            $table->foreign('idusuario')->references('id')->on('users');
            $table->string('llave')->unique();
            $table->date('fechafin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('llave_acceso');
    }
}
