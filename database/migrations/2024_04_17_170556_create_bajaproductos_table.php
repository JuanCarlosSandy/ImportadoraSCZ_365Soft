<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBajaproductosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ajuste_invetarios', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('producto');
            $table->string('almacen');
            $table->integer('cantidad');
            $table->integer('idtipobajas')->unsigned();
            $table->foreign('idtipobajas')->references('id')->on('tipo_bajas');
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
        Schema::dropIfExists('bajaproductos');
    }
}
