<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfiguracionesTrabajo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('configuracion_trabajos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('gestion', 5)->unique();

            $table->string('codigoProductos', 100);
            $table->integer('almacenPredeterminado')->nullable();
            $table->decimal('maximoDescuento', 6)->nullable();
            $table->string('valuacionInventario', 100);
            $table->boolean('backupAutomatico')->default(0);

            $table->string('rutaBackup', 100)->nullable();
            $table->boolean('saldosNegativos')->default(0);
            $table->string('separadorDecimales', 15);

            $table->boolean('mostrarCostos')->default(1);
            $table->boolean('mostrarProveedores')->default(1);
            $table->boolean('mostrarSaldosStock')->default(1);
            $table->boolean('actualizarIva')->default(1);

            $table->boolean('permitirDevolucion')->default(0);
            $table->boolean('editarNroDoc')->default(0);
            $table->boolean('registroClienteObligatorio')->default(1);
            $table->boolean('buscarClientePorCodigo')->default(1);

            //PENDIENTE... quitar columnas innecesarias

            $table->integer('idMonedaPrincipal')->unsigned();
            $table->integer('idMonedaVenta')->unsigned();
            $table->integer('idMonedaCompra')->unsigned();



            $table->foreign('idMonedaPrincipal')->references('id')->on('monedas');
            $table->foreign('idMonedaVenta')->references('id')->on('monedas');
            $table->foreign('idMonedaCompra')->references('id')->on('monedas');

            $table->timestamps();
        });
        DB::table('configuracion_trabajos')->insert(
            array(
                'gestion' => '2025',
                'codigoProductos' => '1',
                'separadorDecimales' => ',',
                'idMonedaPrincipal' => '1',
                'idMonedaVenta' => '1',
                'idMonedaCompra' => '1',
                'valuacionInventario'=>'ninguno'

            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
