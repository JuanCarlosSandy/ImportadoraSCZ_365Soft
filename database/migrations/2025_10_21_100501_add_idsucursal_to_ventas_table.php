<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdsucursalToVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Agregar el campo idsucursal después del campo idusuario (puedes cambiar la posición si deseas)
            $table->integer('idsucursal')->unsigned()->nullable();

              // Crear la relación foránea con la tabla sucursales
            $table->foreign('idsucursal')->references('id')->on('sucursales');
            
          
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Eliminar la relación y el campo
            $table->dropForeign(['idsucursal']);
            $table->dropColumn('idsucursal');
        });
    }
}
