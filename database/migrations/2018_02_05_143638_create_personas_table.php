<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePersonasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100)->unique();
            $table->integer('usuario')->nullable(); //aumente
            $table->string('tipo_documento', 20)->nullable();
            $table->string('num_documento', 20)->nullable();
            $table->string('complemento_id', 20)->nullable();
            $table->string('direccion', 70)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 50)->nullable();
            $table->timestamps();
        });
        DB::table('personas')->insert([
        ['id' => '1', 
        'nombre' => 'root',
        'usuario' => '1', 
        'tipo_documento' => 'NA', 
        'num_documento' => '00000000', 
        'direccion' => 'NA', 
        'telefono' => '000000', 
        'email' => 'root@gmail.com'
        ],
        ['id' => '2', 
        'nombre' => 'Casos Especiales',
        'usuario' => null, 
        'tipo_documento' => '1', 
        'num_documento' => '99001', 
        'direccion' => null, 
        'telefono' => null, 
        'email' => null
        ],
        ['id' => '3 ', 
        'nombre' => 'Control Tributario',
        'usuario' => null, 
        'tipo_documento' => '4', 
        'num_documento' => '99002', 
        'direccion' => null, 
        'telefono' => null, 
        'email' => null
        ],
        ['id' => '4', 
        'nombre' => 'VENTAS MENORES DEL DÍA',
        'usuario' => null, 
        'tipo_documento' => '4', 
        'num_documento' => '99003', 
        'direccion' => null, 
        'telefono' => null, 
        'email' => null
        ],
        ['id' => '5', 
        'nombre' => 'S/N',
        'usuario' => null, 
        'tipo_documento' => '1', 
        'num_documento' => '0', 
        'direccion' => null, 
        'telefono' => null, 
        'email' => null
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
        Schema::dropIfExists('personas');
    }
}