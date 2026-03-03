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
                    Log::warning("ArtÃƒÆ’Ã‚Â­culo no encontrado: " . $inventario['idarticulo']);
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

        // ÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ Filtrar por sucursal si el usuario no es rol 4
        if ($usuario->idrol != 4) {
            $inventarios->where('almacens.sucursal', $usuario->idsucursal);
        }

        // ÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ Aplicar bÃƒÆ’Ã‚Âºsqueda si corresponde
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

        $usuario = \Auth::user(); // Usuario logueado
        $buscar = $request->buscar;
        $criterio = $request->criterio;

        $almacen_id = $request->almacen_id;
        $medicamento = $request->medicamento;
        $laboratorio = $request->laboratorio;
        $codigo = trim((string) $request->codigo);
        $codigo = trim((string) $request->codigo);

        $query = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
        ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
        ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
        ->join('personas', 'proveedores.id', '=', 'personas.id')
        ->select(
            'inventarios.idarticulo',
            'inventarios.idalmacen',
            'almacens.nombre_almacen',
            'almacens.ubicacion',
            'articulos.codigo',
            'articulos.nombre as nombre_producto',
            'articulos.unidad_envase',
            'articulos.stock',
            'articulos.precio_costo_unid',
            'personas.nombre as nombre_proveedor',
            \DB::raw('SUM(inventarios.saldo_stock) as saldo_stock')
        )
        ->groupBy(
            'inventarios.idarticulo',
            'inventarios.idalmacen',
            'almacens.nombre_almacen',
            'almacens.ubicacion',
            'articulos.codigo',
            'articulos.nombre',
            'articulos.unidad_envase',
            'articulos.stock',
            'articulos.precio_costo_unid',
            'personas.nombre'
        )
        ->havingRaw('articulos.stock > SUM(inventarios.saldo_stock)');


        // ✅ Filtrar por sucursal del usuario (solo si no es rol 4)
        if ($usuario->idrol != 4) {
            $query->where('almacens.sucursal', $usuario->idsucursal);
        }

        
        if ($buscar != '') {
            $query->where('inventarios.' . $criterio, 'like', '%' . $buscar . '%');
        }

        if (!empty($almacen_id)) {
            $query->where('inventarios.idalmacen', $almacen_id);
        }

        
        if (!empty($medicamento)) {
            $query->where('articulos.nombre', 'like', '%' . $medicamento . '%');
        }

        
        if (!empty($laboratorio)) {
            $query->where('personas.nombre', 'like', '%' . $laboratorio . '%');
        }

        if ($codigo !== '') {
            $query->where('articulos.codigo', 'like', '%' . $codigo . '%');
        }

        $inventarios = $query
            ->orderBy('almacens.nombre_almacen', 'asc')
            ->orderBy('personas.nombre', 'asc')
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


   public function listarReporteBajoStockExcel(Request $request)
    {
        $fechaGeneracion = date('Y-m-d');
        $nombreArchivo = "Productos_bajo_stock_{$fechaGeneracion}.xlsx";

        return Excel::download(
            new ProductosBajoStockExport(
                $request->almacen_id, 
                $request->medicamento, 
                $request->laboratorio,
                $request->codigo
            ),
            $nombreArchivo
        );
    }

    public function exportarProductosBajoStockPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->idrol, [1, 4])) {
            return response()->json([
                'error' => true,
                'message' => 'Acceso denegado. Esta accion solo esta permitida para Administrador y SuperAdministrador.'
            ], 403);
        }

        [$query, $filtros] = $this->construirQueryBajoStock($request);
        $datos = $query->get();

        $pdf = new PDFInventario('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $toAscii = function ($text) {
            $text = (string) $text;
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            return $converted !== false ? $converted : $text;
        };

        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, 'REPORTE DE PRODUCTOS CON BAJO STOCK', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, 'Fecha de generacion: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(3);

        $txtFiltros = [
            'Almacen: ' . $toAscii($filtros['nombre_almacen'] ?? 'Todos'),
            'Proveedor: ' . $toAscii($filtros['proveedor'] ?: 'Todos'),
            'Productos: ' . $toAscii($filtros['productos'] ?: 'Todos'),
            'Codigo: ' . $toAscii($filtros['codigo'] ?: 'Todos'),
            'Categoria: ' . $toAscii($filtros['nombre_categoria'] ?? 'Todas'),
        ];
        if ($filtros['stock_desde'] !== null || $filtros['stock_hasta'] !== null) {
            $txtFiltros[] = 'Rango stock: ' .
                ($filtros['stock_desde'] !== null ? $filtros['stock_desde'] : '-') .
                ' a ' .
                ($filtros['stock_hasta'] !== null ? $filtros['stock_hasta'] : '-');
        }

        $pdf->SetFont('Arial', '', 8);
        $pdf->MultiCell(0, 5, implode(' | ', $txtFiltros));
        $pdf->Ln(2);

        $w = [36, 76, 46, 58, 28, 28];
        $headers = ['Almacen', 'Producto', 'Categoria', 'Proveedor', 'Stock Actual', 'Stock Minimo'];

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(220, 220, 220);
        foreach ($headers as $i => $header) {
            $pdf->Cell($w[$i], 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);
        $fill = false;
        foreach ($datos as $row) {
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            $pdf->Cell($w[0], 6, substr($toAscii($row->nombre_almacen), 0, 22), 1, 0, 'L', true);
            $pdf->Cell($w[1], 6, substr($toAscii($row->nombre_producto), 0, 42), 1, 0, 'L', true);
            $pdf->Cell($w[2], 6, substr($toAscii($row->nombre_categoria), 0, 26), 1, 0, 'L', true);
            $pdf->Cell($w[3], 6, substr($toAscii($row->nombre_proveedor), 0, 30), 1, 0, 'L', true);
            $pdf->Cell($w[4], 6, number_format($row->stock_actual, 0), 1, 0, 'R', true);
            $pdf->Cell($w[5], 6, number_format($row->stock_minimo, 0), 1, 1, 'R', true);
            $fill = !$fill;
        }

        if ($datos->isEmpty()) {
            $pdf->Cell(array_sum($w), 8, 'No hay registros para los filtros seleccionados.', 1, 1, 'C');
        }

        $nombreArchivo = 'ReporteProductosBajoStock_' . date('Ymd_His') . '.pdf';
        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');
    }

    private function construirQueryBajoStock(Request $request)
    {
        $usuario = \Auth::user();
        $almacenId = $request->input('idAlmacen', $request->input('almacen_id'));
        $proveedor = trim($request->input('proveedor', ''));
        $productos = trim($request->input('productos', $request->input('producto', '')));
        $codigo = trim((string) $request->input('codigo', $request->input('codigo_producto', '')));
        $idCategoria = $request->input('idCategoria', $request->input('categoria_id'));
        $stockExpr = 'SUM(inventarios.saldo_stock)';

        $query = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
            ->leftJoin('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->leftJoin('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->leftJoin('personas', 'proveedores.id', '=', 'personas.id')
            ->select(
                'almacens.id as id_almacen',
                'almacens.nombre_almacen',
                'articulos.id as id_producto',
                'articulos.nombre as nombre_producto',
                DB::raw('COALESCE(categorias.nombre, "SIN CATEGORIA") as nombre_categoria'),
                DB::raw('COALESCE(personas.nombre, "SIN PROVEEDOR") as nombre_proveedor'),
                DB::raw($stockExpr . ' as stock_actual'),
                DB::raw($stockExpr . ' as saldo_stock'),
                'articulos.stock as stock_minimo'
            )
            ->where('articulos.condicion', '=', 1)
            ->groupBy(
                'almacens.id',
                'almacens.nombre_almacen',
                'articulos.id',
                'articulos.nombre',
                'categorias.nombre',
                'personas.nombre',
                'articulos.stock'
            )
            ->havingRaw('(' . $stockExpr . ') <= articulos.stock');

        if ($usuario && $usuario->idrol != 4 && !empty($usuario->idsucursal)) {
            $query->where('almacens.sucursal', $usuario->idsucursal);
        }
        if (!empty($almacenId)) {
            $query->where('inventarios.idalmacen', $almacenId);
        }
        if ($proveedor !== '') {
            $query->whereRaw('COALESCE(personas.nombre, "") like ?', ['%' . $proveedor . '%']);
        }
        if ($productos !== '') {
            $query->where('articulos.nombre', 'like', '%' . $productos . '%');
        }
        if ($codigo !== '') {
            $query->where('articulos.codigo', 'like', '%' . $codigo . '%');
        }
        if (!empty($idCategoria)) {
            $query->where('articulos.idcategoria', $idCategoria);
        }

        $query->orderBy('almacens.nombre_almacen')->orderBy('articulos.nombre');

        $filtros = [
            'id_almacen' => $almacenId,
            'nombre_almacen' => !empty($almacenId) ? DB::table('almacens')->where('id', $almacenId)->value('nombre_almacen') : null,
            'proveedor' => $proveedor,
            'productos' => $productos,
            'codigo' => $codigo,
            'id_categoria' => $idCategoria,
            'nombre_categoria' => !empty($idCategoria) ? DB::table('categorias')->where('id', $idCategoria)->value('nombre') : null,
            'stock_desde' => null,
            'stock_hasta' => null,
        ];

        return [$query, $filtros];
    }

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

        // ConstrucciÃƒÆ’Ã‚Â³n de la consulta base con joins y groupBy
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
            $palabras = explode(' ', $buscar); // Dividir la bÃƒÆ’Ã‚Âºsqueda en palabras
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
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->leftJoin('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->select(
                    'articulos.codigo',
                    'articulos.nombre as nombre_producto',
                    'articulos.unidad_envase',
                    'almacens.nombre_almacen',
                    'proveedores.contacto as nombre_proveedor',
                    'articulos.descripcion_fabrica',
                    'categorias.nombre as nombre_categoria',

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
                ->groupBy('articulos.codigo', 'articulos.nombre', 'almacens.nombre_almacen', 'articulos.unidad_envase', 'proveedores.contacto', 'articulos.descripcion_fabrica', 'categorias.nombre')
                ->orderBy('categorias.nombre')
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
                                ->orWhere('articulos.codigo', 'like', '%' . $palabra . '%')
                                ->orWhere('categorias.nombre', 'like', '%' . $palabra . '%')
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
            // Si falta alguno de los valores, regresar respuesta vacÃƒÆ’Ã‚Â­a
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
                return response()->json(['mensaje' => 'ImportaciÃƒÆ’Ã‚Â³n exitosa'], 200);
            }
        } catch (Exception $e) {
            Log::error('Error en la importaciÃƒÆ’Ã‚Â³n: ' . $e->getMessage());

            return response()->json(['error' => 'Error en la importaciÃƒÆ’Ã‚Â³n', 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function productosActualizadosRecientemente(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        // Obtener la fecha de hace 7 dÃƒÆ’Ã‚Â­as
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

        // Obtener nombre de almacÃƒÆ’Ã‚Â©n
        $almacen = \DB::table('almacens')->where('id', $idAlmacen)->first();
        $nombreAlmacen = $almacen ? $almacen->nombre_almacen : 'Desconocido';

        // Obtener inventario segÃƒÆ’Ã‚Âºn modo - MEJORADO CON CATEGORÃƒÆ’Ã‚ÂA
        if ($modo === 'item') {
            $inventarios = \DB::table('articulos')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->select(
                    'articulos.nombre as item',
                    'categorias.nombre as categoria',
                    'proveedores.contacto as proveedor',
                    'articulos.unidad_envase',
                    \DB::raw('CAST(SUM(inventarios.saldo_stock) as UNSIGNED) as stock_unidades'),
                    \DB::raw('FLOOR(SUM(inventarios.saldo_stock) / NULLIF(articulos.unidad_envase, 0)) as stock_cajas')
                )
                ->where('inventarios.idalmacen', $idAlmacen)
                ->where('articulos.condicion', 1)
                ->groupBy('articulos.id', 'articulos.nombre', 'categorias.nombre', 'proveedores.contacto', 'articulos.unidad_envase')
                ->orderBy('categorias.nombre')
                ->orderBy('proveedores.contacto')
                ->get();
        } else {
            // Modo lote
            $inventarios = \DB::table('articulos')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->select(
                    'articulos.nombre as item',
                    'categorias.nombre as categoria',
                    'proveedores.contacto as proveedor',
                    'articulos.unidad_envase',
                    'inventarios.saldo_stock as stock_unidades',
                    \DB::raw('FLOOR(inventarios.saldo_stock / NULLIF(articulos.unidad_envase, 0)) as stock_cajas'),
                    'inventarios.created_at',
                    'inventarios.fecha_vencimiento'
                )
                ->where('inventarios.idalmacen', $idAlmacen)
                ->where('articulos.condicion', 1)
                ->orderBy('categorias.nombre')
                ->orderBy('proveedores.contacto')
                ->get();
        }

        $pdf = new PDFInventario();
        $pdf->AliasNbPages();
        $pdf->AddPage();

        // Logo - Izquierda - Buscar en diferentes ubicaciones
        $logoPath = null;
        if (file_exists(public_path('img/logoPrincipal.png'))) {
            $logoPath = public_path('img/logoPrincipal.png');
        } elseif (file_exists(public_path('logo.png'))) {
            $logoPath = public_path('logo.png');
        } elseif (file_exists(public_path('img/logo.png'))) {
            $logoPath = public_path('img/logo.png');
        } elseif (file_exists(public_path('images/logo.png'))) {
            $logoPath = public_path('images/logo.png');
        }
        
        if ($logoPath) {
            $pdf->Image($logoPath, 10, 8, 30);
        }

        // Encabezado con colores corporativos
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(47, 50, 107); // Color #21234a
        $pdf->SetXY(50, 15);
        $pdf->Cell(140, 10, utf8_decode('REPORTE DE INVENTARIO'), 0, 1, 'L');
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(50, 26);
        
        $pdf->Cell(140, 6, utf8_decode('AlmacÃƒÆ’Ã‚Â©n: ' . $nombreAlmacen), 0, 1, 'L');
        
        $pdf->SetXY(50, 33);
        $pdf->Cell(140, 6, 'Fecha: ' . date('d/m/Y H:i'), 0, 1, 'L');

        $pdf->Ln(8);

        if ($modo === 'item') {
            // Agrupar por categorÃƒÆ’Ã‚Â­a
            $agrupado = [];
            foreach ($inventarios as $inv) {
                if (!isset($agrupado[$inv->categoria])) {
                    $agrupado[$inv->categoria] = [];
                }
                $agrupado[$inv->categoria][] = $inv;
            }

            foreach ($agrupado as $categoria => $items) {
                // Encabezado de categorÃƒÆ’Ã‚Â­a con color corporativo
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(47, 50, 107); // Color #21234a
                $pdf->SetTextColor(255, 255, 255); // Blanco
                $pdf->Cell(188, 7, utf8_decode("CATEGORÃƒÆ’Ã‚ÂA: " . strtoupper($categoria)), 0, 1, 'L', true);

                // Cabecera de tabla con colores corporativos
                $pdf->SetFillColor(72, 75, 138); // Color #21234a
                $pdf->SetTextColor(255, 255, 255); // Blanco
                $pdf->SetFont('Arial', 'B', 8);
                
                $pdf->Cell(95, 7, utf8_decode('Producto'), 1, 0, 'C', true);
                $pdf->Cell(55, 7, utf8_decode('Proveedr'), 1, 0, 'C', true);
                $pdf->Cell(18, 7, utf8_decode('Unid/Paq'), 1, 0, 'C', true);
                $pdf->Cell(20, 7, utf8_decode('Stock Actual'), 1, 1, 'C', true);

                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(0, 0, 0);

                $contador = 0;
                foreach ($items as $inv) {
                    // Color alternado para mejor legibilidad
                    if ($inv->stock_unidades == 0) {
                        $pdf->SetFillColor(255, 200, 200); // Rojo claro para sin stock
                    } else {
                        // Alternar entre blanco y gris muy claro
                        if ($contador % 2 == 0) {
                            $pdf->SetFillColor(255, 255, 255);
                        } else {
                            $pdf->SetFillColor(245, 245, 245);
                        }
                    }
                    $contador++;

                    $pdf->Cell(95, 6, utf8_decode(substr($inv->item, 0, 40)), 1, 0, 'L', true);
                    $pdf->Cell(55, 6, utf8_decode(substr($inv->proveedor, 0, 25)), 1, 0, 'L', true);
                    $pdf->Cell(18, 6, $inv->unidad_envase, 1, 0, 'C', true);
                    $pdf->Cell(20, 6, $inv->stock_unidades, 1, 1, 'R', true);
                }

                $pdf->Ln(3);
            }
        } else {
            // Modo lote
            $pdf->SetFillColor(33, 35, 74); // Color #21234a
            $pdf->SetTextColor(255, 255, 255); // Blanco
            $pdf->SetFont('Arial', 'B', 8);
            
            $pdf->Cell(45, 7, utf8_decode('PRODUCTO'), 1, 0, 'C', true);
            $pdf->Cell(30, 7, utf8_decode('PROVEEDOR'), 1, 0, 'C', true);
            $pdf->Cell(15, 7, utf8_decode('UNID/PAQ'), 1, 0, 'C', true);
            $pdf->Cell(18, 7, utf8_decode('STOCK UND'), 1, 0, 'C', true);
            $pdf->Cell(18, 7, utf8_decode('STOCK CAJ'), 1, 0, 'C', true);
            $pdf->Cell(22, 7, utf8_decode('F. INGRESO'), 1, 0, 'C', true);
            $pdf->Cell(22, 7, utf8_decode('F. VENCIM'), 1, 1, 'C', true);

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0);

            $contador = 0;
            foreach ($inventarios as $inv) {
                if ($inv->stock_unidades == 0) {
                    $pdf->SetFillColor(255, 200, 200); // Rojo claro
                } else {
                    // Alternar entre blanco y gris muy claro
                    if ($contador % 2 == 0) {
                        $pdf->SetFillColor(255, 255, 255);
                    } else {
                        $pdf->SetFillColor(245, 245, 245);
                    }
                }
                $contador++;

                $pdf->Cell(45, 6, utf8_decode(substr($inv->item, 0, 25)), 1, 0, 'L', true);
                $pdf->Cell(30, 6, utf8_decode(substr($inv->proveedor, 0, 20)), 1, 0, 'L', true);
                $pdf->Cell(15, 6, $inv->unidad_envase, 1, 0, 'C', true);
                $pdf->Cell(18, 6, $inv->stock_unidades, 1, 0, 'R', true);
                $pdf->Cell(18, 6, $inv->stock_cajas, 1, 0, 'R', true);
                $pdf->Cell(22, 6, date('d/m/Y', strtotime($inv->created_at)), 1, 0, 'C', true);
                $pdf->Cell(22, 6, date('d/m/Y', strtotime($inv->fecha_vencimiento)), 1, 1, 'C', true);
            }
        }

        // Output
        $fecha = date('d_m_Y');
        $nombreArchivoLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombreAlmacen);
        $nombreArchivoLimpio = preg_replace('/_+/', '_', $nombreArchivoLimpio); // Eliminar guiones bajos duplicados
        $filename = 'reporteInventario_' . $nombreArchivoLimpio . '_' . $fecha . '.pdf';
        
        $pdfContent = $pdf->Output('S');
        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function exportarExcel(Request $request)
    {
        $modo = $request->input('modo', 'item');
        $idAlmacen = $request->input('idAlmacen');

        // Obtener nombre de almacÃƒÆ’Ã‚Â©n
        $almacen = \DB::table('almacens')->where('id', $idAlmacen)->first();
        $nombreAlmacen = $almacen ? $almacen->nombre_almacen : 'Desconocido';

        // Generar nombre del archivo
        $fecha = date('d_m_Y');
        $nombreArchivoLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombreAlmacen);
        $nombreArchivoLimpio = preg_replace('/_+/', '_', $nombreArchivoLimpio); // Eliminar guiones bajos duplicados
        $filename = 'reporteInventario_' . $nombreArchivoLimpio . '_' . $fecha . '.xlsx';

        return Excel::download(new InventarioExport($modo, $idAlmacen), $filename);
    }
    public function exportarBajoStockPDF(Request $request)
    {
        // 1. CAPTURAR FILTROS (Igual que en la vista)
        $almacen_id = $request->almacen_id;
        $medicamento = $request->medicamento;
        $laboratorio = $request->laboratorio;
        
        // Variables legacy por si acaso
        $buscar = $request->buscar; 
        $criterio = $request->criterio;

        // 2. CONSULTA (Copiada EXACTA de tu función productosBajoStock)
        // Quitamos los GROUP BY y SUM sql, usamos la lógica fila por fila
      $query = Inventario::join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
        ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
        ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
        ->join('personas', 'proveedores.id', '=', 'personas.id')
        ->select(
            'inventarios.idarticulo',
            'inventarios.idalmacen',
            'almacens.nombre_almacen',
            'almacens.ubicacion',
            'articulos.codigo',
            'articulos.nombre as nombre_producto',
            'articulos.unidad_envase',
            'articulos.stock as stock_minimo',
            DB::raw('SUM(inventarios.saldo_stock) as saldo_stock'),
            'personas.nombre as nombre_proveedor'
        )
        ->groupBy(
            'inventarios.idarticulo',
            'inventarios.idalmacen',
            'almacens.nombre_almacen',
            'almacens.ubicacion',
            'articulos.codigo',
            'articulos.nombre',
            'articulos.unidad_envase',
            'articulos.stock',
            'personas.nombre'
        )
        ->havingRaw('SUM(inventarios.saldo_stock) <= articulos.stock');

        // 3. APLICAR LOS MISMOS FILTROS
        $usuario = \Auth::user();
        if ($usuario->idrol != 4) {
            $query->where('almacens.sucursal', $usuario->idsucursal);
        }

        // Filtros del buscador nuevo
        if (!empty($almacen_id) && $almacen_id !== 'null') {
            $query->where('inventarios.idalmacen', $almacen_id);
        }
        if (!empty($medicamento) && $medicamento !== 'null') {
            $query->where('articulos.nombre', 'like', '%' . $medicamento . '%');
        }
        if (!empty($laboratorio) && $laboratorio !== 'null') {
            $query->where('personas.nombre', 'like', '%' . $laboratorio . '%');
        }
        if (!empty($codigo) && $codigo !== 'null') {
            $query->where('articulos.codigo', 'like', '%' . $codigo . '%');
        }

        // Filtro legacy (buscador antiguo)
        if (!empty($buscar)) {
            $query->where('inventarios.' . $criterio, 'like', '%' . $buscar . '%');
        }

        // 4. OBTENER DATOS (Sin paginar)
        $data = $query->orderBy('almacens.nombre_almacen', 'asc')
                    ->orderBy('personas.nombre', 'asc')
                    ->get();

        // 5. AGRUPAR PARA EL PDF (Visualmente)
        // Esto no filtra datos, solo los organiza para que el PDF dibuje los títulos
        $inventarios = $data->groupBy('nombre_almacen');

        // --- GENERACIÓN DEL PDF ---
        $pdf = new \FPDF('L', 'mm', 'A4');
        $pdf->AddPage();

        $this->addHeader($pdf);

        // LOGICA VISUAL DE FILTROS (Para que sepas qué imprimiste)
        $filtrosTexto = [];
        if (!empty($almacen_id) && $almacen_id !== 'null') {
            $nombre = \DB::table('almacens')->where('id', $almacen_id)->value('nombre_almacen');
            $filtrosTexto[] = "Almacén: " . ($nombre ?? $almacen_id);
        } else {
            $filtrosTexto[] = "Almacén: Todos";
        }
        if (!empty($medicamento) && $medicamento !== 'null') $filtrosTexto[] = "Med: " . $medicamento;
        if (!empty($laboratorio) && $laboratorio !== 'null') $filtrosTexto[] = "Lab: " . $laboratorio;
        if (!empty($codigo) && $codigo !== 'null') $filtrosTexto[] = "Cod: " . $codigo;
        
        // Imprimir filtros debajo del título
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(100);
        $pdf->Cell(0, 6, utf8_decode("Filtros: " . implode(" | ", $filtrosTexto)), 0, 1, 'C');
        $pdf->Ln(2);

        // Total de registros encontrados
        $this->addReportInfo($pdf, $data->count());

        foreach ($inventarios as $nombreAlmacen => $productos) {
            $this->addAlmacenHeader($pdf, $nombreAlmacen);
            $this->addStyledTable($pdf, $productos);
        }

        $this->addFooter($pdf);

        $fechaGeneracion = date('Y-m-d');
        $nombreArchivo = "Productos_bajo_stock_{$fechaGeneracion}.pdf";

        $pdf->Output('D', $nombreArchivo);
        exit;
    }
     private function addHeader($pdf)
    {
        $pdf->SetFillColor(52, 73, 94);
        $pdf->Rect(10, 10, 277, 20, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetXY(10, 16);
        $pdf->Cell(277, 8, utf8_decode('INFORME DE PRODUCTOS BAJO STOCK'), 0, 1, 'C');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(8);
    }

    private function addReportInfo($pdf, $totalRegistros)
    {
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);

        $pdf->Cell(70, 6, utf8_decode('Fecha: ' . date('d/m/Y H:i')), 0, 0, 'L');
        $pdf->Cell(70, 6, utf8_decode('Total productos: ' . $totalRegistros), 0, 0, 'L');
        $pdf->Cell(70, 6, utf8_decode('Estado: Requiere reposición'), 0, 1, 'L');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
    }

    private function addAlmacenHeader($pdf, $nombreAlmacen)
    {
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Cell(277, 8, utf8_decode('ALMACÉN: ' . $nombreAlmacen), 1, 1, 'L', true);
        $pdf->Ln(2);
    }

    private function addStyledTable($pdf, $productos)
    {
        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(52, 73, 94);

        $widths = [25, 95, 18, 20, 18, 62, 39];
        $headers = ['Código', 'Producto', 'Unidad', 'Mínimo', 'Actual', 'Proveedor', 'Estado'];

        $x = 10;
        foreach ($headers as $i => $header) {
            $pdf->SetXY($x, $pdf->GetY());
            $pdf->Cell($widths[$i], 8, utf8_decode($header), 1, 0, 'C', true);
            $x += $widths[$i];
        }
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);
        $fill = false;
        $rowCount = 0;

        foreach ($productos as $inv) {
            if ($pdf->GetY() > 175) {
                $pdf->AddPage();
                $this->addTableHeader($pdf, $widths, $headers);
            }

            $y = $pdf->GetY();

            if ($inv->saldo_stock == 0) {
                $pdf->SetFillColor(255, 235, 235);
                $pdf->SetTextColor(192, 57, 43);
            } else {
                $pdf->SetFillColor($fill ? 249 : 255, $fill ? 249 : 255, $fill ? 249 : 255);
                $pdf->SetTextColor(0, 0, 0);
            }

            $data = [
                utf8_decode($inv->codigo),
                utf8_decode($this->truncateText($inv->nombre_producto, 25)),
                utf8_decode($inv->unidad_envase),
                utf8_decode($inv->stock_minimo),
                utf8_decode($inv->saldo_stock),
                utf8_decode($this->truncateText($inv->nombre_proveedor, 25)),
            ];

            $x = 10;
            foreach ($data as $i => $item) {
                $align = in_array($i, [2, 3, 4]) ? 'C' : 'L';
                $pdf->SetXY($x, $y);
                $pdf->Cell($widths[$i], 7, $item, 1, 0, $align, true);
                $x += $widths[$i];
            }

            // Agregar columna de Estado
            $estado = $inv->saldo_stock == 0 ? 'Sin Stock' : 'Bajo Stock';
            $pdf->SetXY($x, $y);
            $pdf->Cell($widths[6], 6, utf8_decode($estado), 1, 0, 'C', true);

            $pdf->Ln();
            $fill = !$fill;
            $rowCount++;
        }
    }

    private function addTableHeader($pdf, $widths, $headers)
    {
        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(52, 73, 94);

        $x = 10;
        foreach ($headers as $i => $header) {
            $pdf->SetXY($x, $pdf->GetY());
            $pdf->Cell($widths[$i], 8, utf8_decode($header), 1, 0, 'C', true);
            $x += $widths[$i];
        }
        $pdf->Ln();
    }

    private function addFooter($pdf)
    {
        $pdf->Ln(5);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, utf8_decode('Los productos marcados requieren reposición urgente según stock mínimo establecido.'), 0, 1, 'L');

        $pdf->SetY(-15);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 10, utf8_decode('Sistema de Inventarios - Página ' . $pdf->PageNo() . ' - Generado: ' . date('d/m/Y H:i:s')), 0, 0, 'C');
    }

    private function truncateText($text, $maxLength)
    {
        return strlen($text) > $maxLength ? substr($text, 0, $maxLength - 2) . '..' : $text;
    }
}

class PDFInventario extends FPDF
{
    public function Footer()
    {
        // Posiciona el pie de pÃƒÆ’Ã‚Â¡gina a 1.5 cm del final
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

