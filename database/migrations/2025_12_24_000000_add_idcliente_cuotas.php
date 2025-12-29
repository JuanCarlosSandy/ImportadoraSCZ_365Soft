<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdclienteCuotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cuotas_credito', function (Blueprint $table) {
            // 🟢 Agregamos las nuevas columnas
            $table->integer('idcliente')->nullable()->references('id')->on('personas');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cuotas_credito', function (Blueprint $table) {
            // 🔴 Eliminamos las columnas si se revierte la migración
            $table->dropColumn(['idcliente']);
        });
    }
}
