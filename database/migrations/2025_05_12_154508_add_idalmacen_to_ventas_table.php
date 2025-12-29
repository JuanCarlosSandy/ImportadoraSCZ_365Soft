<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdalmacenToVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->integer('idalmacen')->default(1)->after('idtipo_pago')->unsigned();

            $table->foreign('idalmacen')->references('id')->on('almacens');
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['idalmacen']);
            $table->dropColumn('idalmacen');
        });
    }
}
