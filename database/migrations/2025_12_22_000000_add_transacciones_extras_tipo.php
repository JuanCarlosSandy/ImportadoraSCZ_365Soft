<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransaccionesExtrasTipo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transacciones_cajas', function (Blueprint $table) {
            $table->integer('tipo_pago')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transacciones_cajas', function (Blueprint $table) {
            $table->dropColumn('tipo_pago');
        });
    }
}
