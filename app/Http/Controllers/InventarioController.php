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

use Carbon\Carbon;
use App\ConfiguracionHistorialInventario;
use App\Exports\InventarioHistoricoExport;

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
        if (!$request->ajax()) {
            return redirect('/');
        }

        $usuario = \Auth::user();

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        $almacen_id = $request->almacen_id;
        $medicamento = $request->medicamento;
        $laboratorio = $request->laboratorio;
        $codigo = trim((string) $request->codigo);

        /*
        |--------------------------------------------------------------------------
        | 1. Obtener los almacenes del mismo departamento
        |--------------------------------------------------------------------------
        */

        $almacenesColumnas = collect();

        if (!empty($almacen_id)) {

            $almacenSeleccionado = DB::table('almacens')
                ->join(
                    'sucursales',
                    'almacens.sucursal',
                    '=',
                    'sucursales.id'
                )
                ->where('almacens.id', $almacen_id)
                ->select(
                    'almacens.id',
                    'almacens.nombre_almacen',
                    'sucursales.departamento'
                )
                ->first();

            if ($almacenSeleccionado) {

                $almacenesColumnas = DB::table('almacens')
                    ->join(
                        'sucursales',
                        'almacens.sucursal',
                        '=',
                        'sucursales.id'
                    )
                    ->where(
                        'sucursales.departamento',
                        $almacenSeleccionado->departamento
                    )
                    // No repetir el almacén seleccionado
                    ->where('almacens.id', '<>', (int) $almacen_id)
                    // El depósito siempre tendrá su columna fija
                    ->where('almacens.id', '<>', 2)
                    ->select(
                        'almacens.id',
                        'almacens.nombre_almacen'
                    )
                    ->orderBy('almacens.nombre_almacen', 'asc')
                    ->get();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Consulta principal de inventarios
        |--------------------------------------------------------------------------
        */

        $query = Inventario::join(
            'almacens',
            'inventarios.idalmacen',
            '=',
            'almacens.id'
        )
            ->join(
                'articulos',
                'inventarios.idarticulo',
                '=',
                'articulos.id'
            )
            ->leftJoin(
                'proveedores',
                'articulos.idproveedor',
                '=',
                'proveedores.id'
            )
            ->leftJoin(
                'personas',
                'proveedores.id',
                '=',
                'personas.id'
            )

            /*
            |--------------------------------------------------------------------------
            | Stock fijo del depósito (ID 2)
            |--------------------------------------------------------------------------
            */

            ->leftJoin(
                DB::raw('(
                SELECT
                    idarticulo,
                    SUM(saldo_stock) AS stock_almacen_2
                FROM inventarios
                WHERE idalmacen = 2
                GROUP BY idarticulo
            ) AS inventario_almacen_2'),
                'inventario_almacen_2.idarticulo',
                '=',
                'inventarios.idarticulo'
            )

            ->where('articulos.condicion', 1)

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

                DB::raw("
                COALESCE(
                    MAX(personas.nombre),
                    'Sin proveedor'
                ) AS nombre_proveedor
            "),

                DB::raw(
                    'SUM(inventarios.saldo_stock) AS saldo_stock'
                ),

                DB::raw(
                    'COALESCE(
                    inventario_almacen_2.stock_almacen_2,
                    0
                ) AS stock_almacen_2'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | 3. Agregar el stock individual de cada almacén del departamento
        |--------------------------------------------------------------------------
        */

        foreach ($almacenesColumnas as $almacenColumna) {

            $idAlmacenColumna = (int) $almacenColumna->id;

            $query->addSelect(DB::raw(
                '(SELECT COALESCE(SUM(inv2.saldo_stock), 0)
              FROM inventarios AS inv2
              WHERE inv2.idarticulo = inventarios.idarticulo
              AND inv2.idalmacen = ' . $idAlmacenColumna . ')
              AS stock_almacen_' . $idAlmacenColumna
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Agrupación
        |--------------------------------------------------------------------------
        */

        $query->groupBy(
            'inventarios.idarticulo',
            'inventarios.idalmacen',
            'almacens.nombre_almacen',
            'almacens.ubicacion',
            'articulos.codigo',
            'articulos.nombre',
            'articulos.unidad_envase',
            'articulos.stock',
            'articulos.precio_costo_unid',
            'inventario_almacen_2.stock_almacen_2'
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Mostrar productos con bajo stock
        |--------------------------------------------------------------------------
        */

        $query->havingRaw(
            'articulos.stock > SUM(inventarios.saldo_stock)'
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Filtrar por sucursal del usuario
        |--------------------------------------------------------------------------
        */

        if ($usuario->idrol != 4) {
            $query->where(
                'almacens.sucursal',
                $usuario->idsucursal
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Búsqueda general
        |--------------------------------------------------------------------------
        */

        if (!empty($buscar) && !empty($criterio)) {

            $query->where(
                'inventarios.' . $criterio,
                'like',
                '%' . $buscar . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Filtrar por almacén seleccionado
        |--------------------------------------------------------------------------
        */

        if (!empty($almacen_id)) {
            $query->where(
                'inventarios.idalmacen',
                $almacen_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Filtrar por medicamento
        |--------------------------------------------------------------------------
        */

        if (!empty($medicamento)) {
            $query->where(
                'articulos.nombre',
                'like',
                '%' . $medicamento . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Filtrar por laboratorio o proveedor
        |--------------------------------------------------------------------------
        */

        if (!empty($laboratorio)) {
            $query->whereRaw(
                "COALESCE(personas.nombre, 'Sin proveedor') LIKE ?",
                ['%' . $laboratorio . '%']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Filtrar por código
        |--------------------------------------------------------------------------
        */

        if ($codigo !== '') {
            $query->where(
                'articulos.codigo',
                'like',
                '%' . $codigo . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Ordenar y paginar
        |--------------------------------------------------------------------------
        */

        $inventarios = $query
            ->orderByRaw('SUM(inventarios.saldo_stock) ASC')
            ->orderBy('almacens.nombre_almacen', 'asc')
            ->orderBy('articulos.nombre', 'asc')
            ->paginate(13);

        /*
        |--------------------------------------------------------------------------
        | 13. Respuesta para Vue
        |--------------------------------------------------------------------------
        */

        return [
            'pagination' => [
                'total' => $inventarios->total(),
                'current_page' => $inventarios->currentPage(),
                'per_page' => $inventarios->perPage(),
                'last_page' => $inventarios->lastPage(),
                'from' => $inventarios->firstItem(),
                'to' => $inventarios->lastItem(),
            ],

            'inventarios' => $inventarios,

            'almacenes_columnas' => $almacenesColumnas
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

        $rutaLogo = public_path('img/logoPrincipal.png');
        if (file_exists($rutaLogo)) {
            $pdf->Image($rutaLogo, 10, 5, 20);
        }

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 10, utf8_decode('REPORTE DE PRODUCTOS CON BAJO STOCK'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, utf8_decode('Fecha de generacion: ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        $pdf->Ln(5);

        $filtrosY = $pdf->GetY();
        $txtFiltros = [
            'Código: ' . $toAscii($filtros['codigo'] ?: 'Todos'),
            'Almacen: ' . $toAscii($filtros['nombre_almacen'] ?? 'Todos'),
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

        $textoBusqueda = 'Ninguna';
        if ($filtros['productos'] !== '' && $filtros['codigo'] !== '') {
            $textoBusqueda = $filtros['productos'] . ' / ' . $filtros['codigo'];
        } elseif ($filtros['productos'] !== '') {
            $textoBusqueda = $filtros['productos'];
        } elseif ($filtros['codigo'] !== '') {
            $textoBusqueda = $filtros['codigo'];
        }

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(10, $filtrosY, 277, 21, 'F');
        $pdf->SetY($filtrosY);
        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Rect(10, $pdf->GetY(), 277, 16, 'F');

        $pdf->SetX(12);
        $pdf->Cell(25, 8, utf8_decode('Almacen:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(100, 8, utf8_decode(substr($toAscii($filtros['nombre_almacen'] ?? 'Todos'), 0, 50)), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 8, utf8_decode('Proveedor:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(100, 8, utf8_decode(substr($toAscii($filtros['proveedor'] ?: 'Todos'), 0, 50)), 0, 1, 'L');

        $pdf->SetX(12);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 8, utf8_decode('Categoria:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(100, 8, utf8_decode(substr($toAscii($filtros['nombre_categoria'] ?? 'Todas'), 0, 50)), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 8, utf8_decode('Busqueda:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(100, 8, utf8_decode(substr($toAscii($textoBusqueda), 0, 50)), 0, 1, 'L');
        $pdf->Ln(5);

        $w = [13, 36, 76, 46, 28, 28];
        $headers = ['Código', 'Almacen', 'Producto', 'Categoria', 'Stock Actual', 'Stock Minimo'];

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
            $pdf->Cell($w[0], 6, substr($toAscii($row->codigo), 0, 12), 1, 0, 'L', true);
            $pdf->Cell($w[1], 6, substr($toAscii($row->nombre_almacen), 0, 22), 1, 0, 'L', true);
            $pdf->Cell($w[2], 6, substr($toAscii($row->nombre_producto), 0, 42), 1, 0, 'L', true);
            $pdf->Cell($w[3], 6, substr($toAscii($row->nombre_categoria), 0, 26), 1, 0, 'L', true);
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
                'articulos.codigo',
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
                'articulos.codigo',
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

    private function consultaInventarioItemLote(Request $request, $tipo)
    {
        $idAlmacen = $request->idAlmacen;
        $buscar = $request->buscar;
        $orden = $request->get('orden', 'stock_asc');

        /*
        |--------------------------------------------------------------------------
        | 1. Obtener los almacenes del mismo departamento
        |--------------------------------------------------------------------------
        */

        $almacenesColumnas = collect();

        if (!empty($idAlmacen)) {

            $almacenSeleccionado = DB::table('almacens')
                ->join(
                    'sucursales',
                    'almacens.sucursal',
                    '=',
                    'sucursales.id'
                )
                ->where('almacens.id', $idAlmacen)
                ->select(
                    'almacens.id',
                    'almacens.nombre_almacen',
                    'sucursales.departamento'
                )
                ->first();

            if ($almacenSeleccionado) {

                $almacenesColumnas = DB::table('almacens')
                    ->join(
                        'sucursales',
                        'almacens.sucursal',
                        '=',
                        'sucursales.id'
                    )
                    ->where(
                        'sucursales.departamento',
                        $almacenSeleccionado->departamento
                    )
                    // Excluir el almacén seleccionado
                    ->where('almacens.id', '<>', (int) $idAlmacen)
                    // Excluir el depósito
                    ->where('almacens.id', '<>', 2)
                    ->select(
                        'almacens.id',
                        'almacens.nombre_almacen'
                    )
                    ->orderBy('almacens.nombre_almacen', 'asc')
                    ->get();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Consulta principal según el tipo
        |--------------------------------------------------------------------------
        */

        if ($tipo === 'item') {

            $inventarios = Articulo::leftJoin('inventarios', function ($join) use ($idAlmacen) {
                $join->on('articulos.id', '=', 'inventarios.idarticulo')
                    ->where('inventarios.idalmacen', '=', $idAlmacen);
            })
                ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->leftJoin('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->select(
                    'articulos.id',
                    'articulos.codigo',
                    'articulos.nombre as nombre_producto',
                    'articulos.unidad_envase',
                    'almacens.nombre_almacen',
                    'proveedores.contacto as nombre_proveedor',
                    'articulos.descripcion_fabrica',
                    'categorias.nombre as nombre_categoria',

                    // Stock total del almacén seleccionado
                    DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) as saldo_stock_total'),

                    // Stock del depósito
                    DB::raw('
    (
        SELECT COALESCE(SUM(inv2.saldo_stock), 0)
        FROM inventarios AS inv2
        WHERE inv2.idarticulo = articulos.id
        AND inv2.idalmacen = 2
    ) AS stock_deposito
'),

                    // Cajas completas
                    DB::raw('FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / NULLIF(articulos.unidad_envase, 0)) as cajas'),

                    // Unidades sueltas
                    DB::raw('(IFNULL(SUM(inventarios.saldo_stock), 0) % NULLIF(articulos.unidad_envase, 0)) as unidades'),

                    // Stock formateado
                    DB::raw("
                    CONCAT(
                        FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / NULLIF(articulos.unidad_envase, 0)),
                        ' cajas y ',
                        (IFNULL(SUM(inventarios.saldo_stock), 0) % NULLIF(articulos.unidad_envase, 0)),
                        ' unidades'
                    ) as stock_formateado
                ")
                );

            /*
            |--------------------------------------------------------------------------
            | 3. Agregar stock individual de cada almacén del departamento
            |--------------------------------------------------------------------------
            */

            foreach ($almacenesColumnas as $almacenColumna) {

                $idAlmacenColumna = (int) $almacenColumna->id;

                $inventarios->addSelect(DB::raw(
                    '(SELECT COALESCE(SUM(inv2.saldo_stock), 0)
                    FROM inventarios AS inv2
                    WHERE inv2.idarticulo = articulos.id
                    AND inv2.idalmacen = ' . $idAlmacenColumna . ')
                    AS stock_almacen_' . $idAlmacenColumna
                ));
            }

            $inventarios
                ->where('articulos.condicion', '=', 1)
                ->groupBy(
                    'articulos.id',
                    'articulos.codigo',
                    'articulos.nombre',
                    'almacens.nombre_almacen',
                    'articulos.unidad_envase',
                    'proveedores.contacto',
                    'articulos.descripcion_fabrica',
                    'categorias.nombre'
                );

        } elseif ($tipo === 'lote') {

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
                    DB::raw('FLOOR(IFNULL(inventarios.saldo_stock, 0) / NULLIF(articulos.unidad_envase, 0)) as stock_en_paquetes'),
                    DB::raw('IFNULL(inventarios.saldo_stock, 0) % NULLIF(articulos.unidad_envase, 0) as unidades_restantes')
                )
                ->where('articulos.condicion', '=', 1)
                ->orderByRaw('IFNULL(inventarios.saldo_stock, 0) ASC')
                ->orderBy('articulos.nombre');
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Búsqueda general
        |--------------------------------------------------------------------------
        */

        if (!empty($buscar)) {

            $palabras = explode(' ', $buscar);

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

        /*
    |--------------------------------------------------------------------------
    | Ordenamiento del inventario
    |--------------------------------------------------------------------------
    */

        if ($tipo === 'item') {

            switch ($orden) {

                case 'nombre_asc':

                    // Orden alfabético A - Z
                    $inventarios->orderBy('articulos.nombre', 'asc')
                        ->orderBy('categorias.nombre', 'asc');

                    break;

                case 'stock_desc':

                    // Mayor a menor stock
                    $inventarios->orderByRaw(
                        'IFNULL(SUM(inventarios.saldo_stock), 0) DESC'
                    );

                    $inventarios->orderBy('articulos.nombre', 'asc');

                    break;

                case 'stock_asc':
                default:

                    // Menor a mayor stock
                    $inventarios->orderByRaw(
                        'IFNULL(SUM(inventarios.saldo_stock), 0) ASC'
                    );

                    $inventarios->orderBy('articulos.nombre', 'asc');

                    break;
            }

            $inventarios->orderBy('almacens.nombre_almacen', 'asc');
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Paginar
        |--------------------------------------------------------------------------
        */

        return [
            'query' => $inventarios,
            'almacenes_columnas' => $almacenesColumnas
        ];
    }

    public function indexItemLote(Request $request, $tipo)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $resultado = $this->consultaInventarioItemLote(
            $request,
            $tipo
        );

        $inventarios = $resultado['query']->paginate(20);

        return [
            'pagination' => [
                'total' => $inventarios->total(),
                'current_page' => $inventarios->currentPage(),
                'per_page' => $inventarios->perPage(),
                'last_page' => $inventarios->lastPage(),
                'from' => $inventarios->firstItem(),
                'to' => $inventarios->lastItem(),
            ],

            'inventarios' => $inventarios,

            'almacenes_columnas' => $resultado['almacenes_columnas']
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
            ->groupBy('s.id', 's.nombre')
            ->get();

        return response()->json(['stocks' => $stocks]);
    }

    public function exportarPdf(Request $request)
    {
        $modo = $request->input('modo', 'item');
        $idAlmacen = (int) $request->input('idAlmacen');
        $buscar = $request->input('buscar', '');
        $orden = $request->input('orden', 'stock_asc');

        if (!in_array($modo, ['item', 'lote'], true)) {
            abort(422, 'Modo de inventario no válido.');
        }

        // Restricción de acceso
        if ((int) auth()->user()->idrol === 2) {
            abort(403, 'No tiene permisos para exportar.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Obtener almacén seleccionado
        |--------------------------------------------------------------------------
        */

        $almacen = DB::table('almacens')
            ->where('id', $idAlmacen)
            ->first();

        if (!$almacen) {
            abort(422, 'Debe seleccionar un almacén válido.');
        }

        $nombreAlmacen = $almacen->nombre_almacen;

        /*
        |--------------------------------------------------------------------------
        | 2. Reutilizar la consulta del listado
        |--------------------------------------------------------------------------
        */

        $resultado = $this->consultaInventarioItemLote(
            $request,
            $modo
        );

        $inventarios = $resultado['query']->get();

        $almacenesColumnas = $resultado['almacenes_columnas'];

        /*
        |--------------------------------------------------------------------------
        | 3. Configuración del PDF
        |--------------------------------------------------------------------------
        */

        $pdf = new PDFInventario('L', 'mm', 'A4');

        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $anchoTotal = 277;

        /*
        |--------------------------------------------------------------------------
        | 4. Funciones auxiliares
        |--------------------------------------------------------------------------
        */

        // Ajustar texto al ancho disponible de la celda
        $ajustarTexto = function ($texto, $ancho) use ($pdf) {

            $texto = utf8_decode((string) $texto);

            if ($pdf->GetStringWidth($texto) <= $ancho - 3) {
                return $texto;
            }

            while (
                strlen($texto) > 0 &&
                $pdf->GetStringWidth($texto . '...') > $ancho - 3
            ) {
                $texto = substr($texto, 0, -1);
            }

            return $texto . '...';
        };

        // Mostrar stock con su unidad de medida
        $formatearStock = function ($stock, $unidad = '') {

            $stock = (float) $stock;

            if ($stock == 0) {
                return 'Sin Stock';
            }

            $cantidad = rtrim(
                rtrim(number_format($stock, 4, '.', ''), '0'),
                '.'
            );

            return trim($cantidad . ' ' . $unidad);
        };

        /*
        |--------------------------------------------------------------------------
        | 5. Encabezado principal
        |--------------------------------------------------------------------------
        */

        $rutaLogo = public_path('img/logoPrincipal.png');

        if (file_exists($rutaLogo)) {
            $pdf->Image($rutaLogo, 10, 5, 20);
        }

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(44, 62, 80);

        $pdf->Cell(
            0,
            10,
            utf8_decode('REPORTE DE INVENTARIO'),
            0,
            1,
            'C'
        );

        $pdf->SetFont('Arial', '', 10);

        $pdf->Cell(
            0,
            6,
            utf8_decode(
                'Fecha de generación: ' . date('d/m/Y H:i:s')
            ),
            0,
            1,
            'C'
        );

        $pdf->Ln(5);

        /*
        |--------------------------------------------------------------------------
        | 6. Información de filtros
        |--------------------------------------------------------------------------
        */

        $ordenes = [
            'nombre_asc' => 'Nombre de A - Z',
            'stock_asc' => 'Menor a mayor stock',
            'stock_desc' => 'Mayor a menor stock'
        ];

        $nombreOrden = $ordenes[$orden] ?? $ordenes['stock_asc'];

        if ($modo === 'lote') {
            $nombreOrden = 'Menor a mayor stock';
        }

        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Rect(
            10,
            $pdf->GetY(),
            $anchoTotal,
            24,
            'F'
        );

        // Almacén
        $pdf->SetX(12);
        $pdf->SetFont('Arial', 'B', 9);

        $pdf->Cell(
            30,
            8,
            utf8_decode('Almacén:'),
            0,
            0,
            'L'
        );

        $pdf->SetFont('Arial', '', 9);

        $pdf->Cell(
            230,
            8,
            $ajustarTexto($nombreAlmacen, 230),
            0,
            1,
            'L'
        );

        // Búsqueda
        $pdf->SetX(12);
        $pdf->SetFont('Arial', 'B', 9);

        $pdf->Cell(
            30,
            8,
            utf8_decode('Búsqueda:'),
            0,
            0,
            'L'
        );

        $pdf->SetFont('Arial', '', 9);

        $pdf->Cell(
            230,
            8,
            $ajustarTexto(
                !empty($buscar) ? $buscar : 'Ninguna',
                230
            ),
            0,
            1,
            'L'
        );

        // Ordenamiento
        $pdf->SetX(12);
        $pdf->SetFont('Arial', 'B', 9);

        $pdf->Cell(
            30,
            8,
            utf8_decode('Orden:'),
            0,
            0,
            'L'
        );

        $pdf->SetFont('Arial', '', 9);

        $pdf->Cell(
            230,
            8,
            utf8_decode($nombreOrden),
            0,
            1,
            'L'
        );

        $pdf->Ln(5);

        /*
        |--------------------------------------------------------------------------
        | 7. Definir columnas del reporte
        |--------------------------------------------------------------------------
        */

        $columnas = [];

        if ($modo === 'item') {

            // Cantidad de columnas correspondientes a stock
            $cantidadColumnasStock = 1;

            // Mostrar depósito si no es el seleccionado
            if ($idAlmacen !== 2) {
                $cantidadColumnasStock++;
            }

            // Almacenes adicionales
            $cantidadColumnasStock += $almacenesColumnas->count();

            // Ajustar los anchos según la cantidad de almacenes
            $anchoCodigo = 25;
            $anchoProducto = 82;

            if ($cantidadColumnasStock >= 6) {
                $anchoCodigo = 22;
                $anchoProducto = 65;
            }

            $anchoStock = (
                $anchoTotal - $anchoCodigo - $anchoProducto
            ) / $cantidadColumnasStock;

            // Código
            $columnas[] = [
                'campo' => 'codigo',
                'titulo' => 'Código',
                'ancho' => $anchoCodigo,
                'alineacion' => 'L',
                'tipo' => 'texto'
            ];

            // Producto
            $columnas[] = [
                'campo' => 'nombre_producto',
                'titulo' => 'Producto',
                'ancho' => $anchoProducto,
                'alineacion' => 'L',
                'tipo' => 'texto'
            ];

            // Stock actual
            $columnas[] = [
                'campo' => 'saldo_stock_total',
                'titulo' => 'Stock Actual',
                'ancho' => $anchoStock,
                'alineacion' => 'C',
                'tipo' => 'stock'
            ];

            // Stock del depósito
            if ($idAlmacen !== 2) {

                $columnas[] = [
                    'campo' => 'stock_deposito',
                    'titulo' => 'Stock Depósito',
                    'ancho' => $anchoStock,
                    'alineacion' => 'C',
                    'tipo' => 'stock'
                ];
            }

            // Columnas dinámicas de otros almacenes
            foreach ($almacenesColumnas as $almacenColumna) {

                $columnas[] = [
                    'campo' => 'stock_almacen_' . $almacenColumna->id,
                    'titulo' => $almacenColumna->nombre_almacen,
                    'ancho' => $anchoStock,
                    'alineacion' => 'C',
                    'tipo' => 'stock'
                ];
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | Columnas para modo lote
            |--------------------------------------------------------------------------
            */

            $columnas = [
                [
                    'campo' => 'nombre_producto',
                    'titulo' => 'Producto',
                    'ancho' => 77,
                    'alineacion' => 'L',
                    'tipo' => 'texto'
                ],
                [
                    'campo' => 'nombre_proveedor',
                    'titulo' => 'Proveedor',
                    'ancho' => 50,
                    'alineacion' => 'L',
                    'tipo' => 'texto'
                ],
                [
                    'campo' => 'unidad_envase',
                    'titulo' => 'Unid/Paq',
                    'ancho' => 20,
                    'alineacion' => 'C',
                    'tipo' => 'texto'
                ],
                [
                    'campo' => 'cantidad',
                    'titulo' => 'Cantidad',
                    'ancho' => 25,
                    'alineacion' => 'C',
                    'tipo' => 'texto'
                ],
                [
                    'campo' => 'saldo_stock',
                    'titulo' => 'Stock Actual',
                    'ancho' => 35,
                    'alineacion' => 'C',
                    'tipo' => 'stock'
                ],
                [
                    'campo' => 'fecha_ingreso',
                    'titulo' => 'F. Ingreso',
                    'ancho' => 35,
                    'alineacion' => 'C',
                    'tipo' => 'fecha'
                ],
                [
                    'campo' => 'fecha_vencimiento',
                    'titulo' => 'F. Vencimiento',
                    'ancho' => 35,
                    'alineacion' => 'C',
                    'tipo' => 'fecha'
                ]
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Función para imprimir encabezados de tabla
        |--------------------------------------------------------------------------
        */

        $imprimirCabecera = function () use ($pdf, $columnas, $ajustarTexto) {

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(52, 73, 94);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(200, 205, 210);

            foreach ($columnas as $indice => $columna) {

                $ultima = $indice === count($columnas) - 1;

                $pdf->Cell(
                    $columna['ancho'],
                    9,
                    $ajustarTexto(
                        $columna['titulo'],
                        $columna['ancho']
                    ),
                    1,
                    $ultima ? 1 : 0,
                    'C',
                    true
                );
            }

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 8);
        };

        $imprimirCabecera();

        /*
        |--------------------------------------------------------------------------
        | 9. Imprimir registros del inventario
        |--------------------------------------------------------------------------
        */

        $fill = false;
        $altoFila = 7;

        foreach ($inventarios as $inv) {

            // Controlar salto de página
            if (
                $pdf->GetY() + $altoFila >
                $pdf->GetPageHeight() - 20
            ) {
                $pdf->AddPage();
                $imprimirCabecera();
            }

            // Alternar color de fondo
            if ($fill) {
                $pdf->SetFillColor(245, 247, 249);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            foreach ($columnas as $indice => $columna) {

                $campo = $columna['campo'];
                $valor = $inv->{$campo} ?? null;

                $ultima = $indice === count($columnas) - 1;

                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(0, 0, 0);

                /*
                |--------------------------------------------------------------------------
                | Formato de stock
                |--------------------------------------------------------------------------
                */

                if ($columna['tipo'] === 'stock') {

                    $stock = (float) ($valor ?? 0);

                    $unidad = $modo === 'item'
                        ? ($inv->descripcion_fabrica ?? '')
                        : '';

                    $valor = $formatearStock(
                        $stock,
                        $unidad
                    );

                    if ($stock == 0) {

                        // Sin Stock en rojo
                        $pdf->SetTextColor(220, 38, 38);
                        $pdf->SetFont('Arial', 'B', 8);

                    } elseif ($campo === 'saldo_stock_total') {

                        // Resaltar stock del almacén seleccionado
                        $pdf->SetFont('Arial', 'B', 8);
                    }

                } elseif ($columna['tipo'] === 'fecha') {

                    $valor = !empty($valor)
                        ? date('d/m/Y', strtotime($valor))
                        : '-';

                } else {

                    $valor = ($valor === null || $valor === '')
                        ? '-'
                        : $valor;
                }

                $pdf->Cell(
                    $columna['ancho'],
                    $altoFila,
                    $ajustarTexto(
                        $valor,
                        $columna['ancho']
                    ),
                    1,
                    $ultima ? 1 : 0,
                    $columna['alineacion'],
                    true
                );
            }

            $fill = !$fill;
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Total de registros
        |--------------------------------------------------------------------------
        */

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 9);

        if ($inventarios->isEmpty()) {

            $pdf->Cell(
                $anchoTotal,
                10,
                utf8_decode('No se encontraron registros.'),
                1,
                1,
                'C'
            );
        }

        $pdf->Ln(4);

        $pdf->Cell(
            0,
            7,
            utf8_decode(
                'Total de registros: ' . $inventarios->count()
            ),
            0,
            1,
            'L'
        );

        /*
        |--------------------------------------------------------------------------
        | 11. Generar archivo PDF
        |--------------------------------------------------------------------------
        */

        $fecha = date('d_m_Y');

        $nombreArchivoLimpio = preg_replace(
            '/[^a-zA-Z0-9_-]/',
            '_',
            $nombreAlmacen
        );

        $nombreArchivoLimpio = preg_replace(
            '/_+/',
            '_',
            $nombreArchivoLimpio
        );

        $filename = 'reporteInventario_'
            . $nombreArchivoLimpio
            . '_'
            . $fecha
            . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'attachment; filename="' . $filename . '"'
            );
    }

    public function exportarExcel(Request $request)
    {
        $modo = $request->input('modo', 'item');
        $idAlmacen = (int) $request->input('idAlmacen');
        $buscar = $request->input('buscar', '');
        $orden = $request->input('orden', 'stock_asc');

        // Validar modo
        if (!in_array($modo, ['item', 'lote'], true)) {
            abort(422, 'Modo de inventario no válido.');
        }

        // Restricción de acceso
        if ((int) auth()->user()->idrol === 2) {
            abort(403, 'No tiene permisos para exportar.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Obtener almacén seleccionado
        |--------------------------------------------------------------------------
        */

        $almacen = DB::table('almacens')
            ->where('id', $idAlmacen)
            ->first();

        if (!$almacen) {
            abort(422, 'Debe seleccionar un almacén válido.');
        }

        $nombreAlmacen = $almacen->nombre_almacen;

        /*
        |--------------------------------------------------------------------------
        | 2. Reutilizar consulta del inventario
        |--------------------------------------------------------------------------
        */

        $resultado = $this->consultaInventarioItemLote(
            $request,
            $modo
        );

        $query = $resultado['query'];

        $almacenesColumnas = $resultado['almacenes_columnas'];

        /*
        |--------------------------------------------------------------------------
        | 3. Generar nombre del archivo
        |--------------------------------------------------------------------------
        */

        $fecha = date('d_m_Y');

        $nombreArchivoLimpio = preg_replace(
            '/[^a-zA-Z0-9_-]/',
            '_',
            $nombreAlmacen
        );

        $nombreArchivoLimpio = preg_replace(
            '/_+/',
            '_',
            $nombreArchivoLimpio
        );

        $filename = 'reporteInventario_'
            . $nombreArchivoLimpio
            . '_'
            . $fecha
            . '.xlsx';

        /*
        |--------------------------------------------------------------------------
        | 4. Exportar Excel
        |--------------------------------------------------------------------------
        */

        return Excel::download(
            new InventarioExport(
                $query,
                $modo,
                $idAlmacen,
                $nombreAlmacen,
                $almacenesColumnas,
                $buscar,
                $orden
            ),
            $filename
        );
    }
    public function exportarBajoStockPDF(Request $request)
    {
        // 1. CAPTURAR FILTROS (Igual que en la vista)
        $almacen_id = $request->almacen_id;
        $medicamento = $request->medicamento;
        $laboratorio = $request->laboratorio;
        $codigo = trim((string) $request->codigo);

        // Variables legacy por si acaso
        $buscar = $request->buscar;
        $criterio = $request->criterio;

        // 2. CONSULTA
        $query = Inventario::join(
            'almacens',
            'inventarios.idalmacen',
            '=',
            'almacens.id'
        )
            ->join(
                'articulos',
                'inventarios.idarticulo',
                '=',
                'articulos.id'
            )
            ->leftJoin(
                'proveedores',
                'articulos.idproveedor',
                '=',
                'proveedores.id'
            )
            ->leftJoin(
                'personas',
                'proveedores.id',
                '=',
                'personas.id'
            )
            // Stock del producto en el almacén con ID 2
            ->leftJoin(
                DB::raw('(
                SELECT
                    idarticulo,
                    SUM(saldo_stock) AS stock_almacen_2
                FROM inventarios
                WHERE idalmacen = 2
                GROUP BY idarticulo
            ) AS inventario_almacen_2'),
                'inventario_almacen_2.idarticulo',
                '=',
                'inventarios.idarticulo'
            )
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
                DB::raw("COALESCE(personas.nombre, 'Sin proveedor') as nombre_proveedor"),
                DB::raw('COALESCE(inventario_almacen_2.stock_almacen_2, 0) as stock_almacen_2')
            )
            ->where('articulos.condicion', 1)
            ->groupBy(
                'inventarios.idarticulo',
                'inventarios.idalmacen',
                'almacens.nombre_almacen',
                'almacens.ubicacion',
                'articulos.codigo',
                'articulos.nombre',
                'articulos.unidad_envase',
                'articulos.stock',
                DB::raw("COALESCE(personas.nombre, 'Sin proveedor')"),
                'inventario_almacen_2.stock_almacen_2'
            )
            ->havingRaw(
                'SUM(inventarios.saldo_stock) <= articulos.stock'
            );

        // 3. APLICAR LOS MISMOS FILTROS

        $usuario = \Auth::user();

        if ($usuario->idrol != 4) {
            $query->where(
                'almacens.sucursal',
                $usuario->idsucursal
            );
        }

        // Filtro almacén
        if (!empty($almacen_id) && $almacen_id !== 'null') {
            $query->where(
                'inventarios.idalmacen',
                $almacen_id
            );
        }

        // Filtro medicamento
        if (!empty($medicamento) && $medicamento !== 'null') {
            $query->where(
                'articulos.nombre',
                'like',
                '%' . $medicamento . '%'
            );
        }

        // Filtro laboratorio / proveedor
        if (!empty($laboratorio) && $laboratorio !== 'null') {
            $query->whereRaw(
                "COALESCE(personas.nombre, 'Sin proveedor') like ?",
                ['%' . $laboratorio . '%']
            );
        }

        // Filtro código
        if (!empty($codigo) && $codigo !== 'null') {
            $query->where(
                'articulos.codigo',
                'like',
                '%' . $codigo . '%'
            );
        }

        // Filtro legacy (buscador antiguo)
        if (!empty($buscar)) {
            $query->where(
                'inventarios.' . $criterio,
                'like',
                '%' . $buscar . '%'
            );
        }

        // 4. OBTENER DATOS
        // Ordenado de menor a mayor stock
        $data = $query
            ->orderByRaw('SUM(inventarios.saldo_stock) ASC')
            ->orderBy('almacens.nombre_almacen', 'asc')
            ->orderBy('articulos.nombre', 'asc')
            ->get();

        // 5. AGRUPAR PARA EL PDF (Visualmente)
        $inventarios = $data->groupBy('nombre_almacen');

        // --- GENERACIÓN DEL PDF ---

        $pdf = new PDFBajoStockReporte('L', 'mm', 'A4');

        $pdf->AliasNbPages();

        $pdf->SetMargins(10, 10, 10);

        $pdf->SetAutoPageBreak(true, 20);

        $nombreAlmacenFiltro = 'Todos';

        if (!empty($almacen_id) && $almacen_id !== 'null') {
            $nombreAlmacenFiltro = \DB::table('almacens')
                ->where('id', $almacen_id)
                ->value('nombre_almacen') ?? $almacen_id;
        }

        $proveedorFiltro = !empty($request->proveedor) && $request->proveedor !== 'null'
            ? $request->proveedor
            : ((!empty($laboratorio) && $laboratorio !== 'null')
                ? $laboratorio
                : 'Todos');

        $busquedaFiltro = !empty($request->producto) && $request->producto !== 'null'
            ? $request->producto
            : ((!empty($medicamento) && $medicamento !== 'null')
                ? $medicamento
                : 'Ninguna');

        if (!empty($codigo) && $codigo !== 'null') {
            $busquedaFiltro = $busquedaFiltro === 'Ninguna'
                ? $codigo
                : $busquedaFiltro . ' / ' . $codigo;
        }

        $pdf->setFiltros(
            $nombreAlmacenFiltro,
            $proveedorFiltro,
            'Todas',
            $busquedaFiltro
        );

        $pdf->AddPage();

        if (false) {

            // LOGICA VISUAL DE FILTROS
            $filtrosTexto = [];

            if (!empty($almacen_id) && $almacen_id !== 'null') {

                $nombre = \DB::table('almacens')
                    ->where('id', $almacen_id)
                    ->value('nombre_almacen');

                $filtrosTexto[] = "Almacén: " . ($nombre ?? $almacen_id);

            } else {

                $filtrosTexto[] = "Almacén: Todos";
            }

            if (!empty($medicamento) && $medicamento !== 'null') {
                $filtrosTexto[] = "Med: " . $medicamento;
            }

            if (!empty($laboratorio) && $laboratorio !== 'null') {
                $filtrosTexto[] = "Lab: " . $laboratorio;
            }

            if (!empty($codigo) && $codigo !== 'null') {
                $filtrosTexto[] = "Cod: " . $codigo;
            }

            // Imprimir filtros debajo del título
            $pdf->SetFont('Arial', '', 9);

            $pdf->SetTextColor(100);

            $pdf->Cell(
                0,
                6,
                utf8_decode(
                    "Filtros: " . implode(" | ", $filtrosTexto)
                ),
                0,
                1,
                'C'
            );

            $pdf->Ln(2);

            // Total de registros encontrados
            $this->addReportInfo(
                $pdf,
                $data->count()
            );
        }

        foreach ($inventarios as $nombreAlmacen => $productos) {

            $this->addAlmacenHeader(
                $pdf,
                $nombreAlmacen
            );

            $this->addStyledTable(
                $pdf,
                $productos
            );
        }

        $this->addFooter($pdf);

        $fechaGeneracion = date('Y-m-d');

        $nombreArchivo = "Productos_bajo_stock_{$fechaGeneracion}.pdf";

        $pdf->Output(
            'D',
            $nombreArchivo
        );

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

        // Ancho útil total en A4 horizontal con márgenes 10/10 = 277 mm
        $widths = [28, 82, 62, 20, 20, 20, 45];
        $headers = ['Código', 'Producto', 'Stock Deposito', 'Unidad', 'Mínimo', 'Actual', 'Estado'];

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

            $nombreProveedor = $inv->nombre_proveedor;
            if (empty($nombreProveedor)) {
                $nombreProveedor = 'Sin proveedor';
            }

            $data = [
                utf8_decode($inv->codigo),
                utf8_decode($this->truncateText($inv->nombre_producto, 40)),
                utf8_decode($inv->stock_almacen_2),
                utf8_decode($inv->unidad_envase),
                utf8_decode($inv->stock_minimo),
                utf8_decode($inv->saldo_stock),
            ];

            $x = 10;
            foreach ($data as $i => $item) {
                $align = in_array($i, [3, 4, 5]) ? 'C' : 'L';
                $pdf->SetXY($x, $y);
                $pdf->Cell($widths[$i], 7, $item, 1, 0, $align, true);
                $x += $widths[$i];
            }

            // Agregar columna de Estado
            $estado = $inv->saldo_stock == 0 ? 'Sin Stock' : 'Bajo Stock';
            $pdf->SetXY($x, $y);
            $pdf->Cell($widths[6], 7, utf8_decode($estado), 1, 0, 'C', true);

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
        return;
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetY(-24);
        $y = $pdf->GetY();
        $pdf->Line(10, $y, 287, $y);

        $pdf->SetY(-20);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, utf8_decode('Los productos marcados requieren reposición urgente según stock mínimo establecido.'), 0, 1, 'L');

        $pdf->SetY(-16);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 4, utf8_decode('Sistema de Inventarios - Página ' . $pdf->PageNo() . ' - Generado: ' . date('d/m/Y H:i:s')), 0, 0, 'C');
    }

    private function truncateText($text, $maxLength)
    {
        return strlen($text) > $maxLength ? substr($text, 0, $maxLength - 2) . '..' : $text;
    }

    public function indexHistorico(Request $request, $tipo)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        if ($tipo !== 'item') {
            return response()->json([
                'message' => 'Tipo de consulta no permitido.'
            ], 422);
        }

        $request->validate([
            'idAlmacen' => 'required|integer|exists:almacens,id',
            'fecha_consulta' => 'required|date_format:Y-m-d',
            'buscar' => 'nullable|string|max:255',
            'orden' => 'nullable|in:nombre_asc,stock_asc,stock_desc'
        ]);

        $hoy = Carbon::now('America/La_Paz')->toDateString();

        if ($request->fecha_consulta > $hoy) {
            return response()->json([
                'message' => 'No se permite consultar fechas futuras.'
            ], 422);
        }

        try {

            $resultado = $this->consultaInventarioHistorico($request);

            $inventarios = $resultado['query']->paginate(20);

            return [
                'pagination' => [
                    'total' => $inventarios->total(),
                    'current_page' => $inventarios->currentPage(),
                    'per_page' => $inventarios->perPage(),
                    'last_page' => $inventarios->lastPage(),
                    'from' => $inventarios->firstItem(),
                    'to' => $inventarios->lastItem(),
                ],

                'inventarios' => $inventarios,

                'almacenes_columnas' =>
                    $resultado['almacenes_columnas'],

                'tipo_consulta' =>
                    $resultado['tipo_consulta'],

                'fecha_corte' =>
                    $resultado['fecha_corte'],

                'fecha_consulta' =>
                    $resultado['fecha_consulta'],

                'mensaje_historial' =>
                    $resultado['mensaje_historial']
            ];

        } catch (\DomainException $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 422);

        } catch (\Throwable $e) {

            \Log::error('Error al consultar inventario histórico', [
                'error' => $e->getMessage(),
                'idalmacen' => $request->idAlmacen,
                'fecha_consulta' => $request->fecha_consulta
            ]);

            return response()->json([
                'message' => 'No se pudo consultar el inventario histórico.'
            ], 500);
        }
    }

    private function obtenerAlmacenesColumnasHistorico($idAlmacen)
    {
        $almacenSeleccionado = DB::table('almacens')
            ->join(
                'sucursales',
                'almacens.sucursal',
                '=',
                'sucursales.id'
            )
            ->where('almacens.id', $idAlmacen)
            ->select(
                'almacens.id',
                'almacens.nombre_almacen',
                'sucursales.departamento'
            )
            ->first();

        if (!$almacenSeleccionado) {
            return collect();
        }

        return DB::table('almacens')
            ->join(
                'sucursales',
                'almacens.sucursal',
                '=',
                'sucursales.id'
            )
            ->where(
                'sucursales.departamento',
                $almacenSeleccionado->departamento
            )
            ->where('almacens.id', '<>', (int) $idAlmacen)
            ->where('almacens.id', '<>', 2)
            ->select(
                'almacens.id',
                'almacens.nombre_almacen'
            )
            ->orderBy('almacens.nombre_almacen', 'asc')
            ->get();
    }

    private function consultaStockActual(array $idsAlmacenes)
    {
        return DB::table('inventarios')
            ->whereIn('idalmacen', $idsAlmacenes)
            ->select(
                'idarticulo',
                'idalmacen',
                DB::raw('SUM(saldo_stock) AS stock')
            )
            ->groupBy(
                'idarticulo',
                'idalmacen'
            );
    }

    private function consultaStockHistoricoRegistrado(
        array $idsAlmacenes,
        $fechaConsulta,
        $fechaCorte
    ) {
        $fechaLimite = Carbon::parse(
            $fechaConsulta,
            'America/La_Paz'
        )
            ->addDay()
            ->startOfDay()
            ->format('Y-m-d H:i:s');

        return DB::table('movimientos_inventario')
            ->whereIn('idalmacen', $idsAlmacenes)
            ->where('fecha_movimiento', '>=', $fechaCorte)
            ->where('fecha_movimiento', '<', $fechaLimite)
            ->select(
                'idarticulo',
                'idalmacen',
                DB::raw('SUM(cantidad) AS stock')
            )
            ->groupBy(
                'idarticulo',
                'idalmacen'
            );
    }

    private function consultaInventarioHistorico(Request $request)
    {
        $idAlmacen = (int) $request->idAlmacen;
        $buscar = trim($request->get('buscar', ''));
        $orden = $request->get('orden', 'stock_asc');
        $fechaConsulta = $request->fecha_consulta;

        $fechaActual = Carbon::now(
            'America/La_Paz'
        )->toDateString();

        /*
        |--------------------------------------------------------------------------
        | 1. Obtener configuración del historial
        |--------------------------------------------------------------------------
        */

        $configuracion = ConfiguracionHistorialInventario::find(1);

        if (
            !$configuracion ||
            !$configuracion->inicializado ||
            !$configuracion->fecha_corte
        ) {
            throw new \DomainException(
                'El historial de inventario todavía no ha sido inicializado.'
            );
        }

        $fechaCorte = $configuracion->fecha_corte
            ->format('Y-m-d H:i:s');

        $diaCorte = $configuracion->fecha_corte
            ->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | 2. Obtener almacenes
        |--------------------------------------------------------------------------
        */

        $almacenesColumnas =
            $this->obtenerAlmacenesColumnasHistorico($idAlmacen);

        $idsAlmacenes = array_values(array_unique(array_merge(
            [$idAlmacen, 2],
            $almacenesColumnas->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray()
        )));

        /*
        |--------------------------------------------------------------------------
        | 3. Determinar origen del stock
        |--------------------------------------------------------------------------
        */

        if ($fechaConsulta === $fechaActual) {

            $consultaStock = $this->consultaStockActual(
                $idsAlmacenes
            );

            $tipoConsulta = 'ACTUAL';

            $mensajeHistorial =
                'Existencias actuales del inventario.';

        } elseif ($fechaConsulta >= $diaCorte) {

            $consultaStock =
                $this->consultaStockHistoricoRegistrado(
                    $idsAlmacenes,
                    $fechaConsulta,
                    $fechaCorte
                );

            $tipoConsulta = 'HISTORICO_REGISTRADO';

            $mensajeHistorial =
                'Existencias obtenidas del historial de movimientos '
                . 'al cierre de la fecha seleccionada.';

        } else {

            $consultaStock = $this->consultaStockHistoricoEstimado(
                $idsAlmacenes,
                $fechaConsulta,
                $fechaCorte
            );

            $tipoConsulta = 'HISTORICO_ESTIMADO';

            $mensajeHistorial =
                'Existencias estimadas mediante la reconstrucción '
                . 'de compras, ventas, ajustes, traspasos y anulaciones '
                . 'anteriores al inicio del historial registrado. '
                . 'Las cantidades pueden presentar diferencias debido '
                . 'a modificaciones o inconsistencias de registros antiguos.';
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Agrupar stock de todos los almacenes
        |--------------------------------------------------------------------------
        */

        $stockAgrupado = DB::table(
            DB::raw('(' . $consultaStock->toSql() . ') AS stock_base')
        )
            ->mergeBindings($consultaStock)
            ->select('stock_base.idarticulo')
            ->selectRaw(
                'SUM(CASE WHEN stock_base.idalmacen = ? '
                . 'THEN stock_base.stock ELSE 0 END) AS saldo_stock_total',
                [$idAlmacen]
            )
            ->selectRaw(
                'SUM(CASE WHEN stock_base.idalmacen = 2 '
                . 'THEN stock_base.stock ELSE 0 END) AS stock_deposito'
            )
            ->groupBy('stock_base.idarticulo');

        /*
        |--------------------------------------------------------------------------
        | 5. Agregar columnas dinámicas
        |--------------------------------------------------------------------------
        */

        foreach ($almacenesColumnas as $almacenColumna) {

            $idAlmacenColumna = (int) $almacenColumna->id;

            $stockAgrupado->selectRaw(
                'SUM(CASE WHEN stock_base.idalmacen = ? '
                . 'THEN stock_base.stock ELSE 0 END) '
                . 'AS stock_almacen_' . $idAlmacenColumna,
                [$idAlmacenColumna]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Consulta principal de productos
        |--------------------------------------------------------------------------
        */

        $inventarios = Articulo::query()
            ->leftJoin(
                DB::raw(
                    '(' . $stockAgrupado->toSql() . ') AS stocks'
                ),
                'articulos.id',
                '=',
                'stocks.idarticulo'
            )
            ->addBinding(
                $stockAgrupado->getBindings(),
                'join'
            )
            ->leftJoin(
                'proveedores',
                'articulos.idproveedor',
                '=',
                'proveedores.id'
            )
            ->leftJoin(
                'categorias',
                'articulos.idcategoria',
                '=',
                'categorias.id'
            )
            ->select(
                'articulos.id',
                'articulos.codigo',
                'articulos.nombre AS nombre_producto',
                'articulos.descripcion_fabrica',
                'articulos.unidad_envase',
                'proveedores.contacto AS nombre_proveedor',
                'categorias.nombre AS nombre_categoria',

                DB::raw(
                    'COALESCE(stocks.saldo_stock_total, 0) '
                    . 'AS saldo_stock_total'
                ),

                DB::raw(
                    'COALESCE(stocks.stock_deposito, 0) '
                    . 'AS stock_deposito'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | 7. Columnas dinámicas de stock
        |--------------------------------------------------------------------------
        */

        foreach ($almacenesColumnas as $almacenColumna) {

            $idAlmacenColumna = (int) $almacenColumna->id;

            $inventarios->addSelect(DB::raw(
                'COALESCE(stocks.stock_almacen_'
                . $idAlmacenColumna
                . ', 0) AS stock_almacen_'
                . $idAlmacenColumna
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Filtrar productos
        |--------------------------------------------------------------------------
        */

        if ($tipoConsulta === 'ACTUAL') {

            $inventarios->where('articulos.condicion', 1);

        } else {

            // Incluir productos activos existentes en la fecha
            // y productos inactivos con registros históricos.
            $inventarios->where(function ($query) use ($fechaConsulta) {

                $query->whereNotNull('stocks.idarticulo')
                    ->orWhere(function ($sub) use ($fechaConsulta) {

                        $sub->where('articulos.condicion', 1)
                            ->whereDate(
                                'articulos.created_at',
                                '<=',
                                $fechaConsulta
                            );
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Búsqueda general
        |--------------------------------------------------------------------------
        */

        if ($buscar !== '') {

            $palabras = preg_split('/\s+/', $buscar);

            foreach ($palabras as $palabra) {

                $inventarios->where(function ($query) use ($palabra) {

                    $query
                        ->where(
                            'articulos.nombre',
                            'like',
                            '%' . $palabra . '%'
                        )
                        ->orWhere(
                            'articulos.codigo',
                            'like',
                            '%' . $palabra . '%'
                        )
                        ->orWhere(
                            'proveedores.contacto',
                            'like',
                            '%' . $palabra . '%'
                        )
                        ->orWhere(
                            'categorias.nombre',
                            'like',
                            '%' . $palabra . '%'
                        );
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Ordenamiento
        |--------------------------------------------------------------------------
        */

        switch ($orden) {

            case 'nombre_asc':

                $inventarios
                    ->orderBy('articulos.nombre', 'asc')
                    ->orderBy('categorias.nombre', 'asc');

                break;

            case 'stock_desc':

                $inventarios
                    ->orderBy('saldo_stock_total', 'desc')
                    ->orderBy('articulos.nombre', 'asc');

                break;

            case 'stock_asc':
            default:

                $inventarios
                    ->orderBy('saldo_stock_total', 'asc')
                    ->orderBy('articulos.nombre', 'asc');

                break;
        }

        // Orden estable para la paginación
        $inventarios->orderBy('articulos.id', 'asc');

        /*
        |--------------------------------------------------------------------------
        | 11. Retornar consulta
        |--------------------------------------------------------------------------
        */

        return [
            'query' => $inventarios,

            'almacenes_columnas' => $almacenesColumnas,

            'tipo_consulta' => $tipoConsulta,

            'fecha_corte' => $fechaCorte,

            'fecha_consulta' => $fechaConsulta,

            'mensaje_historial' => $mensajeHistorial
        ];
    }

    private function consultaMovimientosHistoricosCompras(
        array $idsAlmacenes,
        $fechaDesde,
        $fechaCorte
    ) {
        // Revertir compras registradas
        $compras = DB::table('ingresos AS i')
            ->join(
                'detalle_ingresos AS di',
                'di.idingreso',
                '=',
                'i.id'
            )
            ->whereIn('i.idalmacen', $idsAlmacenes)
            ->whereIn('i.estado', [0, 1])
            ->where('i.fecha_hora', '>=', $fechaDesde)
            ->where('i.fecha_hora', '<', $fechaCorte)
            ->select(
                'di.idarticulo',
                'i.idalmacen',
                DB::raw('-di.cantidad AS cantidad')
            );

        // Revertir anulaciones de compras
        $anulaciones = DB::table('ingresos AS i')
            ->join(
                'detalle_ingresos AS di',
                'di.idingreso',
                '=',
                'i.id'
            )
            ->whereIn('i.idalmacen', $idsAlmacenes)
            ->where('i.estado', 0)
            ->where('i.fecha_hora', '<', $fechaCorte)
            ->where('i.updated_at', '>=', $fechaDesde)
            ->where('i.updated_at', '<', $fechaCorte)
            ->select(
                'di.idarticulo',
                'i.idalmacen',
                DB::raw('di.cantidad AS cantidad')
            );

        return [
            $compras,
            $anulaciones
        ];
    }

    private function consultaMovimientosHistoricosVentas(
        array $idsAlmacenes,
        $fechaDesde,
        $fechaCorte
    ) {
        // Revertir ventas registradas
        $ventas = DB::table('ventas AS v')
            ->join(
                'detalle_ventas AS dv',
                'dv.idventa',
                '=',
                'v.id'
            )
            ->whereIn('v.idalmacen', $idsAlmacenes)
            ->whereIn('v.estado', ['0', '1', '2'])
            ->where('v.fecha_hora', '>=', $fechaDesde)
            ->where('v.fecha_hora', '<', $fechaCorte)
            ->select(
                'dv.idarticulo',
                'v.idalmacen',
                DB::raw('dv.cantidad AS cantidad')
            );

        // Revertir anulaciones de ventas
        $anulaciones = DB::table('ventas AS v')
            ->join(
                'detalle_ventas AS dv',
                'dv.idventa',
                '=',
                'v.id'
            )
            ->whereIn('v.idalmacen', $idsAlmacenes)
            ->where('v.estado', '0')
            ->where('v.fecha_hora', '<', $fechaCorte)
            ->where('v.updated_at', '>=', $fechaDesde)
            ->where('v.updated_at', '<', $fechaCorte)
            ->select(
                'dv.idarticulo',
                'v.idalmacen',
                DB::raw('-dv.cantidad AS cantidad')
            );

        return [
            $ventas,
            $anulaciones
        ];
    }

    private function consultaMovimientosHistoricosAjustes(
        array $idsAlmacenes,
        $fechaDesde,
        $fechaCorte
    ) {
        $ajustes = DB::table('ajuste_invetarios AS ai')
            ->whereIn('ai.almacen', $idsAlmacenes)
            ->where('ai.created_at', '>=', $fechaDesde)
            ->where('ai.created_at', '<', $fechaCorte)
            ->whereIn('ai.tipo_movimiento', [
                'entrada',
                'salida'
            ])
            ->select(
                'ai.producto AS idarticulo',
                DB::raw('CAST(ai.almacen AS UNSIGNED) AS idalmacen'),
                DB::raw("
                CASE
                    WHEN ai.tipo_movimiento = 'entrada'
                        THEN -ai.cantidad

                    WHEN ai.tipo_movimiento = 'salida'
                        THEN ai.cantidad

                    ELSE 0
                END AS cantidad
            ")
            );

        return [
            $ajustes
        ];
    }

    private function consultaMovimientosHistoricosTraspasos(
        array $idsAlmacenes,
        $fechaDesde,
        $fechaCorte
    ) {
        // Estados considerados anulados
        $estadosAnulados = [0, 9];

        /*
        |--------------------------------------------------------------------------
        | 1. Consulta base
        |--------------------------------------------------------------------------
        */

        $base = DB::table('traspasos AS t')
            ->join(
                'detalle_traspasos AS dt',
                'dt.idtraspaso',
                '=',
                't.id'
            )
            ->join(
                'inventarios AS inv',
                'dt.idinventario',
                '=',
                'inv.id'
            );

        /*
        |--------------------------------------------------------------------------
        | 2. Revertir salida del almacén origen
        |--------------------------------------------------------------------------
        */

        $salidasOrigen = (clone $base)
            ->whereIn('t.estado', [0, 1, 9])
            ->whereIn('t.almacen_origen', $idsAlmacenes)
            ->where('t.fecha_traspaso', '>=', $fechaDesde)
            ->where('t.fecha_traspaso', '<', $fechaCorte)
            ->select(
                'inv.idarticulo',
                DB::raw(
                    'CAST(t.almacen_origen AS UNSIGNED) AS idalmacen'
                ),
                DB::raw('dt.cantidad_traspaso AS cantidad')
            );

        /*
        |--------------------------------------------------------------------------
        | 3. Revertir entrada al almacén destino
        |--------------------------------------------------------------------------
        */

        $entradasDestino = (clone $base)
            ->whereIn('t.estado', [0, 1, 9])
            ->whereIn('t.almacen_destino', $idsAlmacenes)
            ->where('t.fecha_traspaso', '>=', $fechaDesde)
            ->where('t.fecha_traspaso', '<', $fechaCorte)
            ->select(
                'inv.idarticulo',
                DB::raw(
                    'CAST(t.almacen_destino AS UNSIGNED) AS idalmacen'
                ),
                DB::raw('-dt.cantidad_traspaso AS cantidad')
            );

        /*
        |--------------------------------------------------------------------------
        | 4. Revertir anulación en almacén origen
        |--------------------------------------------------------------------------
        */

        $anulacionesOrigen = (clone $base)
            ->whereIn('t.estado', $estadosAnulados)
            ->whereIn('t.almacen_origen', $idsAlmacenes)
            ->where('t.fecha_traspaso', '<', $fechaCorte)
            ->where('t.updated_at', '>=', $fechaDesde)
            ->where('t.updated_at', '<', $fechaCorte)
            ->select(
                'inv.idarticulo',
                DB::raw(
                    'CAST(t.almacen_origen AS UNSIGNED) AS idalmacen'
                ),
                DB::raw('-dt.cantidad_traspaso AS cantidad')
            );

        /*
        |--------------------------------------------------------------------------
        | 5. Revertir anulación en almacén destino
        |--------------------------------------------------------------------------
        */

        $anulacionesDestino = (clone $base)
            ->whereIn('t.estado', $estadosAnulados)
            ->whereIn('t.almacen_destino', $idsAlmacenes)
            ->where('t.fecha_traspaso', '<', $fechaCorte)
            ->where('t.updated_at', '>=', $fechaDesde)
            ->where('t.updated_at', '<', $fechaCorte)
            ->select(
                'inv.idarticulo',
                DB::raw(
                    'CAST(t.almacen_destino AS UNSIGNED) AS idalmacen'
                ),
                DB::raw('dt.cantidad_traspaso AS cantidad')
            );

        return [
            $salidasOrigen,
            $entradasDestino,
            $anulacionesOrigen,
            $anulacionesDestino
        ];
    }

    private function consultaStockHistoricoEstimado(
        array $idsAlmacenes,
        $fechaConsulta,
        $fechaCorte
    ) {
        /*
        |--------------------------------------------------------------------------
        | 1. Determinar fecha inicial de reconstrucción
        |--------------------------------------------------------------------------
        */

        $fechaDesde = Carbon::parse(
            $fechaConsulta,
            'America/La_Paz'
        )
            ->addDay()
            ->startOfDay()
            ->format('Y-m-d H:i:s');

        /*
        |--------------------------------------------------------------------------
        | 2. Obtener stock inicial registrado
        |--------------------------------------------------------------------------
        */

        $stockInicial = DB::table('movimientos_inventario AS mi')
            ->whereIn('mi.idalmacen', $idsAlmacenes)
            ->where('mi.tipo_movimiento', 'STOCK_INICIAL')
            ->where('mi.fecha_movimiento', $fechaCorte)
            ->select(
                'mi.idarticulo',
                'mi.idalmacen',
                'mi.cantidad'
            );

        /*
        |--------------------------------------------------------------------------
        | 3. Obtener movimientos históricos de compras
        |--------------------------------------------------------------------------
        */

        $compras = $this->consultaMovimientosHistoricosCompras(
            $idsAlmacenes,
            $fechaDesde,
            $fechaCorte
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Obtener movimientos históricos de ventas
        |--------------------------------------------------------------------------
        */

        $ventas = $this->consultaMovimientosHistoricosVentas(
            $idsAlmacenes,
            $fechaDesde,
            $fechaCorte
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Obtener ajustes históricos
        |--------------------------------------------------------------------------
        */

        $ajustes = $this->consultaMovimientosHistoricosAjustes(
            $idsAlmacenes,
            $fechaDesde,
            $fechaCorte
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Obtener traspasos históricos
        |--------------------------------------------------------------------------
        */

        $traspasos = $this->consultaMovimientosHistoricosTraspasos(
            $idsAlmacenes,
            $fechaDesde,
            $fechaCorte
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Unificar todas las consultas
        |--------------------------------------------------------------------------
        */

        $consultas = array_merge(
            $compras,
            $ventas,
            $ajustes,
            $traspasos
        );

        $movimientos = $stockInicial;

        foreach ($consultas as $consulta) {
            $movimientos->unionAll($consulta);
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Calcular stock histórico por artículo y almacén
        |--------------------------------------------------------------------------
        */

        return DB::table(
            DB::raw(
                '(' . $movimientos->toSql() . ') AS movimientos_historicos'
            )
        )
            ->mergeBindings($movimientos)
            ->select(
                'movimientos_historicos.idarticulo',
                'movimientos_historicos.idalmacen',
                DB::raw(
                    'SUM(movimientos_historicos.cantidad) AS stock'
                )
            )
            ->groupBy(
                'movimientos_historicos.idarticulo',
                'movimientos_historicos.idalmacen'
            );
    }

    public function exportarInventarioHistoricoPdf(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validación y permisos
        |--------------------------------------------------------------------------
        */

        if (!auth()->check()) {
            abort(401, 'Usuario no autenticado.');
        }

        if ((int) auth()->user()->idrol === 2) {
            abort(403, 'No tiene permisos para exportar.');
        }

        $request->validate([
            'modo' => 'nullable|in:item',
            'idAlmacen' => 'required|integer|exists:almacens,id',
            'fecha_consulta' => 'required|date_format:Y-m-d',
            'buscar' => 'nullable|string|max:255',
            'orden' => 'nullable|in:nombre_asc,stock_asc,stock_desc'
        ]);

        $idAlmacen = (int) $request->input('idAlmacen');
        $buscar = $request->input('buscar', '');
        $orden = $request->input('orden', 'stock_asc');
        $fechaConsulta = $request->input('fecha_consulta');

        $fechaActual = Carbon::now('America/La_Paz')
            ->toDateString();

        if ($fechaConsulta > $fechaActual) {
            abort(422, 'No se permite consultar fechas futuras.');
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | 2. Obtener almacén seleccionado
            |--------------------------------------------------------------------------
            */

            $almacen = DB::table('almacens')
                ->leftJoin(
                    'sucursales',
                    'almacens.sucursal',
                    '=',
                    'sucursales.id'
                )
                ->where('almacens.id', $idAlmacen)
                ->select(
                    'almacens.*',
                    'sucursales.departamento'
                )
                ->first();

            if (!$almacen) {
                throw new \DomainException(
                    'Debe seleccionar un almacén válido.'
                );
            }

            $nombreAlmacen = $almacen->nombre_almacen;

            /*
            |--------------------------------------------------------------------------
            | 3. Reutilizar la consulta histórica del listado
            |--------------------------------------------------------------------------
            */

            $resultado = $this->consultaInventarioHistorico(
                $request
            );

            $inventarios = $resultado['query']->get();

            $almacenesColumnas =
                $resultado['almacenes_columnas'];

            $tipoConsulta = $resultado['tipo_consulta'];

            $mensajeHistorial =
                $resultado['mensaje_historial'];

            $fechaConsultaFormateada = Carbon::parse(
                $resultado['fecha_consulta'],
                'America/La_Paz'
            )->format('d/m/Y');

            $fechaCorteFormateada = Carbon::parse(
                $resultado['fecha_corte'],
                'America/La_Paz'
            )->format('d/m/Y H:i:s');

            $fechaGeneracion = Carbon::now(
                'America/La_Paz'
            )->format('d/m/Y H:i:s');

            /*
            |--------------------------------------------------------------------------
            | 4. Configuración según tipo de consulta
            |--------------------------------------------------------------------------
            */

            $configuraciones = [

                'ACTUAL' => [
                    'titulo' => 'INVENTARIO ACTUAL',
                    'titulo_stock' => 'Stock Actual',
                    'fondo' => [239, 246, 255],
                    'texto' => [30, 64, 175]
                ],

                'HISTORICO_REGISTRADO' => [
                    'titulo' => 'HISTÓRICO REGISTRADO',
                    'titulo_stock' => 'Stock Histórico',
                    'fondo' => [236, 253, 245],
                    'texto' => [22, 101, 52]
                ],

                'HISTORICO_ESTIMADO' => [
                    'titulo' => 'HISTÓRICO ESTIMADO',
                    'titulo_stock' => 'Stock Estimado',
                    'fondo' => [255, 247, 237],
                    'texto' => [154, 52, 18]
                ]
            ];

            $configConsulta = $configuraciones[$tipoConsulta]
                ?? $configuraciones['ACTUAL'];

            /*
            |--------------------------------------------------------------------------
            | 5. Configuración del PDF
            |--------------------------------------------------------------------------
            */

            $pdf = new PDFInventario('L', 'mm', 'A4');

            $pdf->AliasNbPages();
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 20);
            $pdf->AddPage();

            $anchoTotal = 277;

            /*
            |--------------------------------------------------------------------------
            | 6. Funciones auxiliares
            |--------------------------------------------------------------------------
            */

            $ajustarTexto = function ($texto, $ancho) use ($pdf) {

                $texto = utf8_decode((string) $texto);

                if (
                    $pdf->GetStringWidth($texto)
                    <= $ancho - 3
                ) {
                    return $texto;
                }

                while (
                    strlen($texto) > 0 &&
                    $pdf->GetStringWidth($texto . '...')
                    > $ancho - 3
                ) {
                    $texto = substr($texto, 0, -1);
                }

                return $texto . '...';
            };

            $formatearStock = function ($stock, $unidad = '') {

                if ($stock === null || !is_numeric($stock)) {
                    return 'Sin datos';
                }

                $stock = (float) $stock;

                if ($stock == 0) {
                    return 'Sin Stock';
                }

                $cantidad = rtrim(
                    rtrim(
                        number_format($stock, 4, '.', ''),
                        '0'
                    ),
                    '.'
                );

                return trim($cantidad . ' ' . $unidad);
            };

            /*
            |--------------------------------------------------------------------------
            | 7. Encabezado principal
            |--------------------------------------------------------------------------
            */

            $rutaLogo = public_path('img/logoPrincipal.png');

            if (file_exists($rutaLogo)) {
                $pdf->Image($rutaLogo, 10, 5, 20);
            }

            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetTextColor(44, 62, 80);

            $pdf->Cell(
                0,
                10,
                utf8_decode('REPORTE DE INVENTARIO HISTÓRICO'),
                0,
                1,
                'C'
            );

            $pdf->SetFont('Arial', 'B', 11);

            $pdf->Cell(
                0,
                7,
                utf8_decode(
                    'Inventario al: ' . $fechaConsultaFormateada
                ),
                0,
                1,
                'C'
            );

            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(100, 116, 139);

            $pdf->Cell(
                0,
                6,
                utf8_decode(
                    'Fecha de generación: ' . $fechaGeneracion
                ),
                0,
                1,
                'C'
            );

            $pdf->Ln(4);

            /*
            |--------------------------------------------------------------------------
            | 8. Información de filtros
            |--------------------------------------------------------------------------
            */

            $ordenes = [
                'nombre_asc' => 'Nombre de A - Z',
                'stock_asc' => 'Menor a mayor stock',
                'stock_desc' => 'Mayor a menor stock'
            ];

            $nombreOrden = $ordenes[$orden]
                ?? $ordenes['stock_asc'];

            $pdf->SetFillColor(236, 240, 241);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Rect(
                10,
                $pdf->GetY(),
                $anchoTotal,
                30,
                'F'
            );

            $informacionFiltros = [
                'Almacén:' => $nombreAlmacen,

                'Departamento:' =>
                    $almacen->departamento ?? '-',

                'Fecha consultada:' =>
                    $fechaConsultaFormateada,

                'Búsqueda:' =>
                    !empty($buscar) ? $buscar : 'Ninguna',

                'Orden:' => $nombreOrden
            ];

            foreach ($informacionFiltros as $titulo => $valor) {

                $pdf->SetX(12);
                $pdf->SetFont('Arial', 'B', 9);

                $pdf->Cell(
                    38,
                    6,
                    utf8_decode($titulo),
                    0,
                    0,
                    'L'
                );

                $pdf->SetFont('Arial', '', 9);

                $pdf->Cell(
                    235,
                    6,
                    $ajustarTexto($valor, 235),
                    0,
                    1,
                    'L'
                );
            }

            $pdf->Ln(4);

            /*
            |--------------------------------------------------------------------------
            | 9. Indicador del tipo de consulta histórica
            |--------------------------------------------------------------------------
            */

            $pdf->SetFillColor(
                $configConsulta['fondo'][0],
                $configConsulta['fondo'][1],
                $configConsulta['fondo'][2]
            );

            $pdf->SetTextColor(
                $configConsulta['texto'][0],
                $configConsulta['texto'][1],
                $configConsulta['texto'][2]
            );

            $pdf->SetFont('Arial', 'B', 10);

            $pdf->Cell(
                $anchoTotal,
                8,
                utf8_decode($configConsulta['titulo']),
                0,
                1,
                'L',
                true
            );

            $pdf->SetFont('Arial', '', 9);

            $pdf->MultiCell(
                $anchoTotal,
                5,
                utf8_decode($mensajeHistorial),
                0,
                'L',
                true
            );

            $pdf->Cell(
                $anchoTotal,
                6,
                utf8_decode(
                    'Inicio del historial registrado: '
                    . $fechaCorteFormateada
                ),
                0,
                1,
                'L',
                true
            );

            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(4);

            /*
            |--------------------------------------------------------------------------
            | 10. Definir columnas del reporte
            |--------------------------------------------------------------------------
            */

            $columnas = [];

            $cantidadColumnasStock = 1;

            if ($idAlmacen !== 2) {
                $cantidadColumnasStock++;
            }

            $cantidadColumnasStock +=
                $almacenesColumnas->count();

            $anchoCodigo = 25;
            $anchoProducto = 82;

            if ($cantidadColumnasStock >= 6) {
                $anchoCodigo = 22;
                $anchoProducto = 65;
            }

            $anchoStock = (
                $anchoTotal
                - $anchoCodigo
                - $anchoProducto
            ) / $cantidadColumnasStock;

            // Código
            $columnas[] = [
                'campo' => 'codigo',
                'titulo' => 'Código',
                'ancho' => $anchoCodigo,
                'alineacion' => 'L',
                'tipo' => 'texto'
            ];

            // Producto
            $columnas[] = [
                'campo' => 'nombre_producto',
                'titulo' => 'Producto',
                'ancho' => $anchoProducto,
                'alineacion' => 'L',
                'tipo' => 'texto'
            ];

            // Stock del almacén seleccionado
            $columnas[] = [
                'campo' => 'saldo_stock_total',
                'titulo' => $configConsulta['titulo_stock'],
                'ancho' => $anchoStock,
                'alineacion' => 'C',
                'tipo' => 'stock'
            ];

            // Stock del depósito
            if ($idAlmacen !== 2) {

                $columnas[] = [
                    'campo' => 'stock_deposito',
                    'titulo' => 'Stock Depósito',
                    'ancho' => $anchoStock,
                    'alineacion' => 'C',
                    'tipo' => 'stock'
                ];
            }

            // Columnas dinámicas por departamento
            foreach ($almacenesColumnas as $almacenColumna) {

                $columnas[] = [
                    'campo' =>
                        'stock_almacen_' . $almacenColumna->id,

                    'titulo' =>
                        $almacenColumna->nombre_almacen,

                    'ancho' => $anchoStock,
                    'alineacion' => 'C',
                    'tipo' => 'stock'
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 11. Encabezados de tabla
            |--------------------------------------------------------------------------
            */

            $imprimirCabecera = function () use ($pdf, $columnas, $ajustarTexto) {

                $pdf->SetX(10);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetDrawColor(200, 205, 210);

                foreach ($columnas as $indice => $columna) {

                    $ultima =
                        $indice === count($columnas) - 1;

                    $pdf->Cell(
                        $columna['ancho'],
                        9,
                        $ajustarTexto(
                            $columna['titulo'],
                            $columna['ancho']
                        ),
                        1,
                        $ultima ? 1 : 0,
                        'C',
                        true
                    );
                }

                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 8);
            };

            /*
            |--------------------------------------------------------------------------
            | 12. Encabezado para páginas siguientes
            |--------------------------------------------------------------------------
            */

            $imprimirEncabezadoContinuacion = function () use ($pdf, $fechaConsultaFormateada, $nombreAlmacen, $configConsulta, $ajustarTexto, $anchoTotal) {

                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetTextColor(44, 62, 80);

                $pdf->Cell(
                    0,
                    7,
                    utf8_decode(
                        'REPORTE DE INVENTARIO HISTÓRICO'
                    ),
                    0,
                    1,
                    'C'
                );

                $pdf->SetFont('Arial', '', 9);

                $texto = $nombreAlmacen
                    . ' | Fecha: '
                    . $fechaConsultaFormateada
                    . ' | '
                    . $configConsulta['titulo'];

                $pdf->Cell(
                    0,
                    6,
                    $ajustarTexto($texto, $anchoTotal),
                    0,
                    1,
                    'C'
                );

                $pdf->Ln(3);
            };

            if (
                $pdf->GetY() + 19 >
                $pdf->GetPageHeight() - 20
            ) {
                $pdf->AddPage();
                $imprimirEncabezadoContinuacion();
            }

            $imprimirCabecera();

            /*
            |--------------------------------------------------------------------------
            | 13. Imprimir registros
            |--------------------------------------------------------------------------
            */

            $fill = false;
            $altoFila = 7;

            foreach ($inventarios as $inv) {

                // Controlar salto de página
                if (
                    $pdf->GetY() + $altoFila >
                    $pdf->GetPageHeight() - 20
                ) {
                    $pdf->AddPage();

                    $imprimirEncabezadoContinuacion();

                    $imprimirCabecera();
                }

                $pdf->SetX(10);

                // Alternar fondo de filas
                if ($fill) {
                    $pdf->SetFillColor(245, 247, 249);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }

                foreach ($columnas as $indice => $columna) {

                    $campo = $columna['campo'];
                    $valor = $inv->{$campo} ?? null;

                    $ultima =
                        $indice === count($columnas) - 1;

                    $pdf->SetFont('Arial', '', 8);
                    $pdf->SetTextColor(0, 0, 0);

                    /*
                    |--------------------------------------------------------------------------
                    | Formato de stock
                    |--------------------------------------------------------------------------
                    */

                    if ($columna['tipo'] === 'stock') {

                        $stock = is_numeric($valor)
                            ? (float) $valor
                            : null;

                        $unidad =
                            $inv->descripcion_fabrica ?? '';

                        $valor = $formatearStock(
                            $stock,
                            $unidad
                        );

                        if ($stock === null) {

                            // Stock desconocido
                            $pdf->SetTextColor(120, 113, 108);

                        } elseif ($stock == 0) {

                            // Sin stock
                            $pdf->SetTextColor(220, 38, 38);
                            $pdf->SetFont('Arial', 'B', 8);

                        } elseif ($stock < 0) {

                            // Stock negativo
                            $pdf->SetTextColor(185, 28, 28);
                            $pdf->SetFont('Arial', 'B', 8);

                        } elseif (
                            $campo === 'saldo_stock_total'
                        ) {

                            // Resaltar almacén seleccionado
                            $pdf->SetFont('Arial', 'B', 8);
                        }

                    } else {

                        $valor = (
                            $valor === null ||
                            $valor === ''
                        ) ? '-' : $valor;
                    }

                    $pdf->Cell(
                        $columna['ancho'],
                        $altoFila,
                        $ajustarTexto(
                            $valor,
                            $columna['ancho']
                        ),
                        1,
                        $ultima ? 1 : 0,
                        $columna['alineacion'],
                        true
                    );
                }

                $fill = !$fill;
            }

            /*
            |--------------------------------------------------------------------------
            | 14. Total de registros
            |--------------------------------------------------------------------------
            */

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', 'B', 9);

            if ($inventarios->isEmpty()) {

                $pdf->Cell(
                    $anchoTotal,
                    10,
                    utf8_decode(
                        'No se encontraron registros.'
                    ),
                    1,
                    1,
                    'C'
                );
            }

            if (
                $pdf->GetY() + 30 >
                $pdf->GetPageHeight() - 20
            ) {
                $pdf->AddPage();
                $imprimirEncabezadoContinuacion();
            }

            $pdf->Ln(4);

            $pdf->Cell(
                0,
                7,
                utf8_decode(
                    'Total de productos: '
                    . $inventarios->count()
                ),
                0,
                1,
                'L'
            );

            /*
            |--------------------------------------------------------------------------
            | 15. Nota aclaratoria
            |--------------------------------------------------------------------------
            */

            if ($tipoConsulta === 'HISTORICO_ESTIMADO') {

                $pdf->Ln(2);

                $pdf->SetFont('Arial', 'I', 8);
                $pdf->SetTextColor(154, 52, 18);

                $pdf->MultiCell(
                    $anchoTotal,
                    5,
                    utf8_decode(
                        'Nota: Las existencias anteriores al inicio '
                        . 'del historial registrado son estimadas. '
                        . 'Pueden presentar diferencias debido a '
                        . 'modificaciones, anulaciones o inconsistencias '
                        . 'en los registros antiguos. Los valores negativos '
                        . 'deben ser revisados.'
                    ),
                    0,
                    'L'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 16. Generar nombre del archivo
            |--------------------------------------------------------------------------
            */

            $fechaArchivo = Carbon::parse(
                $resultado['fecha_consulta']
            )->format('d_m_Y');

            $nombreArchivoLimpio = preg_replace(
                '/[^a-zA-Z0-9_-]/',
                '_',
                $nombreAlmacen
            );

            $nombreArchivoLimpio = preg_replace(
                '/_+/',
                '_',
                $nombreArchivoLimpio
            );

            $filename = 'InventarioHistorico_'
                . $nombreArchivoLimpio
                . '_'
                . $fechaArchivo
                . '.pdf';

            /*
            |--------------------------------------------------------------------------
            | 17. Retornar archivo PDF
            |--------------------------------------------------------------------------
            */

            return response($pdf->Output('S'), 200)
                ->header(
                    'Content-Type',
                    'application/pdf'
                )
                ->header(
                    'Content-Disposition',
                    'attachment; filename="' . $filename . '"'
                );

        } catch (\DomainException $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 422);

        } catch (\Throwable $e) {

            \Log::error(
                'Error al exportar inventario histórico PDF',
                [
                    'error' => $e->getMessage(),
                    'idalmacen' => $idAlmacen,
                    'fecha_consulta' => $fechaConsulta,
                    'idusuario' => auth()->id()
                ]
            );

            return response()->json([
                'message' =>
                    'No se pudo generar el reporte histórico PDF.'
            ], 500);
        }
    }

    public function exportarInventarioHistoricoExcel(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validación y permisos
        |--------------------------------------------------------------------------
        */

        if (!auth()->check()) {
            abort(401, 'Usuario no autenticado.');
        }

        if ((int) auth()->user()->idrol === 2) {
            abort(403, 'No tiene permisos para exportar.');
        }

        $request->validate([
            'modo' => 'nullable|in:item',
            'idAlmacen' => 'required|integer|exists:almacens,id',
            'fecha_consulta' => 'required|date_format:Y-m-d',
            'buscar' => 'nullable|string|max:255',
            'orden' => 'nullable|in:nombre_asc,stock_asc,stock_desc'
        ]);

        $idAlmacen = (int) $request->input('idAlmacen');
        $buscar = $request->input('buscar', '');
        $orden = $request->input('orden', 'stock_asc');
        $fechaConsulta = $request->input('fecha_consulta');

        $fechaActual = Carbon::now('America/La_Paz')
            ->toDateString();

        if ($fechaConsulta > $fechaActual) {
            abort(422, 'No se permite consultar fechas futuras.');
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | 2. Obtener almacén seleccionado
            |--------------------------------------------------------------------------
            */

            $almacen = DB::table('almacens')
                ->leftJoin(
                    'sucursales',
                    'almacens.sucursal',
                    '=',
                    'sucursales.id'
                )
                ->where('almacens.id', $idAlmacen)
                ->select(
                    'almacens.*',
                    'sucursales.departamento'
                )
                ->first();

            if (!$almacen) {
                throw new \DomainException(
                    'Debe seleccionar un almacén válido.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Reutilizar consulta histórica
            |--------------------------------------------------------------------------
            */

            $resultado = $this->consultaInventarioHistorico(
                $request
            );

            $query = $resultado['query'];

            $almacenesColumnas =
                $resultado['almacenes_columnas'];

            /*
            |--------------------------------------------------------------------------
            | 4. Información histórica
            |--------------------------------------------------------------------------
            */

            $informacionHistorial = [
                'tipo_consulta' =>
                    $resultado['tipo_consulta'],

                'fecha_consulta' =>
                    $resultado['fecha_consulta'],

                'fecha_corte' =>
                    $resultado['fecha_corte'],

                'mensaje_historial' =>
                    $resultado['mensaje_historial'],

                'departamento' =>
                    $almacen->departamento ?? '-'
            ];

            /*
            |--------------------------------------------------------------------------
            | 5. Generar nombre del archivo
            |--------------------------------------------------------------------------
            */

            $fechaArchivo = Carbon::parse(
                $resultado['fecha_consulta']
            )->format('d_m_Y');

            $nombreArchivoLimpio = preg_replace(
                '/[^a-zA-Z0-9_-]/',
                '_',
                $almacen->nombre_almacen
            );

            $nombreArchivoLimpio = preg_replace(
                '/_+/',
                '_',
                $nombreArchivoLimpio
            );

            $filename = 'InventarioHistorico_'
                . $nombreArchivoLimpio
                . '_'
                . $fechaArchivo
                . '.xlsx';

            /*
            |--------------------------------------------------------------------------
            | 6. Exportar Excel
            |--------------------------------------------------------------------------
            */

            return Excel::download(
                new InventarioHistoricoExport(
                    $query,
                    $idAlmacen,
                    $almacen->nombre_almacen,
                    $almacenesColumnas,
                    $informacionHistorial,
                    $buscar,
                    $orden
                ),
                $filename
            );

        } catch (\DomainException $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 422);

        } catch (\Throwable $e) {

            \Log::error(
                'Error al exportar inventario histórico Excel',
                [
                    'error' => $e->getMessage(),
                    'idalmacen' => $idAlmacen,
                    'fecha_consulta' => $fechaConsulta,
                    'idusuario' => auth()->id()
                ]
            );

            return response()->json([
                'message' =>
                    'No se pudo generar el Excel del inventario histórico.'
            ], 500);
        }
    }
}

class PDFBajoStockReporte extends FPDF
{
    protected $almacen = 'Todos';
    protected $proveedor = 'Todos';
    protected $categoria = 'Todas';
    protected $busqueda = 'Ninguna';

    public function setFiltros($almacen, $proveedor, $categoria, $busqueda)
    {
        $this->almacen = $almacen ?: 'Todos';
        $this->proveedor = $proveedor ?: 'Todos';
        $this->categoria = $categoria ?: 'Todas';
        $this->busqueda = $busqueda ?: 'Ninguna';
    }

    public function Header()
    {
        $rutaLogo = public_path('img/logoPrincipal.png');
        if (file_exists($rutaLogo)) {
            $this->Image($rutaLogo, 10, 5, 20);
        }

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 10, utf8_decode('REPORTE DE PRODUCTOS BAJO STOCK'), 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, utf8_decode('Fecha de generacion: ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        $this->Ln(5);

        $this->SetFillColor(236, 240, 241);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Rect(10, $this->GetY(), 277, 16, 'F');

        $this->SetX(12);
        $this->Cell(25, 8, utf8_decode('Almacen:'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(100, 8, utf8_decode(substr($this->almacen, 0, 50)), 0, 0, 'L');

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(25, 8, utf8_decode('Proveedor:'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(100, 8, utf8_decode(substr($this->proveedor, 0, 50)), 0, 1, 'L');

        $this->SetX(12);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(25, 8, utf8_decode('Categoria:'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(100, 8, utf8_decode(substr($this->categoria, 0, 50)), 0, 0, 'L');

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(25, 8, utf8_decode('Busqueda:'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(100, 8, utf8_decode(substr($this->busqueda, 0, 50)), 0, 1, 'L');
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, utf8_decode('Reporte Generado por el sistema - Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'C');
    }
}

class PDFInventario extends FPDF
{
    public function Footer()
    {
        // Posiciona el pie de pÃ¡gina a 1.5 cm del final
        $this->SetY(-15);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, utf8_decode('Reporte Generado por el sistema - Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'C');
    }
}

