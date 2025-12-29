<?php

namespace App\Http\Controllers;

use App\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use FPDF;


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

            $ventasCaja = DB::table('ventas')
                ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
                ->where('ventas.estado', '<>', 0)
                ->where('ventas.idalmacen', $producto->id_almacen)
                ->where('detalle_ventas.idarticulo', $producto->id)
                ->where('detalle_ventas.modo_venta', 'caja')
                ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
                ->sum('detalle_ventas.cantidad');

            $ventasUnidad = DB::table('ventas')
                ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
                ->where('ventas.estado', '<>', 0)
                ->where('ventas.idalmacen', $producto->id_almacen)
                ->where('detalle_ventas.idarticulo', $producto->id)
                ->where('detalle_ventas.modo_venta', 'unidad')
                ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
                ->sum('detalle_ventas.cantidad');
            $unidadEnvase = max(1, (int) $producto->unidad_envase);

            // convertir cajas -> unidades
            $ventasEnUnidades = ($ventasCaja * $unidadEnvase) + $ventasUnidad;

            // ahora obtener cajas y unidades sobrantes
            $ventasCajasFinal = intdiv($ventasEnUnidades, $unidadEnvase);
            $ventasUnidadesFinal = $ventasEnUnidades % $unidadEnvase;

            // generar texto final
            if ($ventasCajasFinal > 0 && $ventasUnidadesFinal > 0) {
                $ventasTexto = "{$ventasCajasFinal} cajas y {$ventasUnidadesFinal} {$unidadNombre}";
            } elseif ($ventasCajasFinal > 0) {
                $ventasTexto = "{$ventasCajasFinal} cajas";
            } else {
                $ventasTexto = "{$ventasUnidadesFinal} {$unidadNombre}";
            }

            $traspasosEntrada = DB::table('detalle_traspasos')
                ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
                ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
                ->where('inventarios.idarticulo', $producto->id)
                ->where('traspasos.almacen_destino', $producto->id_almacen)
                ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
                ->sum('detalle_traspasos.cantidad_traspaso');

            $traspasosSalida = DB::table('detalle_traspasos')
                ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
                ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
                ->where('inventarios.idarticulo', $producto->id)
                ->where('traspasos.almacen_origen', $producto->id_almacen)
                ->where('traspasos.tipo_traspaso', 'Salida')
                ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
                ->sum('detalle_traspasos.cantidad_traspaso');

            $ajuste = DB::table('ajuste_invetarios')
                ->where('producto', $producto->id)
                ->where('almacen', $producto->id_almacen)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->sum('cantidad');

            $ajusteCajas = intdiv($ajuste, $unidadEnvase);
            $ajusteUnidades = $ajuste % $unidadEnvase;

            // generar texto final
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
            $saldoCajas = intdiv($saldo_stock, $unidadEnvase);
            $saldoUnidades = $saldo_stock % $unidadEnvase;

            if ($saldoCajas > 0 && $saldoUnidades > 0) {
                $saldoTexto = "{$saldoCajas} cajas y {$saldoUnidades} {$unidadNombre}";
            } elseif ($saldoCajas > 0) {
                $saldoTexto = "{$saldoCajas} cajas";
            } elseif ($saldoUnidades > 0) {
                $saldoTexto = "{$saldoUnidades} {$unidadNombre}";
            } else {
                $saldoTexto = "0";
            }

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
                'total_ingresos_texto' => $ingresos . ' cajas',

                'total_traspasos_entrada' => $traspasosEntrada,
                'total_traspasos_salida' => $traspasosSalida,
                'total_ajuste' => $ajuste,
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

        // 1. VENTAS 
        $ventas = DB::table('ventas')
            ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            
            ->join('personas', 'ventas.idcliente', '=', 'personas.id') 
            
            ->select(
                'ventas.fecha_hora',
                'ventas.tipo_comprobante',
                'ventas.num_comprobante',
                'detalle_ventas.cantidad',
                'detalle_ventas.modo_venta',
                'detalle_ventas.precio',
                'users.usuario as vendedor',
                'personas.nombre as nombre_cliente' 
            )
            ->where('detalle_ventas.idarticulo', $idArticulo)
            ->where('ventas.idalmacen', $idAlmacen)
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
            ->where('ventas.estado', '<>', '0') 
            ->orderBy('ventas.fecha_hora', 'desc')
            ->get();

        // 2. COMPRAS / INGRESOS 
        $ingresos = DB::table('ingresos')
            ->join('detalle_ingresos', 'detalle_ingresos.idingreso', '=', 'ingresos.id')
            ->join('users', 'ingresos.idusuario', '=', 'users.id') 
            ->select(
                'ingresos.fecha_hora',
                'ingresos.tipo_comprobante',
                'ingresos.num_comprobante',
                'detalle_ingresos.cantidad',
                'detalle_ingresos.precio',
                'users.usuario as responsable_compra' 
            )
            ->where('detalle_ingresos.idarticulo', $idArticulo)
            ->where('ingresos.idalmacen', $idAlmacen)
            ->whereBetween('ingresos.fecha_hora', [$fechaInicio, $fechaFin])
            ->where('ingresos.estado', 1) 
            ->orderBy('ingresos.fecha_hora', 'desc')
            ->get();

        // 3. AJUSTES (ESTE YA ESTABA BIEN)
        $ajustes = DB::table('ajuste_invetarios')
            ->select(
                'ajuste_invetarios.created_at as fecha_hora',
                'ajuste_invetarios.cantidad',
                'ajuste_invetarios.idtipobajas as motivo', 
                DB::raw("'Baja/Ajuste' as tipo_ajuste"),
                DB::raw("'Sistema' as responsable") 
            )
            ->where('ajuste_invetarios.producto', $idArticulo)
            ->where('ajuste_invetarios.almacen', $idAlmacen)
            ->whereBetween('ajuste_invetarios.created_at', [$fechaInicio, $fechaFin])
            ->orderBy('ajuste_invetarios.created_at', 'desc')
            ->get();

        return response()->json([
            'ventas' => $ventas,
            'ingresos' => $ingresos,
            'ajustes' => $ajustes
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
        // Agregar filtros opcionales si se proporcionan otros parámetros
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
        // 1. Obtenemos datos
        $data = $this->resumenFisicoMovimientos($request); 
        $resultados = $data['resultados'];

        // *** CORRECCIÓN 1: 'L' para Landscape (Horizontal) ***
        $pdf = new PDFWithPagination('L', 'mm', 'A4');
        $pdf->AliasNbPages(); // Importante para que funcione el {nb} del footer
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10); // Márgenes de 1cm
        $pdf->SetAutoPageBreak(true, 15); // Salto de página automático

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
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 230, 230); // Gris clarito
        $pdf->SetDrawColor(180, 180, 180); // Bordes grises finos

        // *** CORRECCIÓN 2: Ajuste de Anchos ***
        // Total suma: 270mm (Entra perfecto en A4 Horizontal que mide 297mm)
        // [Codigo, Producto, Categoria, Ventas, Compras, Ajustes, Stock]
        $w = [25, 85, 40, 30, 30, 30, 30]; 
        
        // Cabecera de la tabla
        $pdf->Cell($w[0], 10, 'CODIGO', 1, 0, 'C', true);
        $pdf->Cell($w[1], 10, 'PRODUCTO', 1, 0, 'C', true);
        $pdf->Cell($w[2], 10, 'CATEGORIA', 1, 0, 'C', true);
        $pdf->Cell($w[3], 10, 'VENTAS', 1, 0, 'C', true);
        $pdf->Cell($w[4], 10, 'COMPRAS', 1, 0, 'C', true);
        $pdf->Cell($w[5], 10, 'AJUSTES', 1, 0, 'C', true);
        $pdf->Cell($w[6], 10, 'STOCK', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 8);

        foreach ($resultados as $item) {
            // Lógica para altura dinámica si el nombre es muy largo (opcional, pero ayuda)
            // Por simplicidad usaremos Cell normal recortando texto si es excesivo
            
            $pdf->Cell($w[0], 8, utf8_decode($item['codigo']), 1, 0, 'C');
            
            // Recortamos a 50 caracteres para que no rompa la fila
            $nombre = substr(utf8_decode($item['nombre_producto']), 0, 50);
            $pdf->Cell($w[1], 8, $nombre, 1, 0, 'L');
            
            $categoria = substr(utf8_decode($item['categoria']), 0, 22);
            $pdf->Cell($w[2], 8, $categoria, 1, 0, 'L');
            
            // Cantidades alineadas a la derecha ('R')
            $pdf->Cell($w[3], 8, utf8_decode($item['total_ventas_texto']), 1, 0, 'R');
            $pdf->Cell($w[4], 8, utf8_decode($item['total_ingresos_texto']), 1, 0, 'R');
            $pdf->Cell($w[5], 8, utf8_decode($item['total_ajuste_texto']), 1, 0, 'R');
            $pdf->Cell($w[6], 8, utf8_decode($item['saldo_stock_actual_texto']), 1, 1, 'R');
        }

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="reporte_general.pdf"');
    }

    public function exportarPDFDetallado(Request $request)
    {
        // 1. Obtenemos la data del JSON Response
        // Como detalleMovimientosProducto retorna un response()->json(), accedemos a la data así:
        $response = $this->detalleMovimientosProducto($request);
        $data = $response->getData(); // Esto convierte el JSON a Objeto PHP
        
        $ventas = $data->ventas;
        $ingresos = $data->ingresos;
        $ajustes = $data->ajustes;

        // 2. Buscar info del producto (Nombre y Código) para el título
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

        // --- SECCIÓN 1: VENTAS ---
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(200, 220, 255); // Azulito
        $pdf->Cell(0, 8, '1. VENTAS', 1, 1, 'L', true);

        if (count($ventas) > 0) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(35, 6, 'FECHA', 1);
            $pdf->Cell(25, 6, 'DOC', 1);
            $pdf->Cell(80, 6, 'CLIENTE', 1);
            $pdf->Cell(25, 6, 'MODO', 1);
            $pdf->Cell(25, 6, 'CANT.', 1, 1, 'R');

            $pdf->SetFont('Arial', '', 8);
            foreach ($ventas as $v) {
                $pdf->Cell(35, 6, $v->fecha_hora, 1);
                $pdf->Cell(25, 6, $v->num_comprobante, 1);
                $pdf->Cell(80, 6, substr(utf8_decode($v->nombre_cliente), 0, 45), 1);
                $pdf->Cell(25, 6, $v->modo_venta, 1);
                $pdf->Cell(25, 6, $v->cantidad, 1, 1, 'R');
            }
        } else {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(0, 8, 'No hay ventas en este periodo.', 1, 1, 'C');
        }
        $pdf->Ln(5);

        // --- SECCIÓN 2: COMPRAS ---
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(220, 255, 220); // Verdecito
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

        // --- SECCIÓN 3: AJUSTES ---
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(255, 240, 200); // Naranja bajito
        $pdf->Cell(0, 8, '3. AJUSTES', 1, 1, 'L', true);

        if (count($ajustes) > 0) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(35, 6, 'FECHA', 1);
            $pdf->Cell(130, 6, 'MOTIVO / ID BAJA', 1);
            $pdf->Cell(25, 6, 'CANT.', 1, 1, 'R');

            $pdf->SetFont('Arial', '', 8);
            foreach ($ajustes as $a) {
                $pdf->Cell(35, 6, $a->fecha_hora, 1);
                $pdf->Cell(130, 6, utf8_decode($a->motivo), 1);
                $pdf->Cell(25, 6, $a->cantidad, 1, 1, 'R');
            }
        } else {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(0, 8, 'No hay ajustes en este periodo.', 1, 1, 'C');
        }

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="detalle_'.$articulo->nombre.'.pdf"');
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
