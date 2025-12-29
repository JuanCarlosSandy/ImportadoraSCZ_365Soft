<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCuotasCredito extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuotas_credito', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idcredito')->references('id')->on('credito_ventas');
            $table->integer('idcobrador')->unsigned()->nullable();
            $table->integer('numero_cuota');
            $table->dateTime('fecha_pago');
            $table->dateTime('fecha_cancelado')->nullable();
            $table->decimal('precio_cuota');
            $table->decimal('saldo_restante');
            $table->string('estado');
            $table->foreign('idcobrador')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cuotas_credito');
    }
}

