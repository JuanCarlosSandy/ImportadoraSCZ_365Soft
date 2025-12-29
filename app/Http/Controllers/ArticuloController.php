<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Exports\ProductExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Inventario;
use App\Articulo;
use App\Imports\ArticuloImport;

use App\Precio;

class ArticuloController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;

        $query = Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->select(
                'articulos.id',
                'articulos.idcategoria',
                'articulos.idproveedor',
                'articulos.idmedida',
                'articulos.codigo',
                'articulos.nombre',
                'articulos.nombre_generico',
                'articulos.costo_compra',
                'articulos.vencimiento',
                'articulos.unidad_envase',
                'articulos.precio_list_unid',
                'articulos.precio_costo_unid',
                'articulos.precio_costo_paq',
                'categorias.nombre as nombre_categoria',
                'medidas.descripcion_medida',
                'articulos.precio_uno',
                'articulos.precio_dos',
                'articulos.precio_tres',
                'articulos.precio_cuatro',
                'articulos.precio_venta',
                'articulos.stock',
                'personas.nombre as nombre_proveedor',
                'articulos.descripcion',
                'articulos.condicion',
                'articulos.fotografia',
                'articulos.codigo_alfanumerico',
                'articulos.descripcion_fabrica',
                'articulos.descuento',
                'articulos.fecha_venc_descuento'
            )
            ->where('articulos.condicion', '=', 1);

        if (!empty($buscar)) {
            $palabras = explode(' ', $buscar);
            $query->where(function ($q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $q->where(function ($sub) use ($palabra) {
                        $sub->where('articulos.nombre', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.descripcion', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.codigo', 'like', '%' . $palabra . '%')
                            ->orWhere('personas.nombre', 'like', '%' . $palabra . '%')
                            ->orWhere('categorias.nombre', 'like', '%' . $palabra . '%');
                    });
                }
            });
        }
        $articulos = $query->distinct()->orderBy('articulos.id', 'desc')->paginate(10);

        // Formatear decimales + añadir " Bs"
        $articulos->getCollection()->transform(function ($item) {
            $item->precio_costo_unid = number_format((float)$item->precio_costo_unid, 2, '.', '') . ' Bs';
            $item->precio_costo_paq = number_format((float)$item->precio_costo_paq, 2, '.', '') . ' Bs';
            return $item;
        });
        return [
            'pagination' => [
                'total' => $articulos->total(),
                'current_page' => $articulos->currentPage(),
                'per_page' => $articulos->perPage(),
                'last_page' => $articulos->lastPage(),
                'from' => $articulos->firstItem(),
                'to' => $articulos->lastItem(),
            ],
            'articulos' => $articulos,
            'idrol' => $idrol
        ];
    }
     public function detalle($id)
    {
        $articulo = Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->select(
                'articulos.*',
                'categorias.nombre as nombre_categoria',
                'personas.nombre as nombre_proveedor',
                'medidas.descripcion_medida'
            )
            ->where('articulos.id', $id)
            ->first();

        return response()->json($articulo);
    }

    public function index2(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        if ($buscar == '') {
            $articulos = Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('personas', 'proveedores.id', '=', 'personas.id')
                ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')

                ->select(
                    'articulos.id',
                    'articulos.idcategoria',
                    'articulos.idproveedor',
                    //aumente 7 julio
                    'articulos.idmedida',
                    'articulos.codigo',
                    'articulos.nombre',
                    'articulos.nombre_generico',
                    'articulos.costo_compra',
                    //aumente12julio
                    'articulos.vencimiento',
                    'articulos.unidad_envase',
                    'articulos.precio_list_unid',
                    'articulos.precio_costo_unid',
                    'articulos.precio_costo_paq',

                    'categorias.nombre as nombre_categoria',
                    'medidas.descripcion_medida',
                    //aumente 5 julio

                    'articulos.precio_uno',
                    'articulos.precio_dos',
                    'articulos.precio_tres',
                    'articulos.precio_cuatro',

                    'articulos.precio_venta',
                    'articulos.stock',
                    'personas.nombre as nombre_proveedor',
                    'articulos.descripcion',
                    'articulos.condicion',
                    'articulos.fotografia',
                    // agregado el 26.01,2024

                    'articulos.codigo_alfanumerico',
                    'articulos.descripcion_fabrica'
                )
                ->distinct()
                ->orderBy('articulos.id', 'desc');
        } else {
            $articulos = Articulo::join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('personas', 'proveedores.id', '=', 'personas.id')
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->select(
                    'articulos.id',
                    'articulos.idcategoria',
                    'articulos.codigo',
                    'articulos.nombre',

                    'articulos.unidad_envase',
                    'articulos.precio_list_unid',
                    'articulos.precio_costo_unid',
                    'articulos.precio_costo_paq',

                    'categorias.nombre as nombre_categoria',
                    //aumente 5 julio

                    'articulos.precio_uno',
                    'articulos.precio_dos',
                    'articulos.precio_tres',
                    'articulos.precio_cuatro',

                    'articulos.precio_venta',
                    'articulos.stock',
                    'personas.nombre as nombre_proveedor',
                    'articulos.descripcion',
                    'articulos.condicion',
                    'articulos.fotografia',
                    // agregado el 26.01,2024

                    'articulos.codigo_alfanumerico',
                    'articulos.descripcion_fabrica'
                )
                ->where('articulos.' . $criterio, 'like', '%' . $buscar . '%')
                ->distinct()
                ->orderBy('articulos.id', 'desc');
        }
        $articulos = $articulos->get();

        return [
            'articulos' => $articulos
        ];
    }

    public function buscadorGlobal(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $idProveedor = $request->input('idProveedor');
        $filtro = $request->input('buscar');

        $campos = [
            'articulos.id',
            'articulos.codigo',
            'articulos.nombre',
            'articulos.precio_costo_unid',
            'articulos.precio_costo_paq',
            'articulos.unidad_envase',
            'personas.nombre as nombre_proveedor',
            'categorias.nombre as nombre_categoria'
        ];

        $query = Articulo::select($campos)
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->where('idproveedor', $idProveedor)
            ->orderBy('articulos.id', 'desc');

        if ($filtro) {
            $query->where(function ($q) use ($filtro) {
                $q->where('articulos.nombre', 'like', '%' . $filtro . '%')
                    ->orWhere('articulos.codigo', 'like', '%' . $filtro . '%');
            });
        }

        $productos = $query->paginate(10);

        return response()->json(["articulos" => $productos]);
    }
    public function updateDescuento(Request $request, $id)
    {
        try {
            $articulo = Articulo::findOrFail($id);

            // 🔹 Limpiar la fecha si viene con formato ISO (ej: 2025-04-05T04:00:00.000Z)
            $fecha = $request->input('fecha_venc_descuento');
            if ($fecha) {
                // Si contiene una "T", recortamos solo la parte de fecha
                $fecha = strpos($fecha, 'T') !== false ? substr($fecha, 0, 10) : $fecha;
            }

            // 🔹 Actualizar los campos
            $articulo->descuento = $request->input('descuento');
            $articulo->fecha_venc_descuento = $fecha;
            $articulo->updated_at = now();
            $articulo->save();

            return response()->json([
                'message' => 'Descuento actualizado correctamente',
                'data' => [
                    'id' => $articulo->id,
                    'descuento' => $articulo->descuento,
                    'fecha_venc_descuento' => $articulo->fecha_venc_descuento,
                    'updated_at' => $articulo->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el descuento',
                'details' => $e->getMessage()
            ], 500);
        }
    }



    public function listarArticulo(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $idProveedor = $request->idProveedor;

        $query = Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'articulos.id',
                'articulos.idcategoria',
                'articulos.codigo',
                'articulos.nombre',
                'categorias.nombre as nombre_categoria',
                'articulos.precio_costo_unid',
                'articulos.stock',
                'personas.nombre as nombre_proveedor',
                'articulos.descripcion',
                'articulos.condicion',
                'articulos.unidad_envase',
                'articulos.fotografia',
                'articulos.precio_costo_paq',
                'articulos.vencimiento',
                'articulos.codigo_alfanumerico',
                'articulos.descripcion_fabrica',
                'articulos.precio_uno',
            )
            ->where('articulos.condicion', '=', 1);

        // Aplicar búsqueda global si hay texto
        if (!empty($buscar)) {
            $palabras = explode(' ', $buscar); // Dividir el texto en palabras
            $query->where(function ($q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $q->where(function ($sub) use ($palabra) {
                        $sub->where('articulos.nombre', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.descripcion', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.codigo', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.codigo_alfanumerico', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.descripcion_fabrica', 'like', '%' . $palabra . '%')
                            ->orWhere('personas.nombre', 'like', '%' . $palabra . '%');
                    });
                }
            });
        }


        $articulos = $query->orderBy('articulos.id', 'desc')->get();

        return ['articulos' => $articulos];
    }
    public function actualizarPrecioVenta(Request $request)
    {
        try {
            $articulo = Articulo::findOrFail($request->id);

            // Validar campos
            if ($request->precio_uno === null) {
                return response()->json([
                    "status" => "error",
                    "message" => "El precio de venta es requerido"
                ], 400);
            }

            $articulo->precio_uno = $request->precio_uno;
            $articulo->save();

            return response()->json([
                "status" => "success",
                "message" => "Precio de venta actualizado correctamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Error al actualizar precio de venta: " . $e->getMessage()
            ], 500);
        }
    }

    public function verificarLotes(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $articuloId = $request->articuloId;
        $idAlmacen = $request->idalmacen;
        $lotes = Inventario::where('idarticulo', $articuloId)
            ->where('idalmacen', $idAlmacen)
            ->where('saldo_stock', '>', 0)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();
        return ['lotes' => $lotes];
    }


    public function listarArticuloVenta(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }
        $buscar = $request->buscar;
        $criterio = $request->criterio;
        $idAlmacen = $request->idAlmacen;

        if (empty($buscar)) {
            return ['articulos' => []];
        }

        $palabrasBuscar = array_filter(explode(" ", $buscar));

        $articulos = DB::table('articulos')
            ->leftJoin('inventarios', function ($join) use ($idAlmacen) {
                $join->on('articulos.id', '=', 'inventarios.idarticulo')
                    ->where('inventarios.idalmacen', '=', $idAlmacen);
            })
            ->leftJoin('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->select(
                'articulos.id',
                'articulos.nombre',
                'articulos.precio_uno',
                'articulos.precio_dos',
                'articulos.precio_tres',
                'articulos.precio_cuatro',
                'articulos.fotografia',
                'articulos.unidad_envase',
                DB::raw("IFNULL(almacens.nombre_almacen, 'Sin asignar') as nombre_almacen"),
                DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) as saldo_stock'),
                'articulos.codigo',
                'articulos.precio_venta',
                'articulos.condicion',
                'personas.nombre as contacto',
                'categorias.nombre as nombre_categoria',
                'categorias.codigoProductoSin',
                'categorias.actividadEconomica',
                'articulos.descripcion_fabrica',
                'medidas.descripcion_medida as medida',
                'medidas.codigoClasificador as codigoClasificador',
                'articulos.descuento', 
                'articulos.fecha_venc_descuento'
            )
            ->where('articulos.condicion', '=', 1)
            ->groupBy(
                'articulos.id',
                'articulos.nombre',
                'articulos.precio_uno',
                'articulos.precio_dos',
                'articulos.precio_tres',
                'articulos.precio_cuatro',
                'articulos.fotografia',
                'articulos.unidad_envase',
                'almacens.nombre_almacen',
                'articulos.codigo',
                'articulos.precio_venta',
                'articulos.condicion',
                'personas.nombre',
                'categorias.nombre',
                'articulos.descripcion_fabrica',
                'categorias.codigoProductoSin',
                'categorias.actividadEconomica',
                'medidas.descripcion_medida',
                'medidas.codigoClasificador',
                'articulos.descuento',
                'articulos.fecha_venc_descuento' 
            );

        if (!empty($palabrasBuscar)) {
            $articulos->where(function ($query) use ($palabrasBuscar, $buscar) {
                foreach ($palabrasBuscar as $palabra) {
                    $query->where(function ($sub) use ($palabra) {
                        $sub->where('articulos.nombre', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.codigo', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.codigo_alfanumerico', 'like', '%' . $palabra . '%')
                            ->orWhere('categorias.nombre', 'like', '%' . $palabra . '%')
                            ->orWhere('personas.nombre', 'like', '%' . $palabra . '%');
                    });
                }
                $query->orWhere('articulos.codigo', 'like', '%' . $buscar . '%')
                    ->orWhere('articulos.codigo_alfanumerico', 'like', '%' . $buscar . '%')
                    ->orWhere('categorias.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.nombre', 'like', '%' . $buscar . '%');
            });
        }

        $articulos->orderByRaw("
            CASE
                WHEN articulos.nombre LIKE ? THEN 1
                WHEN articulos.codigo LIKE ? THEN 1
                WHEN articulos.codigo_alfanumerico LIKE ? THEN 1
                WHEN articulos.nombre LIKE ? THEN 2
                WHEN articulos.codigo LIKE ? THEN 2
                WHEN articulos.codigo_alfanumerico LIKE ? THEN 2
                ELSE 3
            END
        ", ["{$buscar}%", "{$buscar}%", "{$buscar}%", "%{$buscar}%", "%{$buscar}%", "%{$buscar}%"]);

        $articulos->orderBy('articulos.nombre', 'asc');
        $resultados = $articulos->get();

        return ['articulos' => $resultados];
    }

    public function descargarExcel()
    {
        return Excel::download(new ProductExport, 'articulos.xlsx');
    }
    public function buscarArticulo(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->filtro;
        $articulos = Articulo::where('codigo', '=', $filtro)
            ->select('id', 'codigo', 'nombre', 'precio_costo_unid', 'fotografia', 'unidad_envase', 'descripcion')->orderBy('nombre', 'asc')->take(1)->get();

        return ['articulos' => $articulos];
    }
   public function buscarArticuloVenta(Request $request)
{
    if (!$request->ajax())
        return redirect('/');

    $filtro = trim($request->filtro);
    $idAlmacen = $request->idalmacen;

    // 🔹 Obtener configuración
    $config = DB::table('configuracion_trabajos')->first();
    $permitir_ofertas = $config->permitir_ofertas ?? 0;

    $articulos = Articulo::join('medidas', 'articulos.idmedida', '=', 'medidas.id')
        ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
        ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
        ->join('personas', 'proveedores.id', '=', 'personas.id')
        ->leftJoin('inventarios', function ($join) use ($idAlmacen) {
            $join->on('inventarios.idarticulo', '=', 'articulos.id')
                 ->where('inventarios.idalmacen', '=', $idAlmacen);
        })
        ->select(
            'articulos.id',
            'articulos.nombre',
            'articulos.codigo',
            'articulos.codigo_alfanumerico',
            'articulos.descripcion',
            'articulos.precio_uno',
            'articulos.precio_dos',
            'articulos.precio_tres',
            'articulos.precio_cuatro',
            'articulos.precio_costo_unid',
            'articulos.precio_costo_paq',
            'articulos.fotografia',
            'articulos.condicion',
            'categorias.nombre as nombre_categoria',
            'medidas.descripcion_medida as medida',
            'medidas.codigoClasificador as codigoClasificador',
            'categorias.codigoProductoSin',
            'categorias.actividadEconomica',
            'unidad_envase',
            'articulos.descripcion_fabrica',
            'personas.nombre as nombre_proveedor',
            'articulos.descuento',
            'articulos.fecha_venc_descuento',
            DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) as saldo_stock'),
            DB::raw('ROUND(IFNULL(SUM(inventarios.saldo_stock) / NULLIF(articulos.unidad_envase, 0), 0), 2) as saldo_stock_cajas')
            )        
        ->where('articulos.condicion', '=', 1)
        ->where(function ($query) use ($filtro) {
            $palabras = preg_split('/\s+/', $filtro);
            foreach ($palabras as $palabra) {
                $query->where(function ($sub) use ($palabra) {
                    $sub->where('articulos.nombre', 'LIKE', "%{$palabra}%")
                        ->orWhere('articulos.codigo', 'LIKE', "%{$palabra}%")
                        ->orWhere('articulos.descripcion', 'LIKE', "%{$palabra}%")
                        ->orWhere('articulos.codigo_alfanumerico', 'LIKE', "%{$palabra}%");
                });
            }
        })
        ->groupBy(
            'articulos.id','articulos.nombre','articulos.codigo','articulos.codigo_alfanumerico',
            'articulos.descripcion','articulos.precio_uno','articulos.precio_dos','articulos.precio_tres',
            'articulos.precio_cuatro','articulos.precio_costo_unid','articulos.precio_costo_paq',
            'articulos.fotografia','articulos.condicion','categorias.nombre','medidas.descripcion_medida',
            'medidas.codigoClasificador','categorias.codigoProductoSin','categorias.actividadEconomica',
            'unidad_envase','articulos.descripcion_fabrica','personas.nombre','articulos.descuento',
            'articulos.fecha_venc_descuento'
        )
        ->orderByRaw("
            CASE
                WHEN articulos.nombre LIKE ? THEN 1
                WHEN articulos.nombre LIKE ? THEN 2
                ELSE 3
            END
        ", ["{$filtro}%", "%{$filtro}%"])
        ->orderBy('articulos.nombre', 'asc')
        ->take(10)
        ->get();

    // 🔹 Aplicar regla: si NO se permite ofertas, descuento = "0.00"
    if ($permitir_ofertas == 0) {
        foreach ($articulos as $art) {
            $art->descuento = "0.00";
        }
    }

    return ['articulos' => $articulos];
}

    public function store(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        // Validar si ya existe un artículo con el mismo código
        $articuloExistente = Articulo::where('codigo', $request->codigo)->first();
        if ($articuloExistente) {
            return response()->json([
                'error' => true,
                'message' => 'Ya existe un medicamento con el mismo codigo: ' . $articuloExistente->nombre
            ], 409);
        }

        $articulo = new Articulo();
        $articulo->idcategoria = $request->idcategoria;
        $articulo->vencimiento = $request->fechaVencimientoSeleccion;
        $articulo->idmedida = 1;
        $articulo->codigo = $request->codigo;
        $articulo->nombre = $request->nombre;
        $articulo->nombre_generico = $request->nombre;
        $articulo->unidad_envase = $request->unidad_envase;
        $articulo->precio_venta = $request->precio_uno;
        $articulo->precio_uno = $request->precio_uno;
        $articulo->precio_dos = $request->precio_dos;
        $articulo->precio_tres = 0.00;
        $articulo->precio_cuatro = 0.00;
        $articulo->costo_compra = $request->costo_compra;
        $articulo->stock = $request->stock;
        $articulo->idproveedor = $request->idproveedor;
        $articulo->precio_costo_unid = $request->precio_costo_unid;
        $articulo->precio_costo_paq = $request->precio_costo_unid;
        $articulo->descripcion = $request->descripcion;
        $articulo->codigo_alfanumerico = $request->codigo_alfanumerico;
        $articulo->descripcion_fabrica = $request->descripcion_fabrica;
        $articulo->condicion = '1';
        $nombreimagen = '';
        if ($request->hasFile('fotografia')) {
            if ($request->hasFile('fotografia')) {
                $imagen = $request->file("fotografia");
                $nombreimagen = Str::slug($request->nombre) . "." . $imagen->guessExtension();
                $ruta = public_path("img/articulo/");
                if (!File::isDirectory($ruta)) {
                    File::makeDirectory($ruta, 0755, true);
                }
                copy($imagen->getRealPath(), $ruta . $nombreimagen);
                $articulo->fotografia = $nombreimagen;
            }
        }
        $articulo->save();
        return ['idArticulo' => $articulo->id];
    }
    public function update(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        try {
            DB::beginTransaction();

            $articulo = Articulo::findOrFail($request->id);

            // 1. Guardar copia de los precios actuales (antes de sobrescribir)
            $originalPrices = $articulo->only([
                'precio_venta',
                'precio_uno',
                'precio_dos',
                'precio_tres',
                'precio_cuatro',
                'precio_costo_paq',
                'precio_costo_unid',
                'costo_compra',
            ]);

            // 2. Asignar todos los campos como lo haces normalmente
            $articulo->idcategoria = $request->idcategoria;
            $articulo->codigo = $request->codigo;
            $articulo->nombre = $request->nombre;

            $articulo->precio_venta = $request->precio_venta;
            $articulo->precio_costo_paq = $request->precio_costo_unid;
            $articulo->precio_costo_unid = $request->precio_costo_unid;

            $articulo->precio_uno = $request->precio_uno;
            $articulo->precio_dos = $request->precio_dos;
            $articulo->precio_tres = $request->precio_tres;
            $articulo->precio_cuatro = $request->precio_cuatro;

            $articulo->costo_compra = $request->costo_compra;

            $articulo->stock = $request->stock;
            $articulo->descripcion = $request->descripcion;
            $articulo->vencimiento = $request->fechaVencimientoSeleccion;
            $articulo->unidad_envase = $request->unidad_envase;
            $articulo->idproveedor = $request->idproveedor;
            $articulo->idmedida = 1;
            $articulo->codigo_alfanumerico = $request->codigo_alfanumerico;
            $articulo->descripcion_fabrica = $request->descripcion_fabrica;

            // Imagen
            if ($request->hasFile('fotografia')) {
                if ($articulo->fotografia != '' && Storage::exists('public/img/articulo/' . $articulo->fotografia)) {
                    Storage::delete('public/img/articulo/' . $articulo->fotografia);
                }

                $imagen = $request->file("fotografia");
                $nombreimagen = Str::slug($request->nombre) . "." . $imagen->guessExtension();
                $imagen->storeAs('public/img/articulo', $nombreimagen);
                copy($imagen->getRealPath(), public_path("img/articulo/") . $nombreimagen);

                $articulo->fotografia = $nombreimagen;
            }

            // 3. Comparar los valores originales vs. los nuevos
            $precioCambio = false;
            foreach ($originalPrices as $key => $originalValue) {
                $nuevoValor = $articulo->$key;

                // Para campos float, usar round para evitar problemas de formato
                if (round(floatval($originalValue), 2) !== round(floatval($nuevoValor), 2)) {
                    $precioCambio = true;
                    break;
                }
            }

            if ($precioCambio) {
                $articulo->precio_actualizado_en = now();
            }

            $articulo->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar artículo: ' . $e->getMessage());
        }
    }



    public function desactivar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $articulo = Articulo::findOrFail($request->id);
        $articulo->condicion = '0';
        $articulo->save();
    }

    public function activar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $articulo = Articulo::findOrFail($request->id);
        $articulo->condicion = '1';
        $articulo->save();
    }
    //--------LISTADO PARA PEDIDO PROVEEDOR---------------
    public function listPedProve(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        Log::info('Data', [
            'idProveedor' => $request->idProveedor,
            'buscar' => $request->buscar,
            'criterio' => $request->criterio,
        ]);

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        $idProveedor = $request->idProveedor;

        $articulos = Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'articulos.id',
                //'articulos.idcategoria', 
                'articulos.codigo',
                'articulos.nombre as articulo',
                //'categorias.nombre as nombre_categoria', 
                'articulos.precio_costo_unid as precio',
                'articulos.precio_costo_paq',
                //'articulos.stock', 
                'personas.nombre as nombre_proveedor',
                'articulos.fotografia',
                'articulos.descripcion',
                //'articulos.fecha_vencimiento', 
                //'articulos.condicion', 
                'articulos.unidad_envase as unidad_x_paquete',
            )
            ->where('proveedores.id', '=', $idProveedor);
        //->orderBy('articulos.id', 'desc')->paginate(10);
        if (!empty($buscar)) {
            $articulos = $articulos->where(function ($query) use ($criterio, $buscar) {
                $query->where('articulos.' . $criterio, 'like', '%' . $buscar . '%');
            });
        }
        $articulos = $articulos->orderBy('articulos.id', 'desc')->paginate(6);
        return [
            'pagination' => [
                'total' => $articulos->total(),
                'current_page' => $articulos->currentPage(),
                'per_page' => $articulos->perPage(),
                'last_page' => $articulos->lastPage(),
                'from' => $articulos->firstItem(),
                'to' => $articulos->lastItem(),
            ],
            'articulos' => $articulos
        ];
    }

    public function importar(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|mimes:xlsx,xls',
            ]);

            $archivo = $request->file('archivo');

            $import = new ArticuloImport();
            Excel::import($import, $archivo);

            $errors = $import->getErrors();

            if (!empty($errors)) {
                return response()->json(['errors' => $errors], 422);
            } else {
                return response()->json(['mensaje' => 'Importación exitosa'], 200);
            }
        } catch (Exception $e) {
            Log::error('Error en la importación: ' . $e->getMessage());

            return response()->json(['error' => 'Error en la importación', 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function editarCostoUnidadPaquete(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        try {
            DB::beginTransaction();

            $articulo = Articulo::findOrFail($request->id);
            $articulo->precio_costo_unid = $request->precio_costo_unidad;
            $articulo->precio_costo_paq = $request->precio_costo_paquete;
            $articulo->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Articulo actualizado correctamente',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar articulo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editarPrecioCompraVenta(Request $request)
    {
        $articulo = Articulo::findOrFail($request->id);
        $articulo->precio_costo_unid = $request->precio_costo_unidad;
        $articulo->precio_costo_paq = $request->precio_costo_paquete;
        $articulo->precio_uno = $request->precio_uno;
        $articulo->precio_dos = $request->precio_dos;
        $articulo->precio_tres = $request->precio_tres;
        $articulo->precio_cuatro = $request->precio_cuatro;
        $articulo->save();
    }

    public function indexAjusteInven(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = trim($request->buscar); 
        $idAlmacen = $request->idAlmacen;
        $idProveedor = $request->idProveedor;

        $query = Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->join('inventarios', function ($join) use ($idAlmacen) {
                $join->on('articulos.id', '=', 'inventarios.idarticulo')
                    ->where('inventarios.idalmacen', '=', $idAlmacen);
            })
            ->select(
                'articulos.id',
                'articulos.idcategoria',
                'articulos.idproveedor',
                'articulos.idmedida',
                'articulos.codigo',
                'articulos.nombre',
                'articulos.unidad_envase',
                'articulos.precio_list_unid',
                'articulos.precio_costo_unid',
                'articulos.precio_costo_paq',
                'categorias.nombre as nombre_categoria',
                'medidas.descripcion_medida',
                'articulos.precio_uno',
                'articulos.precio_dos',
                'articulos.precio_venta',
                'articulos.stock',
                'personas.nombre as nombre_proveedor',
                'articulos.condicion',
                'articulos.fotografia',
// Total unidades
DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) as stock_total_unidades'),

// Cajas completas
DB::raw('FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / NULLIF(articulos.unidad_envase, 0)) as stock_total_cajas'),

// Unidades sueltas
DB::raw('(IFNULL(SUM(inventarios.saldo_stock), 0) % articulos.unidad_envase) as stock_total_unidades_sueltas'),

// Texto formateado "X cajas y Y unidades"
DB::raw("
    CONCAT(
        FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / NULLIF(articulos.unidad_envase, 0)),
        ' cajas y ',
        (IFNULL(SUM(inventarios.saldo_stock), 0) % articulos.unidad_envase),
        ' unidades'
    ) as stock_formateado
"),            )
            ->where('articulos.condicion', '=', 1)
            ->where('inventarios.saldo_stock', '>', 0)
            ->groupBy(
                'articulos.id',
                'articulos.idcategoria',
                'articulos.idproveedor',
                'articulos.idmedida',
                'articulos.codigo',
                'articulos.nombre',
                'articulos.unidad_envase',
                'articulos.precio_list_unid',
                'articulos.precio_costo_unid',
                'articulos.precio_costo_paq',
                'categorias.nombre',
                'medidas.descripcion_medida',
                'articulos.precio_uno',
                'articulos.precio_dos',
                'articulos.precio_venta',
                'articulos.stock',
                'personas.nombre',
                'articulos.condicion',
                'articulos.fotografia'
            );

        if ($idProveedor) {
            $query->where('articulos.idproveedor', $idProveedor);
        }

        // Filtrar por texto de búsqueda solo si no está vacío
        if (!empty($buscar)) {
            $palabrasBuscar = array_filter(explode(" ", $buscar));
            $query->where(function ($query) use ($palabrasBuscar, $buscar) {
                foreach ($palabrasBuscar as $palabra) {
                    $query->where('articulos.nombre', 'like', '%' . $palabra . '%');
                }
                $query->orWhere('articulos.codigo', 'like', '%' . $buscar . '%')
                    ->orWhere('articulos.codigo_alfanumerico', 'like', '%' . $buscar . '%')
                    ->orWhere('categorias.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.nombre', 'like', '%' . $buscar . '%');
            });
        }

        $articulos = $query->orderBy('articulos.id', 'desc')->paginate(6);

        foreach ($articulos as $articulo) {
            $lotes = \DB::table('inventarios')
                ->select('fecha_vencimiento', 'saldo_stock')
                ->where('idarticulo', $articulo->id)
                ->where('idalmacen', $idAlmacen)
                ->where('saldo_stock', '>', 0)
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();
            $articulo->lotes = $lotes;
        }

        return [
            'pagination' => [
                'total' => $articulos->total(),
                'current_page' => $articulos->currentPage(),
                'per_page' => $articulos->perPage(),
                'last_page' => $articulos->lastPage(),
                'from' => $articulos->firstItem(),
                'to' => $articulos->lastItem(),
            ],
            'articulos' => $articulos
        ];
    }

}