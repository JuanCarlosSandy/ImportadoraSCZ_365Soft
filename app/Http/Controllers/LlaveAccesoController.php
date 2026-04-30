<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\LlaveAcceso;

class LlaveAccesoController extends Controller
{
    public function index()
    {
        $llaves = LlaveAcceso::with('usuario')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($llaves);
    }

    public function store(Request $request)
    {
        $request->validate([
            'llave' => 'required|string|max:255',
            'idusuario' => 'required|exists:users,id',
            'fechafin' => 'required|date|after_or_equal:today'
        ], [
            'fechafin.after_or_equal' => 'La fecha de expiración no puede ser anterior a hoy.'
        ]);

        try {

            // 🔥 VALIDAR SI YA EXISTE ESA LLAVE
            $llaveExistente = LlaveAcceso::with('usuario')
                ->where('llave', $request->llave)
                ->where('estado', 1)
                ->first();

            if ($llaveExistente) {

                $nombreUsuario = $llaveExistente->usuario
                    ? $llaveExistente->usuario->usuario
                    : 'otro usuario';

                return response()->json([
                    'error' => "La llave ya está registrada para el usuario {$nombreUsuario}"
                ], 400);
            }

            // ✅ GUARDAR
            $llave = new LlaveAcceso();
            $llave->llave = $request->llave;
            $llave->idusuario = $request->idusuario;
            $llave->fechafin = $request->fechafin;
            $llave->estado = 1;

            $llave->save();

            return response()->json([
                'message' => 'Llave registrada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'llave' => 'required|string|max:255',
            'idusuario' => 'required|exists:users,id',
            'fechafin' => 'required|date'
        ]);
        try {
            $llave = LlaveAcceso::findOrFail($id);

            $llave->llave = $request->llave;
            $llave->idusuario = $request->idusuario;
            $llave->fechafin = $request->fechafin;
            $llave->save();

            return response()->json([
                'message' => 'Llave actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $llave = LlaveAcceso::findOrFail($id);
            $llave->delete();

            return response()->json([
                'message' => 'Llave eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verificar(Request $request)
    {
        $userId = auth()->id();

        $llave = LlaveAcceso::where('idusuario', $userId)
            ->where('estado', 1)
            ->orderBy('id', 'desc')
            ->first();

        if (!$llave) {
            return response()->json(['valido' => false]);
        }

        // 🔒 VALIDAR EXPIRACIÓN PRIMERO
        if (now()->gt($llave->fechafin)) {

            // 🗑 eliminar llave vencida
            $llave->delete();

            return response()->json([
                'valido' => false,
                'mensaje' => 'La llave ha expirado'
            ]);
        }

        // 🔑 VALIDAR CLAVE
        if ($request->clave == $llave->llave) {
            return response()->json(['valido' => true]);
        }

        return response()->json([
            'valido' => false,
            'mensaje' => 'Clave incorrecta'
        ]);
    }
    public function eliminarVencidas()
    {
        try {

            $eliminadas = LlaveAcceso::where('fechafin', '<', now())
                ->delete();

            return response()->json([
                'message' => 'Llaves vencidas eliminadas',
                'total' => $eliminadas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
