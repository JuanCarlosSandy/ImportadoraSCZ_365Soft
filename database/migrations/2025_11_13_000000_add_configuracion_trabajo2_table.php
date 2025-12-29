<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddConfiguracionTrabajo2Table extends Migration
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
            $table->integer('permitir_ofertas')->default(0)->after('permitir_descuento');
            $table->integer('permitir_bonificacion')->default(0)->after('permitir_ofertas');
            $table->integer('permitir_cambioprecio')->default(0)->after('permitir_bonificacion');

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
            $table->dropColumn(['permitir_ofertas', 'permitir_bonificacion', 'permitir_cambioprecio']);
        });
    }
}
