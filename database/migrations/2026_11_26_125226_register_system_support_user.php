<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterSystemSupportUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {

            // Verificar si el usuario ya existe
            $existeUsuario = DB::table('users')
                ->where('usuario', 'controlsistema')
                ->exists();

            if ($existeUsuario) {
                throw new \RuntimeException(
                    'El usuario controlsistema ya existe.'
                );
            }

            // Registrar persona
            $idPersona = DB::table('personas')->insertGetId([
                'nombre' => 'Control Sistemas',
                'usuario' => 1,
                'tipo_documento' => 'NA',
                'num_documento' => '00000000',
                'complemento_id' => null,
                'direccion' => 'NA',
                'telefono' => '000000',
                'email' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Registrar usuario
            DB::table('users')->insert([
                'id' => $idPersona,
                'usuario' => 'controlsistema',
                'password' => Hash::make('controlsistema'),
                'condicion' => 1,
                'idrol' => 4,
                'idsucursal' => 1,
                'idpuntoventa' => 1,
                'remember_token' => null
            ]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {

            $usuario = DB::table('users')
                ->where('usuario', 'controlsistema')
                ->first();

            if ($usuario) {

                DB::table('users')
                    ->where('iduse', $usuario->iduse)
                    ->delete();

                DB::table('personas')
                    ->where('id', $usuario->id)
                    ->where('nombre', 'Control Sistemas')
                    ->delete();
            }

        });
    }
}