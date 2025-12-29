<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Illuminate\Support\Facades\DB;
use App\Marca;
use Illuminate\Support\Facades\Log;
use App\Imports\MarcaImport;
use App\Exports\MarcaExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon; // Agrega la clase Carbon para manejar fechas

class MarcaController extends Controller
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
            $marcas = Marca::orderBy('id', 'desc')->paginate(10);
        }
        else{
            $marcas = Marca::where($criterio, 'like', '%'. $buscar . '%')->orderBy('id', 'desc')->paginate(8);
        }
        

        return [
            'pagination' => [
                'total'        => $marcas->total(),
                'current_page' => $marcas->currentPage(),
                'per_page'     => $marcas->perPage(),
                'last_page'    => $marcas->lastPage(),
                'from'         => $marcas->firstItem(),
                'to'           => $marcas->lastItem(),
            ],
            'marcas' => $marcas
        ];
    }
    public function index2(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $marcas = Marca::orderBy('id', 'desc');
        }
        else{
            $marcas = Marca::where($criterio, 'like', '%'. $buscar . '%')->orderBy('id', 'desc');
        }
        
        $marcas= $marcas->get();
        return [ 'marcas' => $marcas
        ];
    }

    public function selectCategoria(Request $request){
        if (!$request->ajax()) return redirect('/');
        $marcas = Marca::where('condicion','=','1')
        ->select('id','nombre')->orderBy('nombre', 'asc')->get();
        return ['marcas' => $marcas];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info("LLEGO aca");
        if (!$request->ajax()) return redirect('/');
        $marca = new Marca();

        $marca->nombre = $request->nombre;
        $marca->condicion = '1';
        //$marca->condicion = '1';
        $marca->save();
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
        $marca = Marca::findOrFail($request->id);
        $marca->nombre = $request->nombre;
        $marca->condicion = '1';
        //$marca->condicion = '1';
        $marca->save();
    }

    public function desactivar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $marca = Marca::findOrFail($request->id);
        $marca->condicion = '0';
        $marca->save();
    }

    public function activar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $marca = Marca::findOrFail($request->id);
        $marca->condicion = '1';
        $marca->save();
    }

    public function selectMarca(Request $request){
        if (!$request->ajax()) return redirect('/');
        $marcas = Marca::where('condicion','=','1')
        ->select('id','nombre')->orderBy('nombre', 'asc')->get();
        return ['marcas' => $marcas];
    }   
    //-----importar de excel --
    public function saveExecelUser(Request $request){
        $path = $request->file('select_users_file')->getRealPath();
        Excel::import(new MarcaImport, $path);
    }

    //-------Exportar datos a excel ----
    // public function excelMarca()
    // {
    //     // return Excel::download(new MarcaExport, 'marcas.xlsx');
    //     // en csv
    //     return Excel::download(new MarcaExport, 'marcas.csv');
    // }
    public function excelMarca()
    {
        $fechaActual = Carbon::now()->format('Y-m-d_H-i-s'); // Obtiene la fecha actual en el formato deseado
        $nombreArchivo = 'marcas_' . $fechaActual;

        // Puedes elegir entre 'xlsx' o 'csv' según el formato deseado
        // return Excel::download(new MarcaExport, $nombreArchivo . '.xlsx');
        return Excel::download(new MarcaExport, $nombreArchivo . '.csv');
    }
}
