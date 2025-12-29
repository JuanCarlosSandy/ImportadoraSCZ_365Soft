<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddConfiguracionTrabajoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configuracion_trabajos', function (Blueprint $table) {
            // 🟢 Agregamos las nuevas columnas
            $table->integer('permitir_descuento')->default(0)->after('tiempoMinCaducidadArticulo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configuracion_trabajos', function (Blueprint $table) {
            // 🔴 Eliminamos las columnas si se revierte la migración
            $table->dropColumn(['permitir_descuento']);
        });
    }
}
