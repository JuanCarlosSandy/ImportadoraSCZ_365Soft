<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransferenciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transferencias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_banco');
            $table->decimal('monto', 10, 2);
            $table->dateTime('fecha_transferencia');
            $table->string('numero_operacion', 50)->nullable();
            $table->string('imagen_comprobante', 255)->nullable();

            $table->unsignedInteger('id_venta');
            $table->string('tipo_operacion', 50)->nullable();
            $table->unsignedInteger('id_usuario');
            $table->string('moneda', 10)->nullable();
            $table->string('nombre_remitente', 100)->nullable();
            $table->boolean('verificado')->nullable();
            $table->string('concepto', 255)->nullable();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('id_banco')->references('id')->on('bancos');
            $table->foreign('id_venta')->references('id')->on('ventas');
            $table->foreign('id_usuario')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transferencias');
    }
}
