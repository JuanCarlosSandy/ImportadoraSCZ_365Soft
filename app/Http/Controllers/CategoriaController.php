<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Illuminate\Support\Facades\DB;
use App\Categoria;
use App\Exports\LineaExport;
use App\Imports\LineaImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon; // Agrega la clase Carbon para manejar fechas


class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $categorias = Categoria::orderBy('id', 'desc')->paginate(10);
        }
        else{
            $categorias = Categoria::where($criterio, 'like', '%'. $buscar . '%')->orderBy('id', 'desc')->paginate(6);
        }
        

        return [
            'pagination' => [
                'total'        => $categorias->total(),
                'current_page' => $categorias->currentPage(),
                'per_page'     => $categorias->perPage(),
                'last_page'    => $categorias->lastPage(),
                'from'         => $categorias->firstItem(),
                'to'           => $categorias->lastItem(),
            ],
            'categorias' => $categorias
        ];
    }
    public function index2(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $categorias = Categoria::orderBy('id', 'desc');
        }
        if (!empty($buscar)) {
            $categorias = Categoria::where(function ($q) use ($buscar) {
                $q->where('categorias.nombre', 'like', '%' . $buscar . '%')
                  ->orWhere('categorias.descripcion', 'like', '%' . $buscar . '%')
                  ->orWhere('categorias.codigoProductoSin', 'like', '%' . $buscar . '%')
                  ->orderBy('id', 'desc');
            });
        }
        $categorias = $categorias->get();
        return ['categorias' => $categorias];
    }
    public function selectCategoria(Request $request){
        if (!$request->ajax()) return redirect('/');
        $categorias = Categoria::where('condicion','=','1')
        ->select('id','nombre')->orderBy('nombre', 'asc')->get();
        return ['categorias' => $categorias];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $categoria = new Categoria();
        $categoria->nombre = $request->nombre;
        $categoria->descripcion = $request->descripcion;
        $categoria->codigoProductoSin = 1003655;
        $categoria->actividadEconomica = 4772100;

        $categoria->condicion = '1';
        //$categoria->condicion = '1';
        $categoria->save();
    }
  

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $categoria = Categoria::findOrFail($request->id);
        $categoria->nombre = $request->nombre;
        $categoria->descripcion = $request->descripcion;
        $categoria->codigoProductoSin = 62253;
        $categoria->actividadEconomica = 477300;
        $categoria->condicion = '1';
        //$categoria->condicion = '1';
        $categoria->save();
    }

    public function desactivar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $categoria = Categoria::findOrFail($request->id);
        $categoria->condicion = '0';
        $categoria->save();
    }

    public function activar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $categoria = Categoria::findOrFail($request->id);
        $categoria->condicion = '1';
        $categoria->save();
    }
    //---importacion--
    public function importsaveExecelUser(Request $request){
        $path = $request->file('select_users_file')->getRealPath();
        Excel::import(new LineaImport, $path);
    }
    //---exportar---
    public function excelLinea()
    {
        $fechaActual = Carbon::now()->format('Y-m-d_H-i-s'); // Obtiene la fecha actual en el formato deseado
        $nombreArchivo = 'linea_' . $fechaActual;

        // Puedes elegir entre 'xlsx' o 'csv' según el formato deseado
        return Excel::download(new LineaExport, $nombreArchivo . '.xlsx');
        //return Excel::download(new LineaExport, $nombreArchivo . '.csv');
    }
    
}
