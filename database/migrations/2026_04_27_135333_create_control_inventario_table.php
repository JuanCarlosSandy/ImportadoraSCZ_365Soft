<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateControlInventarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('controlinventario', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('fechahora');
            // Agregar el campo idsucursal después del campo idusuario (puedes cambiar la posición si deseas)
            $table->integer('idusuario')->unsigned()->nullable();
            // Crear la relación foránea con la tabla sucursales
            $table->foreign('idusuario')->references('id')->on('users');

            // Agregar el campo idsucursal después del campo idusuario (puedes cambiar la posición si deseas)
            $table->integer('idalmacen')->unsigned()->nullable();
            // Crear la relación foránea con la tabla sucursales
            $table->foreign('idalmacen')->references('id')->on('almacens');

            $table->integer('estado')->default(1);
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
        Schema::dropIfExists('control_inventario');
    }
}
