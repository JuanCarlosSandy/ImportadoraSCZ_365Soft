<?php

namespace App\Http\Controllers;

use App\Articulo;
use App\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Exports\ProductosBajoStockExport;
use App\Exports\ProductosPorVencerseExport;
use App\Exports\ProductosVencidosExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InventarioImport;
use App\Exports\InventarioExport;
use Exception;
use FPDF;

class InventarioController extends Controller
{

    public function registrarInventario(Request $request)
    {
        if (!$request->has('inventarios')) {
            return response()->json(['error' => 'No se enviaron inventarios'], 400);
        }

        DB::beginTransaction();
        try {
            $inventarios = $request->input('inventarios');

            foreach ($inventarios as $inventario) {
                $articulo = Articulo::find($inventario['idarticulo']);

                if (!$articulo) {
                    Log::warning("Artículo no encontrado: " . $inventario['idarticulo']);
                    continue;
                }

                $fechaVencimiento = isset($inventario['fecha_vencimiento'])
                    ? date('Y-m-d', strtotime($inventario['fecha_vencimiento']))
                    : '2099-12-31';

                $cantidad = $inventario['cantidad'] ?? 0;

                $inventarioExistente = Inventario::where('idarticulo', $inventario['idarticulo'])
                    ->where('idalmacen', $inventario['idalmacen'])
                    ->whereDate('fecha_vencimiento', $fechaVencimiento)
                    ->first();

                if ($inventarioExistente) {
                    $inventarioExistente->saldo_stock += $cantidad;
                    $inventarioExistente->cantidad += $cantidad;
                    $inventarioExistente->save();
                } else {
                    Inventario::create([
                        'idalmacen' => $inventario['idalmacen'],
                        'idarticulo' => $inventario['idarticulo'],
                        'fecha_vencimiento' => $fechaVencimiento,
                        'saldo_stock' => $cantidad,
                        'cantidad' => $cantidad
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Inventarios guardados exitosamente'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al guardar inventarios'
            ], 500);
        }
    }


    public function store(Request $request)
    {
        if ($request->has('inventarios')) {
            $inventarios = $request->input('inventarios');
            Log::info("llego");

            foreach ($inventarios as $inventario) {

                $articulo = Articulo::find($inventario['idarticulo']);

                if ($articulo) {

                    $esPaquete = isset($inventario['es_paquete']) ? $inventario['es_paquete'] : false;
                    $cantidadIngresada = isset($inventario['cantidad']) ? $inventario['cantidad'] : 0;

                    if ($esPaquete) {
                        $cantidadReal = $cantidadIngresada * $articulo->unidad_envase;
                    } else {
                        $cantidadReal = $cantidadIngresada;
                    }

                    // 👉 Calcular cantidad multiplicada por unidad_envase
                    $cantidadReal = isset($inventario['cantidad'])
                        ? $inventario['cantidad'] * $articulo->unidad_envase
                        : 0;

                    Log::info("Cantidad real calculada: " . $cantidadReal);

                    // Buscar inventario existente
                    $inventariosExistente = Inventario::where('idarticulo', $inventario['idarticulo'])
                        ->whereDate('fecha_vencimiento', $inventario['fecha_vencimiento'])
                        ->get();

                    $foundInventory = false;

                    foreach ($inventariosExistente as $invExistente) {
                        $fechaVencimiento = new \DateTime($invExistente->fecha_vencimiento);

                        if (
                            $fechaVencimiento->format('Y-m-d') === date('Y-m-d', strtotime($inventario['fecha_vencimiento']))
                            && $invExistente->idalmacen == intval($inventario['idalmacen'])
                        ) {
                            Log::info("se comparo");

                            // Sumar usando cantidad multiplicada
                            $invExistente->saldo_stock += $cantidadReal;
                            $invExistente->cantidad += $cantidadReal;
                            $invExistente->save();

                            $foundInventory = true;
                            break;
                        }
                    }

                    if (!$foundInventory) {
                        // Crear nuevo registro
                        $newInventario = new Inventario();
                        $newInventario->idalmacen = $inventario['idalmacen'];
                        $newInventario->idarticulo = $inventario['idarticulo'];
                        $newInventario->fecha_vencimiento = $inventario['fecha_vencimiento'];

                        $newInventario->saldo_stock = $cantidadReal;
                        $newInventario->cantidad = $cantidadReal;

                        $newInventario->save();
                    }

                } else {
                    Log::info("No existe el articulo");
                }
            }

            return response()->json(['message' => 'Inventarios guardados exitosamente'], 200);
        }

        return response()->json(['error' => 'No se enviaron inventarios'], 400);
    }


    public function productosPorVencer(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        $usuario = \Auth::user(); // Usuario logueado
        $fechaActual = now()->toDateString();

        $inventarios = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'inventarios.id',
                'inventarios.fecha_vencimiento',
                'inventarios.saldo_stock',
                'almacens.nombre_almacen',
                'almacens.ubicacion',
                'articulos.codigo',
                'articulos.nombre as nombre_producto',
                'articulos.unidad_envase',
                'personas.nombre as nombre_proveedor',
                DB::raw('DATEDIFF(inventarios.fecha_vencimiento, "' . $fechaActual . '") AS dias_restantes'),
                DB::raw('IF(inventarios.fecha_vencimiento < "' . $fechaActual . '", 0, 1) AS vencido')
            )
            ->whereRaw('DATEDIFF(inventarios.fecha_vencimiento, "' . $fechaActual . '") <= 30')
            ->orderBy('inventarios.id', 'desc');

        // ✅ Filtrar por sucursal si el usuario no es rol 4
        if ($usuario->idrol != 4) {
            $inventarios->where('almacens.sucursal', $usuario->idsucursal);
        }

        // ✅ Aplicar búsqueda si corresponde
        if (!empty($buscar)) {
            $inventarios->where('inventarios.' . $criterio, 'like', '%' . $buscar . '%');
        }

        $inventarios = $inventarios->paginate(6);

        return [
            'pagination' => [
                'total' => $inventarios->total(),
                'current_page' => $inventarios->currentPage(),
                'per_page' => $inventarios->perPage(),
                'last_page' => $inventarios->lastPage(),
                'from' => $inventarios->firstItem(),
                'to' => $inventarios->lastItem(),
            ],
            'inventarios' => $inventarios
        ];
    }


    public function listarReportePorVencerExcel()
    {
        return Excel::download(new ProductosPorVencerseExport, 'articulosPorVencer.xlsx');
    }

    public function productosVencidos(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        if ($buscar == '') {
            $inventarios = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('personas', 'proveedores.id', '=', 'personas.id')
                ->select(
                    'inventarios.id',
                    'inventarios.fecha_vencimiento',
                    'inventarios.saldo_stock',

                    'almacens.nombre_almacen',
                    'almacens.ubicacion',

                    'articulos.codigo',
                    'articulos.nombre as nombre_producto',
                    'articulos.unidad_envase',

                    'personas.nombre as nombre_proveedor',

                )
                ->whereDate('inventarios.fecha_vencimiento', '<=', DB::raw('CURDATE()'))
                ->orderBy('inventarios.id', 'desc')->paginate(6);
        } else {
            $inventarios = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('personas', 'proveedores.id', '=', 'personas.id')
                ->select(

                    'inventarios.id',
                    'inventarios.fecha_vencimiento',
                    'inventarios.saldo_stock',

                    'almacens.nombre_almacen',
                    'almacens.ubicacion',

                    'articulos.codigo',
                    'articulos.nombre as nombre_producto',
                    'articulos.precio_costo_unid',

                    'personas.nombre as nombre_proveedor',
                )
                ->whereDate('inventarios.fecha_vencimiento', '<=', DB::raw('CURDATE()'))
                ->where('inventarios.' . $criterio, 'like', '%' . $buscar . '%')
                ->orderBy('inventarios.id', 'desc')->paginate(6);
        }


        return [
            'pagination' => [
                'total' => $inventarios->total(),
                'current_page' => $inventarios->currentPage(),
                'per_page' => $inventarios->perPage(),
                'last_page' => $inventarios->lastPage(),
                'from' => $inventarios->firstItem(),
                'to' => $inventarios->lastItem(),
            ],
            'inventarios' => $inventarios
        ];
    }

    public function listarReporteVencidosExcel()
    {
        return Excel::download(new ProductosVencidosExport, 'articulosVencidos.xlsx');
    }
    public function productosBajoStock(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $usuario = \Auth::user();
        $buscar = $request->buscar;
        $criterio = $request->criterio;

        $query = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'inventarios.idarticulo',
                'almacens.nombre_almacen',
                'almacens.ubicacion',
                'articulos.codigo',
                'articulos.nombre as nombre_producto',
                'articulos.unidad_envase',
                'articulos.stock',
                'articulos.precio_costo_unid',
                'personas.nombre as nombre_proveedor',

                // SUMA del stock total real del artículo (en unidades)
                DB::raw('SUM(inventarios.saldo_stock) as saldo_total_unidades'),

                // STOCK TOTAL EN CAJAS
                DB::raw('ROUND(SUM(inventarios.saldo_stock) / NULLIF(articulos.unidad_envase, 0), 2) as saldo_total_cajas')
            )

            // ✅ Solo artículos activos
            ->where('articulos.condicion', '=', 1)

            ->groupBy(
                'inventarios.idarticulo',
                'almacens.nombre_almacen',
                'almacens.ubicacion',
                'articulos.codigo',
                'articulos.nombre',
                'articulos.unidad_envase',
                'articulos.stock',
                'articulos.precio_costo_unid',
                'personas.nombre'
            )

            // 🔥 Bajo stock comparando CAJAS TOTALES de inventario vs stock mínimo configurado
            ->havingRaw('ROUND(SUM(inventarios.saldo_stock) / NULLIF(articulos.unidad_envase, 0), 2) <= articulos.stock');

        // Filtrar por sucursal (si no es rol 4)
        if ($usuario->idrol != 4) {
            $query->where('almacens.sucursal', $usuario->idsucursal);
        }

        // Filtro de búsqueda
        if ($buscar != '') {
            $query->where('inventarios.' . $criterio, 'like', '%' . $buscar . '%');
        }

        $inventarios = $query
            ->orderBy('almacens.nombre_almacen', 'asc')
            ->paginate(6);

        return [
            'pagination' => [
                'total' => $inventarios->total(),
                'current_page' => $inventarios->currentPage(),
                'per_page' => $inventarios->perPage(),
                'last_page' => $inventarios->lastPage(),
                'from' => $inventarios->firstItem(),
                'to' => $inventarios->lastItem(),
            ],
            'inventarios' => $inventarios
        ];
    }



    public function listarReporteBajoStockExcel()
    {
        return Excel::download(new ProductosBajoStockExport, 'articulosBajoStock.xlsx');
    }
    //-------------------aumente el listado mejorado------
    public function indextraspaso(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        Log::info('Data', [
            'idAlmacen' => $request->idAlmacen,
            'buscar' => $request->buscar,
            'criterio' => $request->criterio,
        ]);

        $buscar = $request->buscar;
        $idAlmacen = $request->idAlmacen;

        // Construcción de la consulta base con joins y groupBy
        $inventarios = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'inventarios.idarticulo',
                'articulos.nombre as nombre_producto',
                'articulos.codigo',
                'articulos.precio_costo_unid',
                'articulos.precio_costo_paq',
                DB::raw('SUM(inventarios.saldo_stock) as saldo_stock'), // Sumar saldo_stock
                'articulos.precio_venta',
                'almacens.ubicacion',
                'personas.nombre as nombre_proveedor',
                'articulos.fotografia'
            )
            ->where('inventarios.idalmacen', '=', $idAlmacen)
            ->where('articulos.condicion', '=', 1)
            ->groupBy(
                'inventarios.idarticulo',
                'articulos.nombre',
                'articulos.codigo',
                'articulos.precio_costo_unid',
                'articulos.precio_costo_paq',
                'articulos.precio_venta',
                'almacens.ubicacion',
                'personas.nombre',
                'articulos.fotografia'
            );

        if (!empty($buscar)) {
            $palabras = explode(' ', $buscar); // Dividir la búsqueda en palabras
            $inventarios = $inventarios->where(function ($q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $q->where(function ($sub) use ($palabra) {
                        $sub->where('articulos.nombre', 'like', '%' . $palabra . '%')
                            ->orWhere('articulos.codigo', 'like', '%' . $palabra . '%')
                            ->orWhere('personas.nombre', 'like', '%' . $palabra . '%')
                            ->orWhere('almacens.ubicacion', 'like', '%' . $palabra . '%');
                    });
                }
            });
        }

        // Filtrar por saldo_stock > 0
        $inventarios = $inventarios->having(DB::raw('SUM(inventarios.saldo_stock)'), '>', 0);

        // Ordenar y paginar resultados
        $inventarios = $inventarios->orderBy('inventarios.idarticulo', 'desc')->paginate(6);

        // Respuesta estructurada
        return [
            'pagination' => [
                'total' => $inventarios->total(),
                'current_page' => $inventarios->currentPage(),
                'per_page' => $inventarios->perPage(),
                'last_page' => $inventarios->lastPage(),
                'from' => $inventarios->firstItem(),
                'to' => $inventarios->lastItem(),
            ],
            'inventarios' => $inventarios
        ];
    }
    public function indexItemLote(Request $request, $tipo)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $idAlmacen = $request->idAlmacen;
        $buscar = $request->buscar;

        if ($tipo === 'item') {
            $inventarios = Articulo::leftJoin('inventarios', function ($join) use ($idAlmacen) {
                $join->on('articulos.id', '=', 'inventarios.idarticulo')
                    ->where('inventarios.idalmacen', '=', $idAlmacen);
            })
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->leftJoin('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->select(
                    'articulos.codigo',
                    'articulos.nombre as nombre_producto',
                    'articulos.unidad_envase',
                    'almacens.nombre_almacen',
                    'proveedores.contacto as nombre_proveedor',
                    'articulos.descripcion_fabrica',
                    // Total unidades
                    DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) as saldo_stock_total'),

                    // Cajas completas (sin decimales)
                    DB::raw('FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / NULLIF(articulos.unidad_envase, 0)) as cajas'),

                    // Unidades sueltas
                    DB::raw('(IFNULL(SUM(inventarios.saldo_stock), 0) % articulos.unidad_envase) as unidades'),

                    // Formato final listo para el frontend
                    DB::raw("
                        CONCAT(
                            FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / NULLIF(articulos.unidad_envase, 0)),
                            ' cajas y ',
                            (IFNULL(SUM(inventarios.saldo_stock), 0) % articulos.unidad_envase),
                            ' unidades'
                        ) as stock_formateado
                    ")

                )
                ->where('articulos.condicion', '=', 1)
                ->groupBy('articulos.codigo', 'articulos.nombre', 'almacens.nombre_almacen', 'articulos.unidad_envase', 'proveedores.contacto', 'articulos.descripcion_fabrica')
                ->orderBy('articulos.nombre')
                ->orderBy('almacens.nombre_almacen');
        } else if ($tipo === 'lote') {
            $inventarios = Articulo::leftJoin('inventarios', function ($join) use ($idAlmacen) {
                $join->on('articulos.id', '=', 'inventarios.idarticulo')
                    ->where('inventarios.idalmacen', '=', $idAlmacen);
            })
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->leftJoin('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->select(
                    'articulos.nombre as nombre_producto',
                    'articulos.unidad_envase',
                    'articulos.precio_costo_unid',
                    DB::raw('IFNULL(inventarios.saldo_stock, 0) as saldo_stock'),
                    DB::raw('IFNULL(inventarios.cantidad, 0) as cantidad'),
                    'proveedores.contacto as nombre_proveedor',
                    DB::raw('DATE_FORMAT(inventarios.created_at, "%Y-%m-%d") as fecha_ingreso'),
                    'inventarios.fecha_vencimiento',
                    'almacens.nombre_almacen',
                    DB::raw('FLOOR(IFNULL(inventarios.saldo_stock, 0) / articulos.unidad_envase) as stock_en_paquetes'),
                    DB::raw('IFNULL(inventarios.saldo_stock, 0) % articulos.unidad_envase as unidades_restantes')
                )
                ->where('articulos.condicion', '=', 1)
                ->orderBy('articulos.nombre');
        }

        if (!empty($buscar)) {
            $palabras = explode(' ', $buscar); // Dividir el texto en palabras
            $inventarios = $inventarios->where(function ($query) use ($palabras, $tipo) {
                foreach ($palabras as $palabra) {
                    $query->where(function ($sub) use ($palabra, $tipo) {
                        if ($tipo === 'item') {
                            $sub->where('articulos.nombre', 'like', '%' . $palabra . '%')
                                ->orWhere('proveedores.contacto', 'like', '%' . $palabra . '%')
                                ->orWhere('almacens.nombre_almacen', 'like', '%' . $palabra . '%');
                        } elseif ($tipo === 'lote') {
                            $sub->where('articulos.nombre', 'like', '%' . $palabra . '%')
                                ->orWhere('proveedores.contacto', 'like', '%' . $palabra . '%')
                                ->orWhere('almacens.nombre_almacen', 'like', '%' . $palabra . '%')
                                ->orWhere(DB::raw('DATE_FORMAT(inventarios.created_at, "%Y-%m-%d")'), 'like', '%' . $palabra . '%')
                                ->orWhere('inventarios.fecha_vencimiento', 'like', '%' . $palabra . '%');
                        }
                    });
                }
            });
        }

        $inventarios = $inventarios->paginate(20);

        return [
            'pagination' => [
                'total' => $inventarios->total(),
                'current_page' => $inventarios->currentPage(),
                'per_page' => $inventarios->perPage(),
                'last_page' => $inventarios->lastPage(),
                'from' => $inventarios->firstItem(),
                'to' => $inventarios->lastItem(),
            ],
            'inventarios' => $inventarios
        ];
    }

    //LISTA PARA OBTENER EL SALDO_STOCK POR ITEM POR EL ALMACEN,NOMBRE Y ID DE ARTICULO
    public function indexsaldostock(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }
        $idAlmacen = $request->idAlmacen;
        $idArticulo = $request->idArticulo;
        if ($idAlmacen !== null && $idArticulo !== null) {
            $invenstock = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('personas', 'proveedores.id', '=', 'personas.id')
                ->select(
                    'articulos.nombre as nombre_producto',
                    //'articulos.unidad_envase',
                    'almacens.nombre_almacen',
                    DB::raw('SUM(inventarios.saldo_stock) as saldo_stock_totaldos')
                )
                ->where('inventarios.idalmacen', '=', $idAlmacen)
                ->where('inventarios.idarticulo', '=', $idArticulo)
                ->groupBy('articulos.nombre', 'almacens.nombre_almacen')
                ->orderBy('articulos.nombre')
                ->orderBy('almacens.nombre_almacen')->get();

            return ['invenstock' => $invenstock];
        } else {
            // Si falta alguno de los valores, regresar respuesta vacía
            return ['invenstock' => []];
        }
    }
    public function reporteAlmacenes(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $idAlmacen = $request->idAlmacen;

        $inventarios = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'articulos.nombre as nombre_producto',
                'articulos.unidad_envase',
                'almacens.nombre_almacen',
                DB::raw('SUM(inventarios.saldo_stock) as saldo_stock_total')
            )
            ->where('inventarios.idalmacen', '=', $idAlmacen)
            ->groupBy('articulos.nombre', 'almacens.nombre_almacen', 'articulos.unidad_envase')
            ->orderBy('articulos.nombre')
            ->orderBy('almacens.nombre_almacen');
        //->get();
        //---------------------------------------

        $inventarios = $inventarios->get();

        if ($inventarios->isEmpty()) {
            return response()->json(['mensaje' => 'No existe articulos en el almacen seleccionado']);
        }
        //---------------------------------
        return response()->json(['inventarios' => $inventarios]);
    }

    public function importar(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|mimes:csv,txt',
            ]);

            $archivo = $request->file('archivo');

            $import = new InventarioImport();
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

    public function productosActualizadosRecientemente(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        // Obtener la fecha de hace 7 días
        $fechaInicio = now()->subDays(7)->toDateTimeString();
        $fechaActual = now()->toDateTimeString();

        $articulos = Articulo::join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'articulos.id',
                'articulos.codigo',
                'articulos.nombre as nombre_producto',
                'articulos.unidad_envase',
                'articulos.updated_at',
                DB::raw('ROUND(articulos.precio_uno, 2) as precio_venta'),
                'personas.nombre as nombre_proveedor'
            )
            ->whereBetween('articulos.precio_actualizado_en', [$fechaInicio, $fechaActual])
            ->orderBy('articulos.precio_actualizado_en', 'desc');

        if (!empty($buscar)) {
            $articulos->where('articulos.' . $criterio, 'like', '%' . $buscar . '%');
        }

        $articulos = $articulos->paginate(6);

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

    public function obtenerStockPorSucursal(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $idArticulo = $request->idarticulo;

        $stocks = DB::table('inventarios as i')
            ->join('almacens as a', 'i.idalmacen', '=', 'a.id')
            ->join('sucursales as s', 'a.sucursal', '=', 's.id')
            ->select(
                's.nombre as sucursal',
                DB::raw('SUM(i.saldo_stock) as total_stock')
            )
            ->where('i.idarticulo', $idArticulo)
            ->groupBy('s.id', 's.nombre')
            ->get();

        return response()->json(['stocks' => $stocks]);
    }

    public function exportarPdf(Request $request)
    {
        $modo = $request->input('modo', 'item');
        $idAlmacen = $request->input('idAlmacen');

        // Obtener nombre de almacén
        $almacen = \DB::table('almacens')->where('id', $idAlmacen)->first();
        $nombreAlmacen = $almacen ? $almacen->nombre_almacen : 'Desconocido';

        // Obtener inventario según modo
        if ($modo === 'item') {
            $inventarios = \DB::table('articulos')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->select(
                    'articulos.nombre as item',
                    'proveedores.contacto as laboratorio',
                    'articulos.unidad_envase',
                    \DB::raw('SUM(inventarios.saldo_stock) as saldo_stock_total')
                )
                ->where('inventarios.idalmacen', $idAlmacen)
                ->where('articulos.condicion', 1)
                ->groupBy('articulos.id', 'articulos.nombre', 'proveedores.contacto', 'articulos.unidad_envase')
                ->orderBy('proveedores.contacto')
                ->get();
        } else {
            // Modo lote
            $inventarios = \DB::table('articulos')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->select(
                    'articulos.nombre as item',
                    'proveedores.contacto as laboratorio',
                    'articulos.unidad_envase',
                    'inventarios.saldo_stock',
                    'inventarios.created_at',
                    'inventarios.fecha_vencimiento'
                )
                ->where('inventarios.idalmacen', $idAlmacen)
                ->where('articulos.condicion', 1)
                ->orderBy('proveedores.contacto')
                ->get();
        }

        $pdf = new PDFInventario();
        $pdf->AliasNbPages();
        $pdf->AddPage();

        // Logo
        if (file_exists(public_path('logo.png'))) {
            $pdf->Image(public_path('logo.png'), 10, 8, 30);
        }

        // Encabezado
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, utf8_decode('REPORTE DE INVENTARIO - ' . strtoupper($modo)), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Almacen: ' . utf8_decode($nombreAlmacen), 0, 1, 'C');
        $pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(5);

        if ($modo === 'item') {
            // Agrupar por laboratorio
            $agrupado = [];
            foreach ($inventarios as $inv) {
                $agrupado[$inv->laboratorio][] = $inv;
            }

            foreach ($agrupado as $lab => $items) {
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(0, 7, utf8_decode("LABORATORIO: $lab"), 1, 1, 'L', true);

                // Cabecera
                $pdf->SetFillColor(0, 102, 204);
                $pdf->SetTextColor(255);
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(140, 7, 'ITEM', 1, 0, 'C', true);
                $pdf->Cell(20, 7, 'UNID.', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'STOCK', 1, 1, 'C', true);

                $pdf->SetFont('Arial', '', 9);
                $pdf->SetTextColor(0);

                foreach ($items as $inv) {
                    if ($inv->saldo_stock_total == 0) {
                        $pdf->SetFillColor(255, 220, 220); // Rojo claro
                    } else {
                        $pdf->SetFillColor(255);
                    }

                    $pdf->Cell(140, 7, utf8_decode(substr($inv->item, 0, 60)), 1, 0, 'L', true);
                    $pdf->Cell(20, 7, utf8_decode($inv->unidad_envase), 1, 0, 'C', true);
                    $pdf->Cell(30, 7, $inv->saldo_stock_total, 1, 1, 'C', true);
                }

                $pdf->Ln(2);
            }
        } else {
            // Modo lote
            $pdf->SetFillColor(0, 102, 204);
            $pdf->SetTextColor(255);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(85, 8, 'ITEM', 1, 0, 'C', true);
            $pdf->Cell(35, 8, 'LABORATORIO', 1, 0, 'C', true);
            $pdf->Cell(15, 8, 'STOCK', 1, 0, 'C', true);
            $pdf->Cell(25, 8, 'F. INGRESO', 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'F. VENCIM.', 1, 1, 'C', true);

            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0);

            foreach ($inventarios as $inv) {
                if ($inv->saldo_stock == 0) {
                    $pdf->SetFillColor(255, 220, 220); // Rojo claro
                } else {
                    $pdf->SetFillColor(255);
                }

                $pdf->Cell(85, 7, utf8_decode(substr($inv->item, 0, 35)), 1, 0, 'L', true);
                $pdf->Cell(35, 7, utf8_decode(substr($inv->laboratorio, 0, 20)), 1, 0, 'L', true);
                $pdf->Cell(15, 7, $inv->saldo_stock, 1, 0, 'C', true);
                $pdf->Cell(25, 7, date('d/m/Y', strtotime($inv->created_at)), 1, 0, 'C', true);
                $pdf->Cell(30, 7, date('d/m/Y', strtotime($inv->fecha_vencimiento)), 1, 1, 'C', true);
            }
        }

        // Output
        $filename = 'inventario_' . $modo . '_' . date('Ymd_His') . '.pdf';
        return response($pdf->Output('S', $filename))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportarExcel(Request $request)
    {
        $modo = $request->input('modo', 'item');
        $idAlmacen = $request->input('idAlmacen');

        $filename = 'inventario_' . $modo . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new InventarioExport($modo, $idAlmacen), $filename);
    }
}

class PDFInventario extends FPDF
{
    public function Footer()
    {
        // Posiciona el pie de página a 1.5 cm del final
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}