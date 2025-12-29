<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCreditoVenta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credito_ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idventa')->unsigned();
            $table->integer('idcliente')->unsigned();
            $table->integer('numero_cuotas')->default(0);
            $table->integer('tiempo_dias_cuota')->default(0);
            $table->integer('total')->nullable();
            $table->string('estado')->default('Pendiente');
            $table->dateTime('proximo_pago')->nullable();

            $table->foreign('idventa')->references('id')->on('ventas');
            $table->foreign('idcliente')->references('id')->on('personas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credito_ventas');
    }
}
