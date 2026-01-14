<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Empresa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
class EmpresaController extends Controller
{

    protected $empresas;

    public function __construct(Empresa $empresas)
    {
        $this->empresas = $empresas;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if(!$request->ajax()) return redirect('/');

        $empresa = Empresa::first();
    
        return ['empresa' => $empresa];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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

        $empresa = Empresa::findOrFail($request->id);

        $empresa->nombre = $request->nombre;
        $empresa->direccion = $request->direccion;
        $empresa->telefono = $request->telefono;
        $empresa->email = $request->email;
        $empresa->nit = $request->nit;

        // Manejo del logo
        if ($request->hasFile('logo')) {

            $rutaDestino = public_path('img/logoPrincipal.png');

            // Eliminar logo anterior si existe
            if (File::exists($rutaDestino)) {
                File::delete($rutaDestino);
            }

            // Convertir a PNG
            Image::make($request->file('logo'))
                ->encode('png', 90) // calidad 0–100
                ->save($rutaDestino);
        }

        // Manejo de la imagen de fondo del login
        if ($request->hasFile('backLoginFarma')) {

            $rutaDestino = public_path('img/BackLoginFarma.jpg');

            // Eliminar imagen anterior si existe
            if (File::exists($rutaDestino)) {
                File::delete($rutaDestino);
            }

            // Convertir a JPG
            Image::make($request->file('backLoginFarma'))
                ->encode('jpg', 90) // calidad 0–100
                ->save($rutaDestino);
        }

        $empresa->save();

        return response()->json(['success' => true]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function selectEmpresa(Request $request){
        
        if (!$request->ajax()) return redirect('/');
        $empresas = Empresa::select('id','nombre')->orderBy('nombre', 'asc')->get();
        return ['empresas' => $empresas];
    }
}
