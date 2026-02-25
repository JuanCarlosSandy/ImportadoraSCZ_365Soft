<?php

namespace App\Http\Controllers;

use App\Inventario;
use App\Articulo;
use App\Categoria;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ResumenKardexExport;
use Maatwebsite\Excel\Facades\Excel;
use FPDF;
use App\Exports\KardexDetalladoExport;
use App\Exports\ReporteInventarioValoradoExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;


class ReportesInventariosController extends Controller
{
    public function inventarioFisicoValorado(Request $request, $tipo)
    {
        $fechaVencimiento = $request->fecha_vencimiento;
        if ($tipo === 'item') {
            $resultados = DB::table('inventarios')
                ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
                ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
                ->select(
                    'articulos.nombre AS nombre_producto',
                    'articulos.unidad_envase',
                    'almacens.nombre_almacen',
                    DB::raw('SUM(inventarios.saldo_stock) AS saldo_stock_total'),
                    DB::raw('(SUM(inventarios.saldo_stock) * articulos.precio_costo_unid) AS costo_total'),
                    'categorias.nombre AS nombre_categoria',
                    'marcas.nombre AS nombre_marca',
                    'industrias.nombre AS nombre_industria'
                )
                ->where('inventarios.fecha_vencimiento', '<=', $fechaVencimiento)
                ->groupBy(
                    'articulos.nombre',
                    'articulos.unidad_envase',
                    'almacens.nombre_almacen',
                    'categorias.nombre',
                    'marcas.nombre',
                    'industrias.nombre',
                    'articulos.precio_costo_unid'
                )
                ->orderBy('articulos.nombre')
                ->orderBy('almacens.nombre_almacen');

        } else if ($tipo === 'lote') {
            $resultados = DB::table('inventarios')
                ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
                ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
                ->select(
                    'articulos.nombre AS nombre_producto',
                    'articulos.unidad_envase',
                    'articulos.precio_costo_unid',
                    'inventarios.saldo_stock',
                    DB::raw('(inventarios.saldo_stock * articulos.precio_costo_unid) AS costo_total'),
                    DB::raw('DATE_FORMAT(inventarios.created_at, "%Y-%m-%d") AS fecha_ingreso'),
                    'inventarios.fecha_vencimiento',
                    'almacens.nombre_almacen',
                    'categorias.nombre AS nombre_categoria',
                    'marcas.nombre AS nombre_marca',
                    'industrias.nombre AS nombre_industria'
                )
                ->where('inventarios.fecha_vencimiento', '<=', $fechaVencimiento)
                ->orderBy('articulos.nombre');

        }
        if ($request->has('idAlmacen') && $request->idAlmacen !== 'undefined') {
            $idAlmacen = $request->idAlmacen;
            $resultados->where('almacens.id', $idAlmacen);
        }
        if ($request->has('idArticulo') && $request->idArticulo !== 'undefined') {
            $idArticulo = $request->idArticulo;
            $resultados->where('articulos.id', $idArticulo);
        }
        if ($request->has('idMarca') && $request->idMarca !== 'undefined') {
            $idMarca = $request->idMarca;
            $resultados->where('articulos.idmarca', $idMarca);
        }
        if ($request->has('idLinea') && $request->idLinea !== 'undefined') {
            $idLinea = $request->idLinea;
            $resultados->where('articulos.idcategoria', $idLinea);

        }
        if ($request->has('idIndustria') && $request->idIndustria !== 'undefined') {
            $idIndustria = $request->idIndustria;
            $resultados->where('articulos.idindustria', $idIndustria);

        }
        $resultados = $resultados->get();
        return ['inventarios_valorado' => $resultados];

    }
   public function resumenFisicoMovimientos(Request $request)
{
    $fechaInicio = $request->fechaInicio . ' 00:00:00';
    $fechaFin = $request->fechaFin . ' 23:59:59';

    $productos = DB::table('articulos')
        ->select(
            'articulos.id',
            'articulos.nombre as nombre_producto',
            'articulos.codigo',
            'categorias.nombre as nombre_categoria',
            'almacens.id as id_almacen',
            'almacens.nombre_almacen as nombre_almacen',
            'sucursales.nombre as nombre_sucursal',
            'articulos.descripcion_fabrica',
            'articulos.unidad_envase'
        )
        ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
        ->join('inventarios', 'inventarios.idarticulo', '=', 'articulos.id')
        ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
        ->join('sucursales', 'almacens.sucursal', '=', 'sucursales.id')
        ->where('articulos.condicion', 1)
        ->groupBy(
            'articulos.id',
            'articulos.nombre',
            'articulos.codigo',
            'categorias.nombre',
            'almacens.id',
            'almacens.nombre_almacen',
            'sucursales.nombre',
            'articulos.descripcion_fabrica',
            'articulos.unidad_envase'
        );

    if ($request->has('articulo') && $request->articulo !== 'undefined') {
        $productos->where('articulos.id', $request->articulo);
    }
    if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
        $productos->where('sucursales.id', $request->sucursal);
    }
    if ($request->has('marca') && $request->marca !== 'undefined') {
        $productos->where('articulos.idmarca', $request->marca);
    }
    if ($request->has('linea') && $request->linea !== 'undefined') {
        $productos->where('articulos.idcategoria', $request->linea);
    }

    $productos = $productos->get();

    $resultados = [];

    foreach ($productos as $producto) {
        $unidadNombre = $producto->descripcion_fabrica ?: 'unidades';

        
        $ingresos = DB::table('detalle_ingresos')
            ->join('ingresos', 'detalle_ingresos.idingreso', '=', 'ingresos.id')
            ->where('ingresos.estado', 1)
            ->where('ingresos.idalmacen', $producto->id_almacen)
            ->where('detalle_ingresos.idarticulo', $producto->id)
            ->whereBetween('ingresos.fecha_hora', [$fechaInicio, $fechaFin])
            ->sum('detalle_ingresos.cantidad');

        
        
        $totalVentasCalculado = DB::table('ventas')
            ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id') 
            ->where('ventas.estado', '<>', 0)
            ->where('ventas.idalmacen', $producto->id_almacen)
            ->where('detalle_ventas.idarticulo', $producto->id)
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
            ->value(DB::raw("SUM(detalle_ventas.cantidad * (CASE 
                WHEN detalle_ventas.modo_venta = 'caja' THEN articulos.unidad_envase 
                WHEN detalle_ventas.modo_venta = 'docena' THEN 12 
                ELSE 1 
            END))"));

        
        $ventasEnUnidades = intval($totalVentasCalculado);

        
        $ventasTexto = $ventasEnUnidades . ' ' . ($ventasEnUnidades == 1 ? 'Unid.' : 'Unid.');

        
        
        $traspasosEntrada = DB::table('detalle_traspasos')
            ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
            ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
            ->where('inventarios.idarticulo', $producto->id)
            ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
            ->where(function($query) use ($producto) {
                
                $query->where(function($q) use ($producto) {
                    $q->where('traspasos.almacen_destino', $producto->id_almacen)
                      ->where('traspasos.tipo_traspaso', 'Salida');
                })
                
                ->orWhere(function($q) use ($producto) {
                    $q->where('traspasos.almacen_destino', $producto->id_almacen)
                      ->where('traspasos.tipo_traspaso', 'Entrada');
                })
                
                ->orWhere(function($q) use ($producto) {
                    $q->where('traspasos.almacen_origen', $producto->id_almacen)
                      ->where('traspasos.tipo_traspaso', 'Entrada');
                });
            })
            ->sum('detalle_traspasos.cantidad_traspaso');

        
        $traspasosSalida = DB::table('detalle_traspasos')
            ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
            ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
            ->where('inventarios.idarticulo', $producto->id)
            ->where('traspasos.almacen_origen', $producto->id_almacen)
            ->where('traspasos.tipo_traspaso', 'Salida')
            ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
            ->where(function($query) use ($producto) {
                
                $query->where(function($q) use ($producto) {
                    $q->where('traspasos.almacen_origen', $producto->id_almacen)
                      ->where('traspasos.tipo_traspaso', 'Salida');
                })
                
                ->orWhere(function($q) use ($producto) {
                    $q->where('traspasos.almacen_destino', $producto->id_almacen)
                      ->where('traspasos.tipo_traspaso', 'Salida');
                });
            })
            ->sum('detalle_traspasos.cantidad_traspaso');

        
        $ajusteEntrada = DB::table('ajuste_invetarios')
            ->where('producto', $producto->id)
            ->where('almacen', $producto->id_almacen)
            ->where('idtipobajas', '!=', 2)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('tipo_movimiento', 'entrada')
            ->sum('cantidad');

        
        $ajusteSalida = DB::table('ajuste_invetarios')
            ->where('producto', $producto->id)
            ->where('almacen', $producto->id_almacen)
            ->where('idtipobajas', '!=', 2)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('tipo_movimiento', 'salida')
            ->sum('cantidad');

        
        $ajuste = DB::table('ajuste_invetarios')
            ->where('producto', $producto->id)
            ->where('almacen', $producto->id_almacen)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->sum('cantidad');

        $unidadEnvase = max(1, (int) $producto->unidad_envase);
        $ajusteCajas = intdiv($ajuste, $unidadEnvase);
        $ajusteUnidades = $ajuste % $unidadEnvase;

        
        if ($ajusteCajas > 0 && $ajusteUnidades > 0) {
            $ajusteTexto = "{$ajusteCajas} cajas y {$ajusteUnidades} {$unidadNombre}";
        } elseif ($ajusteCajas > 0) {
            $ajusteTexto = "{$ajusteCajas} cajas";
        } elseif ($ajusteUnidades > 0) {
            $ajusteTexto = "{$ajusteUnidades} {$unidadNombre}";
        } else {
            $ajusteTexto = "0";
        }

        
        $saldo_stock = DB::table('inventarios')
            ->where('idarticulo', $producto->id)
            ->where('idalmacen', $producto->id_almacen)
            ->sum('saldo_stock');

        $saldoTexto = "{$saldo_stock} Unid.";

        $resultados[] = [
            'id_articulo' => $producto->id,
            'id_almacen'  => $producto->id_almacen,
            'codigo' => $producto->codigo,
            'sucursal' => $producto->nombre_sucursal,
            'almacen' => $producto->nombre_almacen,
            'nombre_producto' => $producto->nombre_producto,
            'categoria' => $producto->nombre_categoria,
            'total_ventas' => $ventasEnUnidades, 
            'total_ventas_texto' => $ventasTexto, 
            'total_ingresos' => $ingresos,
            'total_ingresos_texto' => $ingresos . ' Unid.',
            'total_traspasos_entrada' => $traspasosEntrada . ' Unid.',
            'total_traspasos_salida' => $traspasosSalida . ' Unid.',
            'total_ajuste' => $ajuste,
            'ajuste_entrada' => $ajusteEntrada,
            'ajuste_salida' => $ajusteSalida,
            'ajuste_entrada_detalle' => $ajusteEntrada,
            'ajuste_salida_detalle' => $ajusteSalida * -1,
            'total_ajuste_texto' => $ajusteTexto,
            'saldo_stock_actual' => $saldo_stock,
            'saldo_stock_actual_texto' => $saldoTexto,
            'descripcion_fabrica' => $producto->descripcion_fabrica,
            'unidad_envase' => $producto->unidad_envase
        ];
    }

    return ['resultados' => $resultados];
}

    public function detalleMovimientosProducto(Request $request)
    {
        $idArticulo = $request->idArticulo;
        $idAlmacen = $request->idAlmacen;
        
        if($idArticulo == 'undefined' || $idAlmacen == 'undefined' || !$idArticulo) {
            return response()->json(['ventas' => [], 'ingresos' => [], 'ajustes' => []]);
        }

        $fechaInicio = $request->fechaInicio . ' 00:00:00';
        $fechaFin = $request->fechaFin . ' 23:59:59';

        $ventas = DB::table('ventas')
            ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            
            ->join('personas', 'ventas.idcliente', '=', 'personas.id') 
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            
            ->select(
                'ventas.fecha_hora',
                'ventas.tipo_comprobante',
                'ventas.num_comprobante',
                'detalle_ventas.cantidad',
                'detalle_ventas.modo_venta',
                'detalle_ventas.precio',
                'users.usuario as vendedor',
                'personas.nombre as nombre_cliente',
                DB::raw("detalle_ventas.cantidad * (CASE 
                WHEN detalle_ventas.modo_venta = 'caja' THEN articulos.unidad_envase 
                WHEN detalle_ventas.modo_venta = 'docena' THEN 12 
                ELSE 1 END) as cantidad_en_unidades")
            )
            ->where('detalle_ventas.idarticulo', $idArticulo)
            ->where('ventas.idalmacen', $idAlmacen)
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
            ->where('ventas.estado', '<>', '0') 
            ->orderBy('ventas.fecha_hora', 'desc')
            ->get();

        $ingresos = DB::table('ingresos')
            ->join('detalle_ingresos', 'detalle_ingresos.idingreso', '=', 'ingresos.id')
            ->join('users', 'ingresos.idusuario', '=', 'users.id') 
            ->select(
                'ingresos.fecha_hora',
                'ingresos.tipo_comprobante',
                'ingresos.num_comprobante',
                DB::raw("CONCAT(detalle_ingresos.cantidad, ' Unidades') as cantidad"),
                'detalle_ingresos.precio',
                'users.usuario as responsable_compra'
            )
            ->where('detalle_ingresos.idarticulo', $idArticulo)
            ->where('ingresos.idalmacen', $idAlmacen)
            ->whereBetween('ingresos.fecha_hora', [$fechaInicio, $fechaFin])
            ->where('ingresos.estado', 1) 
            ->orderBy('ingresos.fecha_hora', 'desc')
            ->get();

        $ajustes = DB::table('ajuste_invetarios')
        ->leftJoin('tipo_bajas', 'ajuste_invetarios.idtipobajas', '=', 'tipo_bajas.id')
            ->select(
                'ajuste_invetarios.created_at as fecha_hora',
                'ajuste_invetarios.cantidad',
                'ajuste_invetarios.tipo_movimiento',
                DB::raw("COALESCE(tipo_bajas.nombre, 'Sin motivo') as motivo"), 
                DB::raw("'Baja/Ajuste' as tipo_ajuste"),
                DB::raw("'Sistema' as responsable"),
                DB::raw("
                    CASE 
                        WHEN ajuste_invetarios.tipo_movimiento = 'entrada' THEN ajuste_invetarios.cantidad
                        WHEN ajuste_invetarios.tipo_movimiento = 'salida' THEN ajuste_invetarios.cantidad * -1
                    END as cantidad
                ")
            )
            ->where('ajuste_invetarios.producto', $idArticulo)
            ->where('ajuste_invetarios.almacen', $idAlmacen)
            ->whereBetween('ajuste_invetarios.created_at', [$fechaInicio, $fechaFin])
            ->orderBy('ajuste_invetarios.created_at', 'desc')
            ->get();

        
        $traspasos = DB::table('traspasos')
            ->join('detalle_traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
            ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
            ->join('almacens as origen', 'traspasos.almacen_origen', '=', 'origen.id')
            ->join('almacens as destino', 'traspasos.almacen_destino', '=', 'destino.id')
            ->join('users', 'traspasos.idusuario', '=', 'users.id')
            ->select(
                'traspasos.id',
                'traspasos.created_at as fecha_hora', 
                'origen.nombre_almacen as almacen_origen',
                'destino.nombre_almacen as almacen_destino',
                'users.usuario as responsable',
                'detalle_traspasos.cantidad_traspaso as cantidad', 
                
                
                DB::raw("CASE 
                    -- Si soy el Almacén Origen, respeto lo que dice la columna tipo_traspaso
                    WHEN traspasos.almacen_origen = " . intval($idAlmacen) . " THEN traspasos.tipo_traspaso
                    
                    -- Si soy el Almacén Destino, ocurre lo contrario al tipo_traspaso
                    WHEN traspasos.almacen_destino = " . intval($idAlmacen) . " THEN 
                        CASE 
                            WHEN traspasos.tipo_traspaso = 'Entrada' THEN 'Salida'
                            ELSE 'Entrada'
                        END
                    
                    ELSE 'Indefinido'
                END as tipo_movimiento")
                
            )
            ->where('inventarios.idarticulo', $idArticulo)
            ->where(function($query) use ($idAlmacen) {
                $query->where('traspasos.almacen_origen', $idAlmacen)
                      ->orWhere('traspasos.almacen_destino', $idAlmacen);
            })
            ->whereBetween('traspasos.created_at', [$fechaInicio, $fechaFin])
            ->orderBy('traspasos.created_at', 'desc')
            ->get();

        return response()->json([
            'ventas' => $ventas,
            'ingresos' => $ingresos,
            'ajustes' => $ajustes,
            'traspasos' => $traspasos
        ]);
    }

    private function convertirACajasTexto($cantidad, $unidadEnvase)
    {
        $unidadEnvase = max(1, (int) $unidadEnvase);

        $cajas = intdiv($cantidad, $unidadEnvase);
        $resto = $cantidad % $unidadEnvase;

        if ($cantidad <= 0) {
            return "0";
        }

        if ($resto == 0) {
            return "{$cajas} cajas";
        }

        return "{$cajas} cajas y {$resto} unidades";
    }
    public function resumenFisicoMovimientosDetallado(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;

        $fechaInicio = $fechaInicio . ' 00:00:00';
        $fechaFin = $fechaFin . ' 23:59:59';
        $productos = DB::table('articulos')
            ->select(
                'articulos.id',
                'articulos.nombre',
                'articulos.codigo',
                'articulos.descripcion',
                'categorias.nombre as nombre_categoria',
                'marcas.nombre as nombre_marca',
                'industrias.nombre as nombre_industria',
                'medidas.descripcion_medida as medida',
                'almacens.sucursal as idSucursal'
            )
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
            ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->join('inventarios', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->groupBy('articulos.id', 'articulos.nombre', 'articulos.codigo', 'articulos.descripcion', 'categorias.nombre', 'marcas.nombre', 'industrias.nombre', 'medidas.descripcion_medida', 'almacens.sucursal');


        if ($request->has('articulo') && $request->articulo !== 'undefined') {
            $idarticulo = $request->articulo;
            $productos->where('articulos.id', $idarticulo);
        }
        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $productos->where('almacens.sucursal', $sucursal);
        }
        
        if ($request->has('marca') && $request->marca !== 'undefined') {
            $idmarca = $request->marca;
            $productos->where('articulos.idmarca', $idmarca);
        }
        if ($request->has('linea') && $request->linea !== 'undefined') {
            $idlinea = $request->linea;
            $productos->where('articulos.idcategoria', $idlinea);
        }
        $productos = $productos->get();

        $resultados = [];

        foreach ($productos as $producto) {
            $traspasos_ingreso = DB::table('detalle_traspasos')
                ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
                ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
                ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->where('inventarios.idarticulo', $producto->id)
                ->where('traspasos.tipo_traspaso', 'Entrada')
                ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
                ->sum('detalle_traspasos.cantidad_traspaso');
            $traspasos_salida = DB::table('detalle_traspasos')
                ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
                ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
                ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->where('inventarios.idarticulo', $producto->id)
                ->where('traspasos.tipo_traspaso', 'Salida')
                ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
                ->sum('detalle_traspasos.cantidad_traspaso');

            $saldoAnterior = DB::table('detalle_ingresos')
                ->join('ingresos', 'detalle_ingresos.idingreso', '=', 'ingresos.id')
                ->join('users', 'ingresos.idusuario', '=', 'users.id')
                ->where('ingresos.estado', 1)
                ->where('users.idsucursal', $producto->idSucursal)
                ->where('detalle_ingresos.idarticulo', $producto->id)
                ->where('ingresos.fecha_hora', '<', $fechaInicio)
                ->sum('detalle_ingresos.cantidad');

            $egresosAnteriores = DB::table('ventas')
                ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
                ->where('ventas.estado', '<>', 0)
                ->where('detalle_ventas.idarticulo', $producto->id)
                ->where('ventas.fecha_hora', '<', $fechaInicio)
                ->sum('detalle_ventas.cantidad');
            $saldoAnterior -= $egresosAnteriores;

            $ingresos = DB::table('detalle_ingresos')
                ->join('ingresos', 'detalle_ingresos.idingreso', '=', 'ingresos.id')
                ->join('users', 'ingresos.idusuario', '=', 'users.id')
                ->where('ingresos.estado', 1)
                ->where('users.idsucursal', $producto->idSucursal)
                ->where('detalle_ingresos.idarticulo', $producto->id)
                ->where('ingresos.fecha_hora', '>=', $fechaInicio)
                ->where('ingresos.fecha_hora', '<=', $fechaFin)
                ->sum('detalle_ingresos.cantidad');
            $ingresos += $traspasos_ingreso;
            $ventas = DB::table('ventas')
                ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
                ->join('users', 'ventas.idusuario', '=', 'users.id')
                ->where('ventas.estado', '<>', 0)
                ->where('users.idsucursal', $producto->idSucursal)
                ->where('detalle_ventas.idarticulo', $producto->id)
                ->where('ventas.fecha_hora', '>=', $fechaInicio)
                ->where('ventas.fecha_hora', '<=', $fechaFin)
                ->sum('detalle_ventas.cantidad');
            $ventas += $traspasos_salida;
            $saldoActual = $saldoAnterior + $ingresos - $ventas;

            $resultado = [

                'nombre_producto' => $producto->nombre,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'nombre_categoria' => $producto->nombre_categoria,
                'nombre_marca' => $producto->nombre_marca,
                'nombre_industria' => $producto->nombre_industria,
                'medida' => $producto->medida,
                'saldo_anterior' => $saldoAnterior,
                'ingresos' => $ingresos,
                'ventas' => $ventas,
                'saldo_actual' => $saldoActual,
                'traspasos_entrada' => $traspasos_ingreso,
                'traspasos_salida' => $traspasos_salida
            ];

            $resultados[] = $resultado;
        }

        return ['resultados' => $resultados, 'productos' => $productos];

    }

    public function exportarPDFResumenGeneral(Request $request)
{
    // 1. Obtenemos datos (Asegúrate de haber actualizado esta función con el código anterior)
    $data = $this->resumenFisicoMovimientos($request); 
    $resultados = $data['resultados'];

    // Configuración PDF Horizontal
    $pdf = new PDFWithPagination('L', 'mm', 'A4');
    $pdf->AliasNbPages(); 
    $pdf->AddPage();
    $pdf->SetMargins(10, 10, 10); 
    $pdf->SetAutoPageBreak(true, 15); 

    // --- ENCABEZADO ---
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'REPORTE GENERAL DE KARDEX FISICO', 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(25, 6, 'Filtro Fecha:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Del ' . $request->fechaInicio . ' al ' . $request->fechaFin, 0, 1);
    
    // --- TABLA ---
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 8); // Bajamos un poco la fuente a 8 para que entre todo
    $pdf->SetFillColor(230, 230, 230); 
    $pdf->SetDrawColor(180, 180, 180); 

    // *** NUEVOS ANCHOS CALCULADOS (Total aprox 275mm) ***
    // [0]COD, [1]PROD, [2]CAT, [3]VEN, [4]COM, [5]T.ENT, [6]T.SAL, [7]A.ENT, [8]A.SAL, [9]STOCK
    $w = [20, 55, 25, 20, 20, 22, 22, 22, 22, 25]; 
    
    // Cabecera de la tabla
    $pdf->Cell($w[0], 10, 'CODIGO', 1, 0, 'C', true);
    $pdf->Cell($w[1], 10, 'PRODUCTO', 1, 0, 'C', true);
    $pdf->Cell($w[2], 10, 'CATEGORIA', 1, 0, 'C', true);
    $pdf->Cell($w[3], 10, 'VENTAS', 1, 0, 'C', true);
    $pdf->Cell($w[4], 10, 'COMPRAS', 1, 0, 'C', true);
    
    // Nuevas Columnas
    $pdf->Cell($w[5], 10, 'TRAS. ENT', 1, 0, 'C', true);
    $pdf->Cell($w[6], 10, 'TRAS. SAL', 1, 0, 'C', true);
    
    $pdf->Cell($w[7], 10, 'AJ. ENT', 1, 0, 'C', true);
    $pdf->Cell($w[8], 10, 'AJ. SAL', 1, 0, 'C', true);
    $pdf->Cell($w[9], 10, 'STOCK', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 7); // Letra pequeña para el contenido

    foreach ($resultados as $item) {
        
        // 1. CODIGO
        $pdf->Cell($w[0], 8, utf8_decode($item['codigo']), 1, 0, 'C');
        
        // 2. PRODUCTO (Recorte para evitar desbordes visuales)
        $nombre = substr(utf8_decode($item['nombre_producto']), 0, 40);
        $pdf->Cell($w[1], 8, $nombre, 1, 0, 'L');
        
        // 3. CATEGORIA
        $categoria = substr(utf8_decode($item['categoria']), 0, 18);
        $pdf->Cell($w[2], 8, $categoria, 1, 0, 'L');
        
        // 4. VENTAS
        $pdf->Cell($w[3], 8, utf8_decode($item['total_ventas_texto']), 1, 0, 'R');
        
        // 5. COMPRAS
        $pdf->Cell($w[4], 8, utf8_decode($item['total_ingresos_texto']), 1, 0, 'R');

        // 6. TRASPASO ENTRADA (NUEVO)
        $valTrasEnt = $item['total_traspasos_entrada'];
        $txtTrasEnt = ($valTrasEnt > 0) ? $valTrasEnt . ' Und' : '0';
        $pdf->SetTextColor(0, 100, 0); // Verde oscuro opcional para resaltar
        $pdf->Cell($w[5], 8, utf8_decode($txtTrasEnt), 1, 0, 'R');
        $pdf->SetTextColor(0, 0, 0); // Reset color

        // 7. TRASPASO SALIDA (NUEVO)
        $valTrasSal = $item['total_traspasos_salida'];
        $txtTrasSal = ($valTrasSal > 0) ? $valTrasSal . ' Und' : '0';
        $pdf->SetTextColor(150, 0, 0); // Rojo oscuro opcional para resaltar
        $pdf->Cell($w[6], 8, utf8_decode($txtTrasSal), 1, 0, 'R');
        $pdf->SetTextColor(0, 0, 0); // Reset color

        // 8. AJUSTE ENTRADA
        $valEntrada = $item['ajuste_entrada'];
        $txtEntrada = ($valEntrada != 0) ? $valEntrada . ' Und' : '0';
        $pdf->Cell($w[7], 8, utf8_decode($txtEntrada), 1, 0, 'R');

        // 9. AJUSTE SALIDA
        $valSalida = $item['ajuste_salida'];
        $txtSalida = ($valSalida != 0) ? $valSalida . ' Und' : '0';
        $pdf->Cell($w[8], 8, utf8_decode($txtSalida), 1, 0, 'R');
        
        // 10. STOCK FINAL
        $pdf->SetFont('Arial', 'B', 7); // Negrita para el stock
        $pdf->Cell($w[9], 8, utf8_decode($item['saldo_stock_actual_texto']), 1, 1, 'R');
        $pdf->SetFont('Arial', '', 7); // Reset fuente
    }

    $nombreArchivo = 'KardexFisico_' . $request->fechaInicio . '.pdf';

    return response($pdf->Output('S'))
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');
}

    public function exportarExcelResumenGeneral(Request $request)
    {
        $data = $this->resumenFisicoMovimientos($request); 
        $resultados = $data['resultados'];

        $nombreSucursal = 'Casa Matriz'; 

   
        $nombreArticulo = null;
        if ($request->has('idarticulo') && $request->idarticulo != null) {
            $art = Articulo::find($request->idarticulo);
            $nombreArticulo = $art ? $art->nombre : null;
        }


        $nombreCategoria = null;
        if ($request->has('idcategoria') && $request->idcategoria != null) {
            $cat = Categoria::find($request->idcategoria);
            $nombreCategoria = $cat ? $cat->nombre : null;
        }


        $filtros = [
            'sucursal'    => $nombreSucursal,
            'articulo'    => $nombreArticulo,    
            'categoria'   => $nombreCategoria,   
            'fechaInicio' => $request->fechaInicio,
            'fechaFin'    => $request->fechaFin,
        ];

        $nombreArchivo = 'KardexFisico_' . $request->fechaInicio . '_' . $request->fechaFin . '.xlsx';

        return Excel::download(new ResumenKardexExport($resultados, $filtros), $nombreArchivo);
    }

    public function exportarPDFDetallado(Request $request)
{
    // 1. Obtenemos la data completa (incluyendo los nuevos TRASPASOS)
    $response = $this->detalleMovimientosProducto($request);
    $data = $response->getData(); 
    
    $ventas = $data->ventas;
    $ingresos = $data->ingresos;
    $ajustes = $data->ajustes;
    
    // Obtenemos los traspasos (Asegúrate de haber actualizado detalleMovimientosProducto)
    $traspasos = isset($data->traspasos) ? $data->traspasos : [];

    // Info del artículo
    $articulo = DB::table('articulos')->where('id', $request->idArticulo)->first();

    $pdf = new PDFWithPagination('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // --- ENCABEZADO ---
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'KARDEX DETALLADO DE PRODUCTO', 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Rango: ' . $request->fechaInicio . ' al ' . $request->fechaFin, 0, 1, 'C');
    $pdf->Ln(4);

    // Info Producto
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(20, 6, 'Codigo:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(40, 6, utf8_decode($articulo->codigo), 0, 0);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(20, 6, 'Producto:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, utf8_decode($articulo->nombre), 0, 1);
    $pdf->Ln(6);

    // --- 1. VENTAS ---
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(200, 220, 255); 
    $pdf->Cell(0, 8, '1. VENTAS', 1, 1, 'L', true);

    if (count($ventas) > 0) {
        $pdf->SetFont('Arial', 'B', 9); // Faltaba definir fuente negrita para header tabla ventas
        $pdf->Cell(35, 6, 'FECHA', 1);
        $pdf->Cell(25, 6, 'DOC', 1);
        $pdf->Cell(65, 6, 'CLIENTE', 1);
        $pdf->Cell(20, 6, 'MODO', 1);
        $pdf->Cell(15, 6, 'CANT.', 1, 0, 'R'); 
        $pdf->Cell(30, 6, 'TOTAL UNID.', 1, 1, 'R');

        $pdf->SetFont('Arial', '', 8);
        foreach ($ventas as $v) {
            $pdf->Cell(35, 6, $v->fecha_hora, 1);
            $pdf->Cell(25, 6, $v->num_comprobante, 1);
            $pdf->Cell(65, 6, substr(utf8_decode($v->nombre_cliente), 0, 45), 1);
            $pdf->Cell(20, 6, $v->modo_venta, 1);
            $pdf->Cell(15, 6, $v->cantidad, 1, 0, 'R');
            $pdf->Cell(30, 6, $v->cantidad_en_unidades, 1, 1, 'R'); 
        }
    } else {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 8, 'No hay ventas en este periodo.', 1, 1, 'C');
    }
    $pdf->Ln(5);

    // --- 2. COMPRAS ---
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(220, 255, 220); 
    $pdf->Cell(0, 8, '2. COMPRAS / INGRESOS', 1, 1, 'L', true);

    if (count($ingresos) > 0) {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(35, 6, 'FECHA', 1);
        $pdf->Cell(25, 6, 'DOC', 1);
        $pdf->Cell(105, 6, 'REGISTRADO POR', 1);
        $pdf->Cell(25, 6, 'CANT.', 1, 1, 'R');

        $pdf->SetFont('Arial', '', 8);
        foreach ($ingresos as $i) {
            $pdf->Cell(35, 6, $i->fecha_hora, 1);
            $pdf->Cell(25, 6, $i->num_comprobante, 1);
            $pdf->Cell(105, 6, utf8_decode($i->responsable_compra), 1);
            $pdf->Cell(25, 6, $i->cantidad, 1, 1, 'R');
        }
    } else {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 8, 'No hay compras en este periodo.', 1, 1, 'C');
    }
    $pdf->Ln(5);
    
    // --- 3. TRASPASOS (NUEVO) ---
    // Insertamos la sección de Traspasos antes de Ajustes o después, según prefieras.
    // Aquí la pongo como sección 3.
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(255, 220, 255); // Un color lila clarito
    $pdf->Cell(0, 8, '3. TRASPASOS ENTRE ALMACENES', 1, 1, 'L', true);

    if (count($traspasos) > 0) {
        $pdf->SetFont('Arial', 'B', 9);
        // Anchos: Fecha(35) + Mov(20) + Origen(40) + Destino(40) + Resp(30) + Cant(25) = 190
        $pdf->Cell(35, 6, 'FECHA', 1);
        $pdf->Cell(20, 6, 'MOV', 1, 0, 'C');
        $pdf->Cell(40, 6, 'ORIGEN', 1);
        $pdf->Cell(40, 6, 'DESTINO', 1);
        $pdf->Cell(30, 6, 'RESPONSABLE', 1);
        $pdf->Cell(25, 6, 'CANT.', 1, 1, 'R');

        $pdf->SetFont('Arial', '', 8);
        foreach ($traspasos as $t) {
            $pdf->Cell(35, 6, $t->fecha_hora, 1);
            
            // Lógica para poner Entrada (Verde) o Salida (Rojo) visualmente si quisieras
            // pero en FPDF estándar es más simple poner el texto.
            $tipoMov = strtoupper($t->tipo_movimiento);
            $pdf->Cell(20, 6, $tipoMov, 1, 0, 'C');
            
            $pdf->Cell(40, 6, substr(utf8_decode($t->almacen_origen), 0, 22), 1);
            $pdf->Cell(40, 6, substr(utf8_decode($t->almacen_destino), 0, 22), 1);
            $pdf->Cell(30, 6, substr(utf8_decode($t->responsable), 0, 18), 1);
            
            // Cantidad con signo
            $signo = ($t->tipo_movimiento == 'Entrada' || $t->tipo_movimiento == 'ENTRADA') ? '+' : '-';
            $txtCant = $signo . $t->cantidad . ' Und';
            
            $pdf->Cell(25, 6, $txtCant, 1, 1, 'R');
        }
    } else {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 8, 'No hay traspasos en este periodo.', 1, 1, 'C');
    }
    $pdf->Ln(5);

    // --- 4. AJUSTES ---
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(255, 240, 200); 
    $pdf->Cell(0, 8, '4. AJUSTES', 1, 1, 'L', true);

    if (count($ajustes) > 0) {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(35, 6, 'FECHA', 1);
        $pdf->Cell(130, 6, 'MOTIVO', 1);
        $pdf->Cell(25, 6, 'CANT.', 1, 1, 'R');

        $pdf->SetFont('Arial', '', 8);
        foreach ($ajustes as $a) {
            $pdf->Cell(35, 6, $a->fecha_hora, 1);
            $pdf->Cell(130, 6, utf8_decode($a->motivo), 1);
            
            // CORRECCION: $a en vez de $v (tenías un error de copy-paste en tu código original)
            $cantAbs = abs($a->cantidad);
            $signo = ($a->cantidad > 0) ? '+' : '-';
            $textoCantidad = $signo . $cantAbs . ' ' . ($cantAbs == 1 ? 'Ud' : 'Uds');
            
            $pdf->Cell(25, 6, $textoCantidad, 1, 1, 'R');
        }
    } else {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 8, 'No hay ajustes en este periodo.', 1, 1, 'C');
    }

    // Generación de archivo
    $nombreProductoLimpio = preg_replace('/[^A-Za-z0-9\-_]/', '_', $articulo->nombre);
    $nombreProductoLimpio = preg_replace('/_+/', '_', $nombreProductoLimpio); 
    $nombreProductoLimpio = trim($nombreProductoLimpio, '_'); 
    
    $nombreArchivo = 'KF_' . $nombreProductoLimpio . '_' . $request->fechaInicio . '.pdf';

    return response($pdf->Output('S'))
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');
}

    public function exportarExcelDetallado(Request $request)
    {
        
        $response = $this->detalleMovimientosProducto($request);
        $data = $response->getData(); 

        
        $articulo = DB::table('articulos')->where('id', $request->idArticulo)->first();

        
        $nombreProductoLimpio = preg_replace('/[^A-Za-z0-9\-_]/', '_', $articulo->nombre);
        $nombreProductoLimpio = preg_replace('/_+/', '_', $nombreProductoLimpio);
        $nombreProductoLimpio = trim($nombreProductoLimpio, '_');
        
        $nombreArchivo = 'KF_' . $nombreProductoLimpio . '_' . $request->fechaInicio . '_' . $request->fechaFin . '.xlsx';

        
        return Excel::download(
            new KardexDetalladoExport(
                $data, 
                $articulo, 
                $request->fechaInicio, 
                $request->fechaFin
            ), 
            $nombreArchivo
        );
    }

    public function obtenerDatosInventarioValorado(Request $request)
    {
        $idAlmacen = $request->idAlmacen;
        $buscar = $request->buscar;
        $idLaboratorio = $request->idLaboratorio;
        $idPresentacion = $request->idPresentacion;

        $inventarios = Articulo::leftJoin('inventarios', function ($join) use ($idAlmacen) {
            $join->on('articulos.id', '=', 'inventarios.idarticulo')
                ->where('inventarios.idalmacen', '=', $idAlmacen);
        })
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->leftJoin('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->select(
                'articulos.nombre as nombre_producto',
                'articulos.unidad_envase',
                'articulos.precio_uno as precio_venta',
                DB::raw('ROUND(articulos.precio_costo_unid, 2) as precio_costo_unid'),
                'almacens.nombre_almacen',
                'personas.nombre as nombre_proveedor',
                'categorias.nombre as nombre_categoria',
                DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) as saldo_stock_total'),
                DB::raw('FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / articulos.unidad_envase) as stock_en_paquetes'),
                DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) % articulos.unidad_envase as unidades_restantes'),
                DB::raw('ROUND(articulos.precio_costo_unid * IFNULL(SUM(inventarios.saldo_stock), 0), 2) as valor_total')
            )
            ->where('articulos.condicion', '=', 1);

        // 🔹 Filtros opcionales
        if (!empty($idLaboratorio)) {
            $inventarios = $inventarios->where('articulos.idproveedor', $idLaboratorio);
        }

        if (!empty($idPresentacion) && $idPresentacion !== 'undefined') {
            $inventarios = $inventarios->where('articulos.idcategoria', $idPresentacion);
        }

        if (!empty($buscar)) {
            $inventarios = $inventarios->where(function ($query) use ($buscar) {
                $query->where('articulos.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('almacens.nombre_almacen', 'like', '%' . $buscar . '%');
            });
        }

        // 🔹 Agrupamos y ordenamos (sin paginar)
        return $inventarios->groupBy(
            'articulos.nombre',
            'almacens.nombre_almacen',
            'articulos.unidad_envase',
            'articulos.precio_costo_unid',
            'categorias.nombre',
            'personas.nombre',
            'articulos.precio_uno',
        )
            ->orderBy('articulos.nombre')
            ->orderBy('almacens.nombre_almacen')
            ->get();
    }

    public function datosInventarioFisicoValorado(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $idAlmacen = $request->idAlmacen;
        $buscar = $request->buscar;
        $idLaboratorio = $request->idLaboratorio; 
        $idPresentacion = $request->idPresentacion; // opcional

        $inventarios = Articulo::leftJoin('inventarios', function ($join) use ($idAlmacen) {
            $join
                ->on('articulos.id', '=', 'inventarios.idarticulo')
                ->where('inventarios.idalmacen', '=', $idAlmacen);
        })
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->leftJoin('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->select(
                'articulos.nombre as nombre_producto',
                'articulos.unidad_envase',
                'categorias.nombre as nombre_categoria',
                DB::raw('ROUND(articulos.precio_costo_unid, 2) as precio_costo_unid'),
                'almacens.nombre_almacen',
                'personas.nombre as nombre_proveedor',
                'articulos.precio_uno as precio_venta',
                DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) as saldo_stock_total'),
                DB::raw('FLOOR(IFNULL(SUM(inventarios.saldo_stock), 0) / articulos.unidad_envase) as stock_en_paquetes'),
                DB::raw('IFNULL(SUM(inventarios.saldo_stock), 0) % articulos.unidad_envase as unidades_restantes'),
                DB::raw('ROUND(articulos.precio_costo_unid * IFNULL(SUM(inventarios.saldo_stock), 0), 2) as valor_total')
            )
            ->where('articulos.condicion', '=', 1);

        // 🔹 Filtrado por laboratorio (idproveedor)
        if (!empty($idLaboratorio)) {
            $inventarios = $inventarios->where('articulos.idproveedor', $idLaboratorio);
        }

        if (!empty($idPresentacion) && $idPresentacion !== 'undefined') {
            $inventarios = $inventarios->where('articulos.idcategoria', $idPresentacion);
        }

        // 🔹 Filtro de búsqueda general
        if (!empty($buscar)) {
            $inventarios = $inventarios->where(function ($query) use ($buscar) {
                $query
                    ->where('articulos.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('categorias.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('almacens.nombre_almacen', 'like', '%' . $buscar . '%');
            });
        }

        $inventarios = $inventarios
            ->groupBy(
                'articulos.nombre',
                'almacens.nombre_almacen',
                'articulos.unidad_envase',
                'articulos.precio_costo_unid',
                'categorias.nombre',
                'personas.nombre',
                'articulos.precio_uno',
            )
            ->orderBy('articulos.nombre')
            ->orderBy('almacens.nombre_almacen')
            ->paginate(10);

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

    public function exportarInventarioValoradoPdf(Request $request)
    {
        // Aumentar tiempo de ejecución y memoria para reportes grandes
        ini_set('max_execution_time', 600); // 10 minutos
        ini_set('memory_limit', '1024M');   // 1GB de memoria

        try {
            $inventarios = $this->obtenerDatosInventarioValorado($request);

            if ($inventarios->isEmpty()) {
                return response()->json(['error' => 'No se encontraron datos para el reporte.'], 404);
            }

            // 🔹 Preparar datos de filtros
            $nombreLaboratorio = 'Todos';
            if (!empty($request->idLaboratorio)) {
                $laboratorio = \DB::table('personas')->find($request->idLaboratorio);
                if ($laboratorio) $nombreLaboratorio = $laboratorio->nombre;
            }

            $nombrePresentacion = 'Todos';
            if (!empty($request->idPresentacion)) {
                $presentacion = \DB::table('categorias')->find($request->idPresentacion);
                if ($presentacion) $nombrePresentacion = $presentacion->nombre;
            }

            $nombreAlmacen = $request->nombreAlmacen ?? 'Todos';
            $buscar = $request->buscar ?? 'Ninguna';

            // 🔹 Generar PDF con FPDF
            $pdf = new FPDF('L', 'mm', 'A4');
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();

            // --- ENCABEZADO ---
            $rutaLogo = public_path('img/logoPrincipal.png');
            if (file_exists($rutaLogo)) {
                $pdf->Image($rutaLogo, 10, 5, 30);
            }

            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->Cell(0, 10, utf8_decode('REPORTE DE INVENTARIO FÍSICO VALORADO'), 0, 1, 'C');

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, utf8_decode('Fecha de generación: ' . date('d/m/Y H:i:s')), 0, 1, 'C');
            $pdf->Ln(5);

            // Caja de filtros
            $pdf->SetFillColor(236, 240, 241);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Rect(10, $pdf->GetY(), 277, 16, 'F');

            $pdf->SetX(12);
            $pdf->Cell(25, 8, utf8_decode('Almacén:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(100, 8, utf8_decode(substr($nombreAlmacen, 0, 50)), 0, 0, 'L');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(25, 8, utf8_decode('Proveedor:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(100, 8, utf8_decode(substr($nombreLaboratorio, 0, 50)), 0, 1, 'L');

            $pdf->SetX(12);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(25, 8, utf8_decode('Categoría:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(100, 8, utf8_decode(substr($nombrePresentacion, 0, 50)), 0, 0, 'L');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(25, 8, utf8_decode('Búsqueda:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(100, 8, utf8_decode(substr($buscar, 0, 50)), 0, 1, 'L');
            $pdf->Ln(5);

            // --- TABLA ---
            // Anchos en mm (total = 277, ancho util en A4 horizontal con margenes 10/10)
            $w = [28, 55, 16, 28, 42, 22, 22, 22, 42];
            $headers = ['Almacén', 'Producto', 'Envase', 'Categoría', 'Proveedor', 'P. Venta', 'Costo', 'Stock', 'Total'];

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(52, 73, 94);
            $pdf->SetTextColor(255, 255, 255);

            foreach ($headers as $i => $header) {
                $pdf->Cell($w[$i], 8, utf8_decode($header), 1, 0, 'C', true);
            }
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 7);
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;

            foreach ($inventarios as $item) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                
                $pdf->Cell($w[0], 6, utf8_decode(substr($item->nombre_almacen, 0, 20)), 1, 0, 'L', true);
                $pdf->Cell($w[1], 6, utf8_decode(substr($item->nombre_producto, 0, 38)), 1, 0, 'L', true);
                $pdf->Cell($w[2], 6, $item->unidad_envase, 1, 0, 'C', true);
                $pdf->Cell($w[3], 6, utf8_decode(substr($item->nombre_categoria, 0, 18)), 1, 0, 'L', true);
                $pdf->Cell($w[4], 6, utf8_decode(substr($item->nombre_proveedor, 0, 26)), 1, 0, 'L', true);
                $pdf->Cell($w[5], 6, number_format($item->precio_venta, 2), 1, 0, 'R', true);
                $pdf->Cell($w[6], 6, number_format($item->precio_costo_unid, 2), 1, 0, 'R', true);
                $pdf->Cell($w[7], 6, number_format($item->saldo_stock_total, 0), 1, 0, 'R', true);
                $pdf->Cell($w[8], 6, number_format($item->valor_total, 2), 1, 0, 'R', true);
                
                $pdf->Ln();
                $fill = !$fill;
            }

            $almacenNombre = $request->nombreAlmacen ?? 'General';
            $almacenClean = str_replace(' ', '_', $almacenNombre);
            $fechaHoy = now()->format('Y-m-d');
            $nombreArchivo = "ReporteInventarioValorado_{$almacenClean}_{$fechaHoy}.pdf";
            
            return response($pdf->Output('S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}\"");

        } catch (\Exception $e) {
            \Log::error('Error al generar PDF Inventario Valorado: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF: ' . $e->getMessage()], 500);
        }
    }

    public function exportarInventarioFisicoPdf(Request $request)
    {
        // Aumentar tiempo de ejecución y memoria para reportes grandes
        ini_set('max_execution_time', 600); // 10 minutos
        ini_set('memory_limit', '1024M');   // 1GB de memoria

        try {
            $inventarios = $this->obtenerDatosInventarioValorado($request);

            if ($inventarios->isEmpty()) {
                return response()->json(['error' => 'No se encontraron datos para el reporte.'], 404);
            }

            // 🔹 Preparar datos de filtros
            $nombreLaboratorio = 'Todos';
            if (!empty($request->idLaboratorio)) {
                $laboratorio = \DB::table('personas')->find($request->idLaboratorio);
                if ($laboratorio) $nombreLaboratorio = $laboratorio->nombre;
            }

            $nombrePresentacion = 'Todos';
            if (!empty($request->idPresentacion)) {
                $presentacion = \DB::table('categorias')->find($request->idPresentacion);
                if ($presentacion) $nombrePresentacion = $presentacion->nombre;
            }

            $nombreAlmacen = $request->nombreAlmacen ?? 'Todos';
            $buscar = $request->buscar ?? 'Ninguna';

            // 🔹 Generar PDF con FPDF
            $pdf = new FPDF('L', 'mm', 'A4');
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();

            // --- ENCABEZADO ---
            $rutaLogo = public_path('img/logoPrincipal.png');
            if (file_exists($rutaLogo)) {
                $pdf->Image($rutaLogo, 10, 5, 30);
            }

            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->Cell(0, 10, utf8_decode('REPORTE DE INVENTARIO FÍSICO'), 0, 1, 'C');

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, utf8_decode('Fecha de generación: ' . date('d/m/Y H:i:s')), 0, 1, 'C');
            $pdf->Ln(5);

            // Caja de filtros
            $pdf->SetFillColor(236, 240, 241);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Rect(10, $pdf->GetY(), 277, 16, 'F');

            $pdf->SetX(12);
            $pdf->Cell(25, 8, utf8_decode('Almacén:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(100, 8, utf8_decode(substr($nombreAlmacen, 0, 50)), 0, 0, 'L');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(25, 8, utf8_decode('Proveedor:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(100, 8, utf8_decode(substr($nombreLaboratorio, 0, 50)), 0, 1, 'L');

            $pdf->SetX(12);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(25, 8, utf8_decode('Categorías:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(100, 8, utf8_decode(substr($nombrePresentacion, 0, 50)), 0, 0, 'L');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(25, 8, utf8_decode('Búsqueda:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(100, 8, utf8_decode(substr($buscar, 0, 50)), 0, 1, 'L');
            $pdf->Ln(5);

            $w = [28, 55, 16, 28, 42, 22, 22, 22, 42];
            $headers = ['Almacén', 'Producto', 'Envase', 'Categoría', 'Proveedor', 'P. Venta', 'Costo', 'Stock', 'Total'];

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(52, 73, 94);
            $pdf->SetTextColor(255, 255, 255);

            foreach ($headers as $i => $header) {
                $pdf->Cell($w[$i], 8, utf8_decode($header), 1, 0, 'C', true);
            }
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 7);
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;

            foreach ($inventarios as $item) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                
                $pdf->Cell($w[0], 6, utf8_decode(substr($item->nombre_almacen, 0, 20)), 1, 0, 'L', true);
                $pdf->Cell($w[1], 6, utf8_decode(substr($item->nombre_producto, 0, 38)), 1, 0, 'L', true);
                $pdf->Cell($w[2], 6, $item->unidad_envase, 1, 0, 'C', true);
                $pdf->Cell($w[3], 6, utf8_decode(substr($item->nombre_categoria, 0, 18)), 1, 0, 'L', true);
                $pdf->Cell($w[4], 6, utf8_decode(substr($item->nombre_proveedor, 0, 26)), 1, 0, 'L', true);
                $pdf->Cell($w[5], 6, number_format($item->precio_venta, 2), 1, 0, 'R', true);
                $pdf->Cell($w[6], 6, number_format($item->precio_costo_unid, 2), 1, 0, 'R', true);
                $pdf->Cell($w[7], 6, number_format($item->saldo_stock_total, 0), 1, 0, 'R', true);
                $pdf->Cell($w[8], 6, number_format($item->valor_total, 2), 1, 0, 'R', true);
                
                $pdf->Ln();
                $fill = !$fill;
            }

            $almacenNombre = $request->nombreAlmacen ?? 'General';
            $almacenClean = str_replace(' ', '_', $almacenNombre);
            $fechaHoy = now()->format('Y-m-d');
            $nombreArchivo = "ReporteInventarioFisico_{$almacenClean}_{$fechaHoy}.pdf";
            
            return response($pdf->Output('S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}\"");

        } catch (\Exception $e) {
            \Log::error('Error al generar PDF Inventario Físico: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF: ' . $e->getMessage()], 500);
        }
    }

    public function exportarInventarioValorado(Request $request)
    {

        try {
            // 🔹 Capturar todos los parámetros
            $idAlmacen = $request->idAlmacen;
            $nombreAlmacen = $request->nombreAlmacen ?? 'almacen';
            $idLaboratorio = $request->idLaboratorio;
            $idPresentacion = $request->idPresentacion;
            $buscar = $request->buscar;
            $criterio = $request->criterio;
            $page = $request->page;
            $tipoSeleccionado = $request->tipoSeleccionado;

            // 🔹 Validar almacén obligatorio
            if (!$idAlmacen) {
                Log::error('Error: No se envió idAlmacen');
                return response()->json(['error' => 'Debe seleccionar un almacén'], 400);
            }

            // 🔹 Obtener nombres de filtros
            $nombreLaboratorio = 'Todos';
            if (!empty($idLaboratorio)) {
                $lab = DB::table('personas')->find($idLaboratorio);
                if ($lab) $nombreLaboratorio = $lab->nombre;
            }

            // 🔹 Generar nombre del archivo
            $nombreArchivo = 'inventario_valorado_' . $nombreAlmacen . '_' . date('Y-m-d') . '.xlsx';
    
            $export = new ReporteInventarioValoradoExport(
                $idAlmacen,
                $idLaboratorio,
                $idPresentacion,
                $buscar,
                $criterio,
                $tipoSeleccionado,
                $nombreAlmacen,
                $nombreLaboratorio
            );

            // 🔹 Descargar Excel
            return Excel::download($export, $nombreArchivo);

        } catch (\Exception $e) {
            Log::error('ERROR EN EXPORTAR INVENTARIO VALORADO:', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function exportarInventarioFisico(Request $request)
    {
        Log::info('=== INICIO EXPORTAR INVENTARIO VALORADO ===');

        try {
            // 🔹 Capturar todos los parámetros
            $idAlmacen = $request->idAlmacen;
            $nombreAlmacen = $request->nombreAlmacen ?? 'almacen';
            $idLaboratorio = $request->idLaboratorio;
            $idPresentacion = $request->idPresentacion;
            $buscar = $request->buscar;
            $criterio = $request->criterio;
            $page = $request->page;
            $tipoSeleccionado = $request->tipoSeleccionado;

            // 🔹 Registrar los datos recibidos
            Log::info('Datos recibidos para exportar inventario valorado:', [
                'idAlmacen' => $idAlmacen,
                'nombreAlmacen' => $nombreAlmacen,
                'idLaboratorio' => $idLaboratorio,
                'idPresentacion' => $idPresentacion,
                'buscar' => $buscar,
                'criterio' => $criterio,
                'page' => $page,
                'tipoSeleccionado' => $tipoSeleccionado
            ]);

            // 🔹 Validar almacén obligatorio
            if (!$idAlmacen) {
                Log::error('Error: No se envió idAlmacen');
                return response()->json(['error' => 'Debe seleccionar un almacén'], 400);
            }

            Log::info('Validación de almacén OK');

            // 🔹 Generar nombre del archivo
            $nombreArchivo = 'inventario_fisico_' . $nombreAlmacen . '_' . date('Y-m-d') . '.xlsx';
            Log::info('Nombre archivo generado: ' . $nombreArchivo);

            // 🔹 Obtener los datos corregidos usando la función que ya arreglamos
            $datos = $this->obtenerDatosInventarioValorado($request);

            if ($datos->isEmpty()) {
                return response()->json(['error' => 'No hay datos para exportar'], 404);
            }

            // 🔹 Preparar datos de filtros para el encabezado
            $nombreLaboratorio = 'Todos';
            if (!empty($idLaboratorio)) {
                $lab = \DB::table('personas')->find($idLaboratorio);
                if ($lab) $nombreLaboratorio = $lab->nombre;
            }

            $nombrePresentacion = 'Todos';
            if (!empty($idPresentacion)) {
                $pres = \DB::table('categorias')->find($idPresentacion);
                if ($pres) $nombrePresentacion = $pres->nombre;
            }

            $textoBuscar = !empty($buscar) ? $buscar : 'Ninguno';
            $fechaGeneracion = date('d/m/Y H:i:s');
            $tituloReporte = 'REPORTE DE INVENTARIO FÍSICO';

            $coleccion = $datos->map(function ($item) {
                return [
                    'Almacén'        => $item->nombre_almacen,
                    'Producto'       => $item->nombre_producto,
                    'Unidad Envase'  => $item->unidad_envase,
                    'Categoría'      => $item->nombre_categoria,
                    'Proveedor'      => $item->nombre_proveedor,
                    'Costo unitario' => $item->precio_costo_unid,
                    'Precio ventas'  => $item->precio_venta,
                    'Stock total'    => $item->saldo_stock_total,
                ];
            });

            // 🔹 Crear clase anónima para exportar
            $export = new class($coleccion, $nombreAlmacen, $nombreLaboratorio, $nombrePresentacion, $textoBuscar, $fechaGeneracion, $tituloReporte) implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents, WithDrawings {
                private $data;
                private $almacen;
                private $laboratorio;
                private $presentacion;
                private $buscar;
                private $fecha;
                private $titulo;

                public function __construct($data, $almacen, $laboratorio, $presentacion, $buscar, $fecha, $titulo) {
                    $this->data = $data;
                    $this->almacen = $almacen;
                    $this->laboratorio = $laboratorio;
                    $this->presentacion = $presentacion;
                    $this->buscar = $buscar;
                    $this->fecha = $fecha;
                    $this->titulo = $titulo;
                }

                public function collection() {
                    return $this->data;
                }

                public function headings(): array
                {
                    return [
                        'Almacén',
                        'Producto',
                        'Unidad Envase',
                        'Categoría',
                        'Proveedor',
                        'Costo unitario',
                        'Precio ventas',
                        'Stock total',
                    ];
                }

                public function startCell(): string
                {
                    return 'A8';
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 25, // Almacén
                        'B' => 40, // Producto
                        'C' => 15, // Unidad Envase
                        'D' => 25, // Categoría
                        'E' => 30, // Proveedor
                        'F' => 15, // Costo unitario
                        'G' => 15, // Precio ventas
                        'H' => 15, // Stock total
                    ];
                }

                public function drawings()
                {
                    $drawings = [];
                    $rutaLogo = public_path('img/logoPrincipal.png');
                    
                    if (file_exists($rutaLogo)) {
                        $drawing = new Drawing();
                        $drawing->setName('Logo');
                        $drawing->setDescription('Logo Empresa');
                        $drawing->setPath($rutaLogo);
                        $drawing->setHeight(70);
                        $drawing->setCoordinates('A1');
                        $drawings[] = $drawing;
                    }

                    return $drawings;
                }

                public function registerEvents(): array
                {
                    return [
                        AfterSheet::class => function(AfterSheet $event) {
                            $sheet = $event->sheet;

                             $sheet->mergeCells('C2:H2');
                            $sheet->setCellValue('C2', $this->titulo);
                            $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16);
                            $sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                             $sheet->mergeCells('C3:H3');
                            $sheet->setCellValue('C3', 'Fecha de generación: ' . $this->fecha);
                            $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                             $sheet->setCellValue('A5', 'Almacén: ' . $this->almacen);
                            $sheet->setCellValue('A6', 'Categoría: ' . $this->presentacion);
                            $sheet->setCellValue('E5', 'Proveedor: ' . $this->laboratorio);
                            $sheet->setCellValue('E6', 'Búsqueda: ' . $this->buscar);

                            $sheet->getStyle('A5:H6')->getFont()->setBold(true);
                        },
                    ];
                }

                public function styles(Worksheet $sheet) {
                    $highestRow = $sheet->getHighestRow();

                     $sheet->getStyle('A8:H8')->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                     $sheet->getStyle('A9:H' . $highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                     $sheet->getStyle('F9:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                     $sheet->getStyle('C9:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    return [];
                }
            };

            return Excel::download($export, $nombreArchivo);

        } catch (\Exception $e) {
            Log::error('ERROR EN EXPORTAR INVENTARIO VALORADO:', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}

class PDFWithPagination extends FPDF
{
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $texto = utf8_decode('Página ') . $this->PageNo() . '/{nb}';
        $this->Cell(0, 10, $texto, 0, 0, 'C');
    }
}
