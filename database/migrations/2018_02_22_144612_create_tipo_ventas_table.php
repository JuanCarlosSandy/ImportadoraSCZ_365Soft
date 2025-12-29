<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTipoVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipo_ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre_tipo_ventas', 50)->nullable();
            $table->timestamps();
        });
        DB::table('tipo_ventas')->insert([
            [
                'nombre_tipo_ventas' => 'Contado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre_tipo_ventas' => 'Credito',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tipo_ventas');
    }
}
