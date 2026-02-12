<?php

namespace App\Http\Controllers;

use App\Inventario;
use App\Articulo;
use App\Categoria;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ResumenKardexExport;
use Maatwebsite\Excel\Facades\Excel;
use FPDF;
use App\Exports\KardexDetalladoExport;



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
