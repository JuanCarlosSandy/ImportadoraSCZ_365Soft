<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVentaInstitucionales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ventas_institucionales', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idcliente')->unsigned();
            $table->foreign('idcliente')->references('id')->on('personas');
            $table->integer('idusuario')->unsigned();
            $table->foreign('idusuario')->references('id')->on('users');
            $table->integer('idtipo_venta')->unsigned();
            $table->foreign('idtipo_venta')->references('id')->on('tipo_ventas');
            $table->integer('idtipo_pago')->unsigned();
            $table->foreign('idtipo_pago')->references('id')->on('tipo_pagos');
            $table->string('tipo_comprobante', 20);
            $table->string('num_comprobante', 10);
            $table->dateTime('fecha_hora');
            $table->decimal('impuesto', 4, 2);
            $table->decimal('total', 11, 2);
            $table->string('estado', 20);
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
        Schema::dropIfExists('venta_institucionales');
    }
}
