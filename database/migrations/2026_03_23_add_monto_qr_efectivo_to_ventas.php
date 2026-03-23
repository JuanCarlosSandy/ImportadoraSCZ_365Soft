<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MontoQrEfectivoToVentas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('monto_qr', 10, 2)->nullable()->after('idbanco')->comment('Monto pagado por QR');
            $table->decimal('monto_efectivo', 10, 2)->nullable()->after('monto_qr')->comment('Monto pagado en efectivo');
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
            $table->dropColumn(['monto_qr', 'monto_efectivo']);
        });
    }
}
