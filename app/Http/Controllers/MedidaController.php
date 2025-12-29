<?php

namespace App\Http\Controllers;

use App\Exports\MedidaExport;
use App\Imports\MedidaImport;
use Illuminate\Http\Request;
use App\Medida;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class MedidaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index2(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $medidas = Medida::orderBy('id', 'asc');
        }
        else{
            $medidas = Medida::where($criterio, 'like', '%'. $buscar . '%')->orderBy('id', 'desc');
        }
        $medidas=$medidas->get();

        return [
            
            'medidas' => $medidas
        ];
    }
    public function index(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $medidas = Medida::orderBy('id', 'asc')->paginate(10);
        }
        else{
            $medidas = Medida::where($criterio, 'like', '%'. $buscar . '%')->orderBy('id', 'desc')->paginate(5);
        }
        

        return [
            'paginationMedida' => [
                'total'        => $medidas->total(),
                'current_page' => $medidas->currentPage(),
                'per_page'     => $medidas->perPage(),
                'last_page'    => $medidas->lastPage(),
                'from'         => $medidas->firstItem(),
                'to'           => $medidas->lastItem(),
            ],
            'medidas' => $medidas
        ];
    }

    public function selectMedida(Request $request){
        if (!$request->ajax()) return redirect('/');
        $medidas = Medida::where('estado','=','1')
        ->select('id','descripcion_medida')->orderBy('descripcion_medida', 'asc')->get();
        return ['medidas' => $medidas];
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
        $medida = new Medida();
        $medida->descripcion_medida = $request->descripcion_medida;
        $medida->codigoClasificador = $request->codigoClasificador;
        $medida->estado = '1' ;
        $medida->save();
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
        $medida = Medida::findOrFail($request->id);
        $medida->descripcion_medida = $request->descripcion_medida;
        $medida->codigoClasificador = $request->codigoClasificador;
        $medida->estado = 1;

        $medida->save();
    }

    public function desactivar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $medida = Medida::findOrFail($request->id);
        $medida->estado = '0';
        $medida->save();
    }

    public function activar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
        $medida = Medida::findOrFail($request->id);
        $medida->estado = '1';
        $medida->save();
    }
    //---exportar---
    public function excelMedida()
    {
        $fechaActual = Carbon::now()->format('Y-m-d_H-i-s'); // Obtiene la fecha actual en el formato deseado
        $nombreArchivo = 'Medidas_' . $fechaActual;

        // Puedes elegir entre 'xlsx' o 'csv' según el formato deseado
        return Excel::download(new MedidaExport, $nombreArchivo . '.xlsx');
        //return Excel::download(new LineaExport, $nombreArchivo . '.csv');
    }
    //---importacion--
    public function importsaveExecelUser(Request $request){
        $path = $request->file('select_users_file')->getRealPath();
        Excel::import(new MedidaImport, $path);
    }
}
