<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\PromocionArticulo;
// use App\Models\Inventario;
use Illuminate\Http\Request;
use App\Promocion;
use App\Inventario;
use Illuminate\Support\Facades\Log;

class OfertaController extends Controller
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

        $ofertas = $query->where('tipo_promocion', 1)
            ->withCount('promocionArticulos as cantidad_articulos')
            ->orderBy('id', 'desc')
            ->paginate(6);

        return response()->json([
            'pagination' => [
                'total' => $ofertas->total(),
                'current_page' => $ofertas->currentPage(),
                'per_page' => $ofertas->perPage(),
                'last_page' => $ofertas->lastPage(),
                'from' => $ofertas->firstItem(),
                'to' => $ofertas->lastItem(),
            ],
            'ofertas' => $ofertas
        ]);
    }

    public function indexKits(Request $request)
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

        $ofertas = $query->where('tipo_promocion', 0)
            ->withCount('promocionArticulos as cantidad_articulos')
            ->orderBy('id', 'desc')
            ->paginate(6);

        return response()->json([
            'pagination' => [
                'total' => $ofertas->total(),
                'current_page' => $ofertas->currentPage(),
                'per_page' => $ofertas->perPage(),
                'last_page' => $ofertas->lastPage(),
                'from' => $ofertas->firstItem(),
                'to' => $ofertas->lastItem(),
            ],
            'ofertas' => $ofertas
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
                'codigo' => 'required',
                'precio' => 'required',
                'porcentaje' => 'required',
                'fecha_final' => 'required',
                'estado' => 'required',
                'tipo_promocion' => 'required',
                'nombre' => 'required',
                'articulos' => 'required|array|min:1',
            ]);
            $oferta = new Promocion([
                'codigo' => $request->codigo,
                'precio' => $request->precio,
                'porcentaje' => $request->porcentaje,
                'fecha_final' => $request->fecha_final,
                'estado' => $request->estado,
                'tipo_promocion' => $request->tipo_promocion,
                'nombre' => $request->nombre,
            ]);

            $oferta->save();

            foreach ($request->articulos as $articulo) {
                $cantidad = isset($articulo['cantidad']) ? $articulo['cantidad'] : 0;
                PromocionArticulo::create([
                    'idpromociones' => $oferta->id,
                    'idarticulos' => $articulo['id'],
                    'cantidad' => $cantidad,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Oferta y artículos asociados creados correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::info('Error al crear oferta: ' . $e->getMessage());
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }
    public function obtenerDatosPromocion(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $idPromocion = $request->idPromocion;

        $articulosPorPromocion = PromocionArticulo::with('articulo')->where('idpromociones', $idPromocion)->get();

        return response()->json(['articulosPorPromocion' => $articulosPorPromocion]);
    }
    public function obtenerDatosKit(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $idPromocion = $request->idPromocion;
        $mensajes = [];

        Log::info("ID de promoción: $idPromocion");

        // Obtener los datos de la promoción junto con los detalles de los artículos
        $articulosPorPromocion = PromocionArticulo::with('articulo')->where('idpromociones', $idPromocion)->get();

        Log::info("Cantidad de artículos en la promoción: " . count($articulosPorPromocion));

        // Verificar el stock de cada artículo en la tabla Inventario
        foreach ($articulosPorPromocion as $articuloPorPromocion) {
            $idArticulo = $articuloPorPromocion->idarticulos;
            $cantidad = $articuloPorPromocion->cantidad;

            Log::info("ID del artículo: $idArticulo, Cantidad: $cantidad");

            // Obtener el stock disponible en la tabla Inventario para el artículo actual
            $inventario = Inventario::where('idarticulo', $idArticulo)->first();

            if (!$inventario) {
                Log::info("No se encontró inventario para el artículo con ID: $idArticulo");
                $mensajes[] = "El artículo {$articuloPorPromocion->articulo->nombre} no tiene stock.";
            } else {
                Log::info("Stock disponible para el artículo con ID: $idArticulo - $inventario->saldo_stock");

                if ($cantidad > $inventario->saldo_stock) {
                    Log::info("Cantidad de artículo mayor que el stock disponible");
                    $articuloPorPromocion->disponible = false;
                    $mensajes[] = "El artículo {$articuloPorPromocion->articulo->nombre} no tiene suficiente stock disponible.";
                } else {
                    Log::info("Cantidad de artículo menor o igual al stock disponible");
                    $articuloPorPromocion->disponible = true;
                }
            }
        }

        $respuesta = [
            'articulosPorPromocion' => $articulosPorPromocion,
            'mensajes' => $mensajes,
        ];

        return response()->json($respuesta);
    }
    public function update(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        try {
            DB::beginTransaction();

            $request->validate([
                'codigo' => 'required',
                'precio' => 'required',
                'porcentaje' => 'required',
                'fecha_final' => 'required',
                'estado' => 'required',
                'tipo_promocion' => 'required',
                'nombre' => 'required',
                'articulos' => 'required|array|min:1',
            ]);
            $oferta = Promocion::findOrFail($request->id);
            $oferta->update([
                'codigo' => $request->codigo,
                'precio' => $request->precio,
                'porcentaje' => $request->porcentaje,
                'fecha_final' => $request->fecha_final,
                'estado' => $request->estado,
                'tipo_promocion' => $request->tipo_promocion,
                'nombre' => $request->nombre,
            ]);

            PromocionArticulo::where('idpromociones', $oferta->id)->delete();

            foreach ($request->articulos as $articulo) {
                $cantidad = isset($articulo['cantidad']) ? $articulo['cantidad'] : 0;

                PromocionArticulo::create([
                    'idpromociones' => $oferta->id,
                    'idarticulos' => $articulo['id'],
                    'cantidad' => $cantidad,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Oferta y artículos asociados actualizados correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['errors' => $e->getMessage()], 422);



        }
    }
    public function modificarEstado(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $oferta = Promocion::findOrFail($request->id);
        $oferta->estado = $request->estado;

        $oferta->save();
    }


    public function obtenerPromocionPorIdArticulo(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        try {
            $idArticulo = $request->idArticulo;

            // Busca la fila en la tabla promociones_articulos por el idarticulos
            $promocionArticulo = PromocionArticulo::where('idarticulos', $idArticulo)
                ->where('cantidad', 0)
                ->first();

            if (!$promocionArticulo) {
                return response()->json(['message' => 'No se encontró ninguna promoción para el artículo.']);
            }

            // Obtiene la fila correspondiente en la tabla promociones usando el idpromociones
            $idPromocion = $promocionArticulo->idpromociones;
            $promocion = Promocion::where('id', $idPromocion)
                ->where('tipo_promocion', 1)
                ->first();


            return response()->json(['promocion' => $promocion]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
