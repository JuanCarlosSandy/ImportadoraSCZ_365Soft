<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MontoBonificacion;

class MontoBonificacionController extends Controller
{
    // 📋 Listar todos los registros
    public function index()
    {
        return response()->json(MontoBonificacion::all());
    }

    // 💾 Crear nuevo registro
    public function store(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0',
            'fecha_actualizacion' => 'nullable|date',
            'es_acumulativo' => 'boolean',
            'periodo_meses' => 'nullable|integer|min:1', // 🔹 nuevo campo
            'fecha_inicio' => 'nullable|date',
        ]);

        $monto = MontoBonificacion::create([
            'monto' => $request->monto,
            'fecha_actualizacion' => $request->fecha_actualizacion,
            'es_acumulativo' => $request->es_acumulativo ?? 0,
            'periodo_meses' => $request->periodo_meses ?? null, // 🔹 nuevo campo
            'fecha_inicio' => $request->fecha_inicio,
        ]);

        return response()->json([
            'message' => 'Monto de bonificación registrado correctamente.',
            'data' => $monto
        ], 201);
    }

    // ✏️ Actualizar registro existente
    public function update(Request $request, $id)
    {
        $monto = MontoBonificacion::findOrFail($id);

        $request->validate([
            'monto' => 'required|numeric|min:0',
            'fecha_actualizacion' => 'nullable|date',
            'es_acumulativo' => 'boolean',
            'periodo_meses' => 'nullable|integer|min:1', // 🔹 nuevo campo
            'fecha_inicio' => 'nullable|date',
        ]);

        $monto->update([
            'monto' => $request->monto,
            'fecha_actualizacion' => $request->fecha_actualizacion,
            'es_acumulativo' => $request->es_acumulativo ?? 0,
            'periodo_meses' => $request->periodo_meses, // 🔹 nuevo campo
            'fecha_inicio' => $request->fecha_inicio,
        ]);

        return response()->json([
            'message' => 'Monto de bonificación actualizado correctamente.',
            'data' => $monto
        ]);
    }

    // 🗑️ Eliminar registro
    public function destroy($id)
    {
        $monto = MontoBonificacion::findOrFail($id);
        $monto->delete();

        return response()->json([
            'message' => 'Monto de bonificación eliminado correctamente.'
        ]);
    }
}
