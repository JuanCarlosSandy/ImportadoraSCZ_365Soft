<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutorizacionesDescuentoTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('autorizaciones_descuento', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idusuario')->unsigned();
            $table->boolean('puede_descontar')->default(0); // 0 = No puede, 1 = Puede
            $table->timestamps(); // <-- ESTO AGREGA created_at y updated_at automáticamente

            // Relaciones
            $table->foreign('idusuario')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autorizaciones_descuento');
    }
}
