<?php
 
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
 
class CreateIngresosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idusuario')->unsigned();
            $table->foreign('idusuario')->references('id')->on('users');
            $table->string('tipo_comprobante', 20);
            $table->string('serie_comprobante')->nullable();
            $table->string('num_comprobante')->nullable();
            $table->dateTime('fecha_hora');
            $table->decimal('impuesto', 4, 2);
            $table->decimal('total', 11, 2);
            $table->boolean('tipoCompra')->nullable();
            $table->integer('num_cuotas')->nullable();
            $table->integer('frecuencia_cuotas')->nullable();
            $table->decimal('cuota_inicial', 11, 2)->nullable();
            $table->string('tipo_pago_cuota')->nullable();
            $table->boolean('estado');
            $table->integer('idalmacen')->unsigned()->nullable();
            $table->foreign('idalmacen')->references('id')->on('almacens');
            $table->integer('idcaja')->unsigned()->nullable();
            $table->foreign('idcaja')->references('id')->on('cajas');
            $table->decimal('descuento_global')->nullable();
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
        Schema::dropIfExists('ingresos');
    }
}