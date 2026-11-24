<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovimientosInventarioTable extends Migration
{
    public function up()
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {

            $table->increments('id');

            $table->unsignedInteger('idarticulo');
            $table->unsignedInteger('idalmacen');
            $table->unsignedInteger('idusuario')->nullable();

            $table->string('tipo_movimiento', 50);

            $table->integer('cantidad');
            $table->integer('stock_anterior');
            $table->integer('stock_actual');

            $table->string('tipo_referencia', 30)->nullable();
            $table->unsignedInteger('idreferencia')->nullable();
            $table->unsignedInteger('iddetalle_referencia')->nullable();

            $table->string('observacion', 255)->nullable();

            $table->dateTime('fecha_movimiento');

            $table->timestamps();

            // Índices para consultas históricas
            $table->index(
                ['idalmacen', 'fecha_movimiento', 'idarticulo'],
                'idx_mov_almacen_fecha_articulo'
            );

            $table->index(
                ['idarticulo', 'idalmacen', 'fecha_movimiento'],
                'idx_mov_articulo_almacen_fecha'
            );

            $table->index(
                ['tipo_referencia', 'idreferencia'],
                'idx_mov_referencia'
            );

            $table->index(
                'tipo_movimiento',
                'idx_mov_tipo'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('movimientos_inventario');
    }
}