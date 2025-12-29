<?php

namespace App\Http\Controllers;

use App\Promocion;
use App\PromocionArticulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PreciosEspecialesController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        $query = Promocion::query();

        if ($buscar !== '') {
            $query->where($criterio, 'like', '%' . $buscar . '%');
        }

        $precioEspecial = $query->where('tipo_promocion', 3)
            ->withCount('promocionArticulos as cantidad_articulos')
            ->orderBy('id', 'desc')
            ->paginate(6);

        return response()->json([
            'pagination' => [
                'total' => $precioEspecial->total(),
                'current_page' => $precioEspecial->currentPage(),
                'per_page' => $precioEspecial->perPage(),
                'last_page' => $precioEspecial->lastPage(),
                'from' => $precioEspecial->firstItem(),
                'to' => $precioEspecial->lastItem(),
            ],
            'ofertas' => $precioEspecial
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }
        try {
            DB::beginTransaction();

            $request->validate([
                'fecha_final' => 'required',
                'estado' => 'required',
                'tipo_promocion' => 'required',
                'nombre' => 'required',
                'articulos' => 'required|array|min:1',
                'precio_r1' =>'required',
                'rango_inicio_r1' =>'required',
                'rango_final_r1' =>'required',
                'precio_r2' => 'required',
                'rango_inicio_r2' =>'required',
                'rango_final_r2' =>'required',
                'precio_r3' => 'required',
                'rango_inicio_r3' =>'required',
                'rango_final_r3' =>'required',

            ]);
            $precioEspecial = new Promocion([
                'codigo' => $request->codigo,
                'precio' => $request->precio,
                'porcentaje' => $request->porcentaje,
                'fecha_final' => $request->fecha_final,
                'estado' => $request->estado,
                'tipo_promocion' => 3,
                'nombre' => $request->nombre,
                'precio_r1' =>$request->precio_r1,
                'rango_inicio_r1'=>$request->rango_inicio_r1,
                'rango_final_r1'=>$request->rango_final_r1,
                'precio_r2' =>$request->precio_r2,
                'rango_inicio_r2'=>$request->rango_inicio_r2,
                'rango_final_r2'=>$request->rango_final_r2,
                'precio_r3' =>$request->precio_r2,
                'rango_inicio_r3'=>$request->rango_inicio_r3,
                'rango_final_r3'=>$request->rango_final_r3
            ]);

            $precioEspecial->save();

            foreach ($request->articulos as $articulo) {
                $cantidad = isset($articulo['cantidad']) ? $articulo['cantidad'] : 0;
                PromocionArticulo::create([
                    'idpromociones' => $precioEspecial->id,
                    'idarticulos' => $articulo['id'],
                    'cantidad' => $cantidad,

                ]);
            }
 
            DB::commit();

            return response()->json(['message' => 'Precios especiales creados correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Error al crear oferta: ' . $e->getMessage());
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }
    public function update(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        try {
            DB::beginTransaction();

            $request->validate([
                'fecha_final' => 'required',
                'estado' => 'required',
                'tipo_promocion' => 'required',
                'nombre' => 'required',
                'articulos' => 'required|array|min:1',
                'precio_r1' =>'required',
                'rango_inicio_r1' =>'required',
                'rango_final_r1' =>'required',
                'precio_r2' => 'required',
                'rango_inicio_r2' =>'required',
                'rango_final_r2' =>'required',
                'precio_r3' => 'required',
                'rango_inicio_r3' =>'required',
                'rango_final_r3' =>'required',

            ]);
            $precioEspecial = Promocion::findOrFail($request->id);
            $precioEspecial->update([
                'codigo' => $request->codigo,
                'precio' => $request->precio,
                'porcentaje' => $request->porcentaje,
                'fecha_final' => $request->fecha_final,
                'estado' => $request->estado,
                'tipo_promocion' => 3,
                'nombre' => $request->nombre,
                'precio_r1' =>$request->precio_r1,
                'rango_inicio_r1'=>$request->rango_inicio_r1,
                'rango_final_r1'=>$request->rango_final_r1,

                'precio_r2' =>$request->precio_r2,
                'rango_inicio_r2'=>$request->rango_inicio_r2,
                'rango_final_r2'=>$request->rango_final_r2,

                'precio_r3' =>$request->precio_r3,
                'rango_inicio_r3'=>$request->rango_inicio_r3,
                'rango_final_r3'=>$request->rango_final_r3
            ]);

            PromocionArticulo::where('idpromociones', $precioEspecial->id)->delete();

            foreach ($request->articulos as $articulo) {
                $cantidad = isset($articulo['cantidad']) ? $articulo['cantidad'] : 0;

                PromocionArticulo::create([
                    'idpromociones' => $precioEspecial->id,
                    'idarticulos' => $articulo['id'],
                    'cantidad' => $cantidad,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Precios especiales actualizados correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['errors' => $e->getMessage()], 422);



        }
    }

    
    public function cambiarEstado (Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $precioEspecial = Promocion::findOrFail($request->id);
        $precioEspecial->estado = $request->estado;

        $precioEspecial->save();
    }
}
