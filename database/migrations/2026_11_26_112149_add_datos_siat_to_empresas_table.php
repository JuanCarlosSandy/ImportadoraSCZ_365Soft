<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDatosSiatToEmpresasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {

            $table->string('razonSocial')
                ->nullable()
                ->default('CRISTIAN ALFREDO PURI RAMOS')
                ->after('nit');

            $table->string('token_siat', 1000)
                ->nullable()
                ->default('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJjNTI0NTQzOThAZ21haWwuY29tIiwiY29kaWdvU2lzdGVtYSI6Ijc3NTM1NTQ2QjcxMkRENDA5RDdBMzg3Iiwibml0IjoiSDRzSUFBQUFBQUFBQUxNd003Y3dNREEzTURRQUFBNFBTZDBLQUFBQSIsImlkIjo1MDE4MTI5LCJleHAiOjE4MTMyNTUwNjksImlhdCI6MTc4MTczMzQzOSwibml0RGVsZWdhZG8iOjUxNTM2MTAwMTIsInN1YnNpc3RlbWEiOiJTRkUifQ.cV8T4ugv4JNql9XdOdlm0yRvI_mVIptN7gEyp4fQTMTS-5dr7lMwBDxzPRveu9wyoTTIQCvZr-wOovMQ-juLvQ')
                ->after('razonSocial');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'razonSocial',
                'token_siat'
            ]);
        });
    }
}