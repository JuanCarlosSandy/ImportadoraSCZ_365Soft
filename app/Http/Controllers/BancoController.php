<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Banco;

class BancoController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = trim($request->buscar);
        $perPage = $request->input('per_page', 15);

        if ($buscar == '') {

            // ORDENAR más reciente primero
            $bancos = Banco::orderBy('id', 'desc')->paginate($perPage);

        } else {

            $palabras = explode(' ', $buscar);

            $bancos = Banco::where(function ($query) use ($palabras) {

                foreach ($palabras as $palabra) {
                    $query->where(function ($q) use ($palabra) {
                        $q->where('nombre_cuenta', 'like', "%$palabra%")
                        ->orWhere('nombre_banco', 'like', "%$palabra%")
                        ->orWhere('numero_cuenta', 'like', "%$palabra%")
                        ->orWhere('tipo_cuenta', 'like', "%$palabra%");
                    });
                }

            })
            ->orderBy('id', 'desc') // <-- ORDENADO POR ÚLTIMO REGISTRADO
            ->paginate($perPage);
        }

        return [
            'pagination' => [
                'total' => $bancos->total(),
                'current_page' => $bancos->currentPage(),
                'per_page' => $bancos->perPage(),
                'last_page' => $bancos->lastPage(),
                'from' => $bancos->firstItem(),
                'to' => $bancos->lastItem(),
            ],
            'bancos' => $bancos
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_cuenta' => 'required',

            'nombre_banco' => 'required',
            'numero_cuenta' => 'required',
            'tipo_cuenta' => 'required',
        ]);

        $banco = Banco::create($request->all());

        return response()->json($banco, 201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'nombre_cuenta' => 'required',
            'nombre_banco' => 'required',
            'numero_cuenta' => 'required',
            'tipo_cuenta' => 'required',
        ]);
        $banco = Banco::findOrFail($request->id);

        $banco->update($request->all());

        return response()->json($banco, 200);
    }

    public function getAllData(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $bancos = Banco::select('nombre_cuenta', 'nombre_banco', 'numero_cuenta')->get();

        return response()->json($bancos, 200);
    }
    public function SelectBancos(Request $request)
    {
        if (!$request->ajax()) {
        return redirect('/');
        }

        $bancos = Banco::select('id','nombre_cuenta','nombre_banco','numero_cuenta','tipo_cuenta')->get();


        return response()->json($bancos, 200);
    }
    public function ListarBancos()
{
    try {

        $bancos = Banco::select(
                'id',
                'nombre_cuenta'
            )
            ->where('estado', 1) // solo bancos activos (si usas este campo)
            ->orderBy('nombre_cuenta', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'bancos' => $bancos
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Error al listar bancos.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
