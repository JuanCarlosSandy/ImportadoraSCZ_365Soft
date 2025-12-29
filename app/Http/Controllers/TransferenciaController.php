<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transferencia;

class TransferenciaController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $perPage = $request->input('per_page', 5);
        $transferencias = Transferencia::with(['banco', 'venta', 'usuario'])->paginate($perPage);

        return response()->json([
            'transferencias' => $transferencias
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_banco' => 'required|integer',
            'monto' => 'required|numeric',
            'fecha_transferencia' => 'required|date',
        ]);

        $transferencia = Transferencia::create([
            'id_banco' => $request->id_banco,
            'monto' => $request->monto,
            'fecha_transferencia' => $request->fecha_transferencia,
            'numero_operacion' => $request->numero_operacion,
            'imagen_comprobante' => $request->imagen_comprobante,
            'id_venta' => $request->id_venta,
            'tipo_operacion' => $request->tipo_operacion,
            'id_usuario' => $request->id_usuario,
            'moneda' => $request->moneda,
            'nombre_remitente' => $request->nombre_remitente,
            'verificado' => $request->verificado,
            'concepto' => $request->concepto,
        ]);

        return response()->json([
            'message' => 'Transferencia registrada exitosamente',
            'transferencia' => $transferencia
        ], 201);
    }
}
