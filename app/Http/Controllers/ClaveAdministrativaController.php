<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\ClaveAdministrativa;

class ClaveAdministrativaController extends Controller
{
    /**
     * Obtener información de la clave.
     */
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        // Solo administrador
        if (Auth::user()->idrol != 4) {
            return response()->json([
                'id' => -1,
                'error' => 'No tiene permisos para realizar esta acción.'
            ], 403);
        }

        $clave = ClaveAdministrativa::first();

        return [
            'configurada' => $clave ? true : false
        ];
    }


    /**
     * Crear o actualizar la clave administrativa.
     */
    public function store(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        // Solo administrador
        if (Auth::user()->idrol != 4) {
            return response()->json([
                'id' => -1,
                'error' => 'No tiene permisos para realizar esta acción.'
            ], 403);
        }

        $request->validate([
            'clave' => 'required|string|min:4|max:255'
        ]);

        try {

            $clave = ClaveAdministrativa::first();

            if (!$clave) {

                // Crear por primera vez
                $clave = new ClaveAdministrativa();

                $clave->clave = Hash::make($request->clave);

                $clave->save();

                return [
                    'id' => $clave->id,
                    'mensaje' => 'Clave administrativa creada correctamente.'
                ];

            } else {

                // Actualizar clave existente
                $clave->clave = Hash::make($request->clave);

                $clave->save();

                return [
                    'id' => $clave->id,
                    'mensaje' => 'Clave administrativa actualizada correctamente.'
                ];
            }

        } catch (\Exception $e) {

            return [
                'id' => -1,
                'error' => 'Ocurrió un error al guardar la clave administrativa.'
            ];
        }
    }


    /**
     * Validar la clave administrativa.
     * Esta función será utilizada al anular una venta.
     */
    public function validar(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $request->validate([
            'clave' => 'required|string'
        ]);

        $clave = ClaveAdministrativa::first();

        // No existe ninguna clave configurada
        if (!$clave) {
            return [
                'valido' => false,
                'error' => 'No existe una clave administrativa configurada.'
            ];
        }

        // Verificar clave
        if (Hash::check($request->clave, $clave->clave)) {

            return [
                'valido' => true
            ];
        }

        return [
            'valido' => false,
            'error' => 'La clave administrativa es incorrecta.'
        ];
    }
}