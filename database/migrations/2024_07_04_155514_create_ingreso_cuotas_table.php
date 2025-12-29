<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIngresoCuotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ingresos_cuotas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idingreso')->unsigned();
            $table->foreign('idingreso')->references('id')->on('ingresos');
            $table->date('fecha_pago');
            $table->decimal('precio_cuota', 11, 2);
            $table->decimal('total_cancelado', 11, 2)->default(0);
            $table->decimal('saldo_restante', 11, 2);
            $table->date('fecha_cancelado')->nullable();
            $table->string('tipo_pago_cuota')->nullable();
            $table->string('estado', 20)->default('Pendiente');
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
        Schema::dropIfExists('ingreso_cuotas');
    }
}
