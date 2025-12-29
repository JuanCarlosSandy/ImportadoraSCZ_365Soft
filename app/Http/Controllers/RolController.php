<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Rol;

class RolController extends Controller
{
   public function index(Request $request)
{
    if (!$request->ajax()) return redirect('/');

    $buscar = $request->buscar;

    if ($buscar == '') {
        $roles = Rol::orderBy('id', 'desc')->paginate(6);
    } else {
        $roles = Rol::where(function($query) use ($buscar) {
            $query->where('nombre', 'like', '%' . $buscar . '%')
                  ->orWhere('descripcion', 'like', '%' . $buscar . '%');
                  // Puedes agregar más campos aquí si lo deseas
        })
        ->orderBy('id', 'desc')
        ->paginate(6);
    }

    return [
        'pagination' => [
            'total'        => $roles->total(),
            'current_page' => $roles->currentPage(),
            'per_page'     => $roles->perPage(),
            'last_page'    => $roles->lastPage(),
            'from'         => $roles->firstItem(),
            'to'           => $roles->lastItem(),
        ],
        'roles' => $roles
    ];
}


    public function selectRol(Request $request)
    {
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;

        $query = Rol::where('condicion', '=', '1');

        if ($idrol == 1) {
            // Excluir el rol con ID 4
            $query->where('id', '!=', 4);
        }

        $roles = $query->select('id', 'nombre')
                    ->orderBy('nombre', 'asc')
                    ->get();

        return ['roles' => $roles];
    }
}
