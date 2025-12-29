<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDetalleVentasUnidadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            // 🟢 Agregamos las nuevas columnas
            $table->string('modo_venta')->after('descuento')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            // 🔴 Eliminamos las columnas si se revierte la migración
            $table->dropColumn(['modo_venta']);
        });
    }
}
