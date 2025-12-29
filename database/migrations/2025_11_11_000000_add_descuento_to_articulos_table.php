<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDescuentoToArticulosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('articulos', function (Blueprint $table) {
            // 🟢 Agregamos las nuevas columnas
            $table->decimal('descuento', 8, 2)->default(0)->after('precio_cuatro');
            $table->date('fecha_venc_descuento')->nullable()->after('descuento');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('articulos', function (Blueprint $table) {
            // 🔴 Eliminamos las columnas si se revierte la migración
            $table->dropColumn(['descuento', 'fecha_venc_descuento']);
        });
    }
}
