<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaldoFavorPersonas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personas', function (Blueprint $table) {
            // 🟢 Saldo a favor del cliente
            $table->decimal('saldo_favor', 15, 2)
                  ->default(0)
                  ->after('email'); // ajusta la columna de referencia si quieres
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn('saldo_favor');
        });
    }
}
