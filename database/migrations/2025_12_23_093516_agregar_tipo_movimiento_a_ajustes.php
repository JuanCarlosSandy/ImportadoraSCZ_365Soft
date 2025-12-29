<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarTipoMovimientoAAjustes extends Migration
{
    public function up()
    {
        Schema::table('ajuste_invetarios', function (Blueprint $table) {
            $table->string('tipo_movimiento', 20)->default('salida')->after('cantidad');
        });
    }

    public function down()
    {
        Schema::table('ajuste_invetarios', function (Blueprint $table) {
            $table->dropColumn('tipo_movimiento');
        });
    }
}