<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticulosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articulos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idcategoria')->unsigned(); //Linea
            $table->integer('idgrupo')->nullable()->unsigned(); //aumente 14 junio
            $table->integer('idproveedor')->unsigned(); //aumente 5 juio

            $table->integer('idmedida')->unsigned(); //new

            $table->string('codigo', 255)->unique();
            $table->string('nombre', 255); //Nombre comercial
            $table->string('nombre_generico', 255); //aumente 5_julio
            $table->integer('unidad_envase'); //aumente
            $table->decimal('precio_list_unid', 15, 4)->nullable(); //aumente
            $table->decimal('precio_costo_unid', 15, 4); //aumente
            $table->decimal('precio_costo_paq', 15, 4); //aumente
            $table->decimal('precio_venta', 15, 4); //precio presio2

            $table->decimal('precio_uno', 15, 4)->nullable();//AUMENTE 19/9/2023
            $table->decimal('precio_dos', 15, 4)->nullable();//AUMENTE 19/9/2023
            $table->decimal('precio_tres', 15, 4)->nullable();//AUMENTE 19/9/2023
            $table->decimal('precio_cuatro', 15, 4)->nullable();//AUMENTE 19/9/2023

            $table->integer('stock'); //stock minimo
            $table->string('descripcion', 256)->nullable(); //stock maximo
                        $table->timestamp('precio_actualizado_en')->nullable();
            $table->boolean('condicion')->default(1); // Controlado
            $table->timestamps();

            $table->foreign('idcategoria')->references('id')->on('categorias');
            $table->foreign('idgrupo')->references('id')->on('grupos');
            $table->foreign('idproveedor')->references('id')->on('proveedores');

            //new
            $table->decimal('costo_compra', 10, 2);
            $table->foreign('idmedida')->references('id')->on('medidas');
            $table->string('codigo_alfanumerico', 50)->nullable();// aumente el 23-01-2024
            $table->string('descripcion_fabrica', 50)->nullable();// aumente el 23-01-2024
            $table->integer('vencimiento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articulos');
    }
}