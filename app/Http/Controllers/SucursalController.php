<?php

namespace App\Http\Controllers;


use App\Empresa;
use Illuminate\Http\Request;
use App\Sucursales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class SucursalController extends Controller
{
    //listar datos de sucursales
    public function index(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
    
        $buscar = $request->buscar;
    
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;
        $idsucursal = $usuario->idsucursal;
    
        $query = Sucursales::join('empresas', 'sucursales.idempresa', '=', 'empresas.id')
            ->select(
                'sucursales.id',
                'sucursales.idempresa',
                'empresas.nombre as nombre_empresa',
                'sucursales.nombre',
                'sucursales.direccion',
                'sucursales.correo',
                'sucursales.telefono',
                'sucursales.departamento',
                'sucursales.codigoSucursal',
                'sucursales.condicion'
            );
    
        // Aplicar filtro por sucursal si el usuario es rol 1
        //if ($idrol == 1) {
        //    $query->where('sucursales.id', $idsucursal);
        //}
    
        // Búsqueda global en varias columnas
        if (!empty($buscar)) {
            $query->where(function($q) use ($buscar) {
                $q->where('sucursales.nombre', 'like', "%$buscar%")
                  ->orWhere('sucursales.direccion', 'like', "%$buscar%")
                  ->orWhere('sucursales.correo', 'like', "%$buscar%")
                  ->orWhere('sucursales.telefono', 'like', "%$buscar%")
                  ->orWhere('sucursales.departamento', 'like', "%$buscar%")
                  ->orWhere('empresas.nombre', 'like', "%$buscar%")
                  ->orWhere('sucursales.codigoSucursal', 'like', "%$buscar%")
                ;
            });
        }
    
        $sucursales = $query->orderBy('sucursales.id', 'asc')->paginate(6);
    
        return [
            'pagination' => [
                'total' => $sucursales->total(),
                'current_page' => $sucursales->currentPage(),
                'per_page' => $sucursales->perPage(),
                'last_page' => $sucursales->lastPage(),
                'from' => $sucursales->firstItem(),
                'to' => $sucursales->lastItem(),
            ],
            'sucursales' => $sucursales
        ];
    }
    
    public function obtenerUltimoCodigoSucursal()
    {
        $ultimoCodigo = DB::table('sucursales')
            ->select('codigoSucursal')
            ->orderBy('codigoSucursal', 'desc')
            ->first();

        return response()->json(['ultimoCodigo' => $ultimoCodigo->codigoSucursal]);
    }

    public function store(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        // Registra la información en el log antes de realizar la operación de almacenamiento
        Log::info('Datos recibidos para registrar una nueva sucursal:', $request->all());

        $sucursal = new Sucursales();

        $sucursal->idempresa = Empresa::first()->id;
        $sucursal->nombre = $request->nombre;
        $sucursal->direccion = $request->direccion ?? '';
        $sucursal->correo = $request->correo ?? '';
        $sucursal->telefono = $request->telefono;
        $sucursal->departamento = $request->departamento;
        $sucursal->codigoSucursal = $request->codigoSucursal;

        $sucursal->condicion = '1';
        $sucursal->save();

        // Registra la información en el log después de realizar la operación de almacenamiento
        Log::info('Nueva sucursal registrada con éxito:', ['id' => $sucursal->id]);

        // Resto del código...
    }
    //---------hasa qui
    //-------actualizar datos
    public function update(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        Log::info('Datos recibidos para actualizar sucursal:', $request->all());

        try {
            // Validar los datos antes de intentar actualizar la sucursal
            $this->validate($request, [
                'id' => 'required|exists:sucursales,id',
                'idempresa' => 'required',
                'nombre' => 'required',
                'direccion' => 'required',
                'correo' => 'required|email',
                'telefono' => 'required',
                'departamento' => 'required',
                'codigoSucursal' => 'required',
            ]);

            // Actualizar la sucursal
            $sucursal = Sucursales::findOrFail($request->id);
            $sucursal->idempresa = $request->idempresa;
            $sucursal->nombre = $request->nombre;
            $sucursal->direccion = $request->direccion;
            $sucursal->correo = $request->correo;
            $sucursal->telefono = $request->telefono;
            $sucursal->departamento = $request->departamento;
            $sucursal->codigoSucursal = $request->codigoSucursal;
            $sucursal->condicion = '1';
            $sucursal->save();

            return response()->json(['message' => 'Sucursal actualizada con éxito'], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Error al actualizar sucursal: No se encontró la sucursal con ID ' . $request->id);
            return response()->json(['error' => 'No se encontró la sucursal con el ID proporcionado'], 404);
        } catch (ValidationException $e) {
            Log::error('Error de validación al actualizar sucursal:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar sucursal:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al actualizar la sucursal'], 500);
        }
    }
    //-----------hasta aqui
    //---desactivar registro

    public function desactivar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $sucursal = Sucursales::findOrFail($request->id);
        $sucursal->condicion = '0';
        $sucursal->save();
    }

    public function activar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $sucursal = Sucursales::findOrFail($request->id);
        $sucursal->condicion = '1';
        $sucursal->save();
    }
    //--------hasta aqui

    public function selectSucursal(Request $request)
    {
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;

        $query = Sucursales::where('condicion', '=', '1');

        /*if ($idrol == 1) {
            // Mostrar solo su propia sucursal
            $query->where('id', $usuario->idsucursal);
        }*/

        $sucursales = $query->select('id', 'nombre')
                            ->orderBy('nombre', 'asc')
                            ->get();

        return ['sucursales' => $sucursales];
    }


    public function selectedSucursal(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
    
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;
        $idsucursal = $usuario->idsucursal;
        $filtro = $request->filtro;
    
        $query = Sucursales::query();
    
        // Si el usuario NO es de rol 4, filtrar por su sucursal
        /*if ($idrol != 4) {
            $query->where('id', $idsucursal);
        }*/
    
        // Aplicar filtro de búsqueda si viene texto
        if (!empty($filtro)) {
            $query->where(function ($q) use ($filtro) {
                $q->where('nombre', 'like', '%' . $filtro . '%')
                  ->orWhere('codigoSucursal', 'like', '%' . $filtro . '%');
            });
        }
    
        $sucursales = $query->orderBy('nombre', 'asc')->get();
    
        return ['sucursales' => $sucursales];
    }
}
