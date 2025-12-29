<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTiempoMinCaducidadArticuloToConfiguracionTrabajos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configuracion_trabajos', function (Blueprint $table) {
            $table->string('tiempoMinCaducidadArticulo');
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
            $table->dropColumn('tiempoMinCaducidadArticulo');
        });
    }
}
