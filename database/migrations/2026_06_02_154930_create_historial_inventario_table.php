<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistorialInventarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historial_inventario', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Agregar el campo idcaja
            $table->integer('idcaja')->unsigned()->nullable();
            // Crear la relación foránea con la tabla cajas
            $table->foreign('idcaja')->references('id')->on('cajas');

            // Agregar el campo idarticulo
            $table->integer('idarticulo')->unsigned()->nullable();
            // Crear la relación foránea con la tabla articulos
            $table->foreign('idarticulo')->references('id')->on('articulos');

            $table->decimal('stock_historico', 18, 2);

            $table->index('idcaja');
            $table->index('idarticulo');

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
        Schema::dropIfExists('historial_inventario');
    }
}
