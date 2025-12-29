<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDescuentoCuotasVentas extends Migration
{
    public function up()
    {
        Schema::table('cuotas_credito', function (Blueprint $table) {
            if (!Schema::hasColumn('cuotas_credito', 'descuento')) {
                $table->decimal('descuento', 10, 2)
                      ->default(0)
                      ->after('precio_cuota'); // posición sugerida
            }
        });
    }

    public function down()
    {
        Schema::table('cuotas_credito', function (Blueprint $table) {
            if (Schema::hasColumn('cuotas_credito', 'descuento')) {
                $table->dropColumn('descuento');
            }
        });
    }
}
