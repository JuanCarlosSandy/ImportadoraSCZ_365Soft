<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMontoBonificacionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
       public function up()
    {
        Schema::create('montobonificacion', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('monto', 10, 2)->default(0);
            $table->date('fecha_actualizacion')->nullable();
            $table->boolean('es_acumulativo')->default(0); // 1 = sí, 0 = no
            $table->integer('periodo_meses')->nullable(); // 🔹 Nuevo campo: periodo de acumulación en meses
            $table->date('fecha_inicio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('montoBonificacion');
    }
}
