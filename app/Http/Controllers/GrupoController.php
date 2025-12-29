<?php

namespace App\Http\Controllers;

use App\Exports\GrupoExport;
use App\Grupo;
use App\Imports\GrupoImport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GrupoController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        //$criterio = $request->criterio;
        
        if ($buscar == '') {
            $grupos = Grupo::orderBy('id', 'desc')->paginate(6);
        } else {
            $grupos = Grupo::where('nombre_grupo', 'like', '%' . $buscar . '%')->orderBy('id', 'desc')->paginate(6);
        }
        

        return [
            'pagination' => [
                'total'        => $grupos->total(),
                'current_page' => $grupos->currentPage(),
                'per_page'     => $grupos->perPage(),
                'last_page'    => $grupos->lastPage(),
                'from'         => $grupos->firstItem(),
                'to'           => $grupos->lastItem(),
            ],
            'grupos' => $grupos
        ];
    }

    public function index2(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        //$criterio = $request->criterio;
        
        if ($buscar == '') {
            $grupos = Grupo::orderBy('id', 'desc');
        } else {
            $grupos = Grupo::where('nombre_grupo', 'like', '%' . $buscar . '%')->orderBy('id', 'desc');
        }
        $grupos=$grupos->get();

        return ['grupos' => $grupos
        ];
    }
    
    //Registrar Provedor
    public function store(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
         
        $grupos = new Grupo();
        $grupos->nombre_grupo = $request->nombre_grupo;
        Log::info('DATOS REGISTRO GRUPO:', [
            'nombre_grupo' => $request->nombre_grupo,
        ]);
        $grupos->save();
    }
    //editar grupo
    public function update(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $grupos = Grupo::findOrFail($request->id);
        $grupos->nombre_grupo = $request->nombre_grupo;
        Log::info('DATOS EDITAR GRUPO:', [
            'nombre_grupo' => $request->nombre_grupo,
        ]);
        $grupos->save();
    }
    //---exportar---
    public function excelGrupo()
    {
        $fechaActual = Carbon::now()->format('Y-m-d_H-i-s'); // Obtiene la fecha actual en el formato deseado
        $nombreArchivo = 'Grupo_' . $fechaActual;

        // Puedes elegir entre 'xlsx' o 'csv' según el formato deseado
        return Excel::download(new GrupoExport, $nombreArchivo . '.xlsx');
        //return Excel::download(new LineaExport, $nombreArchivo . '.csv');
    }
    //---importacion--
    public function importsaveExecelUser(Request $request){
        $path = $request->file('select_users_file')->getRealPath();
        Excel::import(new GrupoImport, $path);
    }
}
