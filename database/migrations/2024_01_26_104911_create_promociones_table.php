<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromocionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo', 11)->nullable();
            $table->decimal('precio', 15, 4)->nullable();
            $table->decimal('porcentaje', 11, 2)->nullable();
            $table->datetime('fecha_final')->nullable();
            $table->string('estado', 50)->default("Activo");
            $table->tinyInteger('tipo_promocion')->nullable();
            $table->string('nombre', 250);
            $table->integer('precio_r1')->nullable();
            $table->integer('rango_inicio_r1')->nullable();
            $table->integer('rango_final_r1')->nullable();
            $table->integer('precio_r2')->nullable();
            $table->integer('rango_inicio_r2')->nullable();
            $table->integer('rango_final_r2')->nullable();
            $table->integer('precio_r3')->nullable();
            $table->integer('rango_inicio_r3')->nullable();
            $table->integer('rango_final_r3')->nullable();
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
        Schema::dropIfExists('promociones');
    }
}
