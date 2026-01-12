<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemcompuestoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('itemcompuesto', function (Blueprint $table) {
            $table->increments('id');
            //$table->unsignedBigInteger('idarticulo');
            //$table->unsignedBigInteger('iditem');
            $table->unsignedInteger('idarticulo');
            $table->unsignedInteger('iditem');
            $table->decimal('cantidad', 11, 2);
            $table->timestamps();

            $table->foreign('idarticulo')->references('id')->on('articulos')->onDelete('cascade');
            $table->foreign('iditem')->references('id')->on('articulos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('itemcompuesto');
    }
}
