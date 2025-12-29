<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromocionesArticulosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promociones_articulos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idpromociones')->unsigned();
            $table->integer('idarticulos')->unsigned();
            $table->foreign('idpromociones')->references('id')->on('promociones');
            $table->foreign('idarticulos')->references('id')->on('articulos');
            $table->integer('cantidad');
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
        Schema::dropIfExists('promociones_articulos');
    }
}
