<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfiguracionHistorialInventarioTable extends Migration
{
    public function up()
    {
        Schema::create('configuracion_historial_inventario', function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->dateTime('fecha_corte')->nullable();

            $table->boolean('inicializado')->default(false);

            $table->unsignedInteger('total_registros')->default(0);

            $table->timestamps();
        });

        DB::table('configuracion_historial_inventario')->insert([
            'id' => 1,
            'fecha_corte' => null,
            'inicializado' => 0,
            'total_registros' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('configuracion_historial_inventario');
    }
}