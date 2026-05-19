<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStockActualDestinoToDetalleTraspasosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('detalle_traspasos', function (Blueprint $table) {

            $table->integer('stock_actual_destino')
                ->default(0)
                ->after('cantidad_traspaso');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detalle_traspasos', function (Blueprint $table) {

            $table->dropColumn('stock_actual_destino');

        });
    }
}
