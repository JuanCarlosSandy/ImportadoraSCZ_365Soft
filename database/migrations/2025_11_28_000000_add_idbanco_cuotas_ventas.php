<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdbancoCuotasVentas extends Migration
{
    public function up()
    {
        // 👉 Agregar idbanco a ventas
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedInteger('idbanco')->nullable()->after('idtipo_pago');

        });

        // 👉 Agregar idbanco + idcaja a cuotas_credito
        Schema::table('cuotas_credito', function (Blueprint $table) {
            $table->unsignedInteger('idbanco')->nullable()->after('idtipo_pago');
        });
    }

    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('idbanco');
        });

        Schema::table('cuotas_credito', function (Blueprint $table) {
            $table->dropColumn(['idbanco']);
        });
    }
}
