<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVariableTemporales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variable_temporals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idventainstitucional')->unsigned()->nullable();
            $table->foreign('idventainstitucional')->references('id')->on('ventas_institucionales');
            $table->string('nombre', 250);
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
        Schema::dropIfExists('variable_temporales');
    }
}
