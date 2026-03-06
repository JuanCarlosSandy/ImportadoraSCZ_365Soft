<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEstadoTraspaso extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('traspasos', function (Blueprint $table) {
            $table->boolean('estado')->default(1)->after('fecha_traspaso')->comment('Estado del traspaso');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('traspasos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
}
