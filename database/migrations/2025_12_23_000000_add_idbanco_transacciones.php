<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdbancoTransacciones extends Migration
{
    public function up()
    {
        // 👉 Agregar idbanco a transacciones_cajas
        Schema::table('transacciones_cajas', function (Blueprint $table) {
            $table->unsignedInteger('idbanco')->nullable()->after('tipo_pago');

        });
    }

    public function down()
    {
        Schema::table('transacciones_cajas', function (Blueprint $table) {
            $table->dropColumn('idbanco');
        });
    }
}
