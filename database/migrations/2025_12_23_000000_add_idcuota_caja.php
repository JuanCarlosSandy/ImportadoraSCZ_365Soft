<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdcuotaCaja extends Migration
{
    public function up()
    {
        // 👉 Agregar idcuota_credito a transacciones_cajas
        Schema::table('transacciones_cajas', function (Blueprint $table) {
            $table->unsignedInteger('idcuota_credito')->nullable()->after('idbanco');

        });
    }

    public function down()
    {
        Schema::table('transacciones_cajas', function (Blueprint $table) {
            $table->dropColumn('idcuota_credito');
        });
    }
}
