<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDetallebajasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detallebajas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cantidad');
            $table->integer('idbajaproductos')->unsigned();
            $table->foreign('idbajaproductos')->references('id')->on('ajuste_invetarios');
            $table->integer('idarticulos')->unsigned();
            $table->foreign('idarticulos')->references('id')->on('articulos');
            
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
        Schema::dropIfExists('detallebajas');
    }
}
