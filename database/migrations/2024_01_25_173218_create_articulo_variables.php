<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticuloVariables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articulo_variables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idvariable')->unsigned();
            $table->foreign('idvariable')->references('id')->on('variable_temporals')->onDelete('cascade');
            $table->integer('idarticulo')->unsigned();
            $table->foreign('idarticulo')->references('id')->on('articulos');
            $table->integer('cantidad');
            $table->decimal('precio', 11, 2);
            $table->decimal('descuento', 11, 2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articulo_variables');
    }
}
