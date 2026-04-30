<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEstadoLlaveacceso extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('llave_acceso', function (Blueprint $table) {
            $table->boolean('estado')->default(0)->after('fechafin')->comment('Llave de acceso de la persona');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('llave_acceso', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
}
