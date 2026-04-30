<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDetalleControlinventarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalle_controlinventario', function (Blueprint $table) {
            $table->increments('id');
             // Agregar el campo idsucursal después del campo idusuario (puedes cambiar la posición si deseas)
            $table->integer('idcontrol')->unsigned()->nullable();
            // Crear la relación foránea con la tabla sucursales
            $table->foreign('idcontrol')->references('id')->on('controlinventario');

            // Agregar el campo idsucursal después del campo idusuario (puedes cambiar la posición si deseas)
            $table->integer('idarticulo')->unsigned()->nullable();
            // Crear la relación foránea con la tabla sucursales
            $table->foreign('idarticulo')->references('id')->on('articulos');
            $table->decimal('stocksistema', 10, 2)->default(0);
            $table->decimal('stockfisico', 10, 2)->default(0);
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
        Schema::dropIfExists('detalle_controlinventario');
    }
}
