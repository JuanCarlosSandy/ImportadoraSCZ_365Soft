<?php

namespace App\Http\Controllers;

use App\DetalleVenta;
use App\Moneda;
use App\Persona;
use App\Venta;
use App\User;
use App\Sucursales;
use App\Exports\VentasGeneralExport;
use App\Exports\VentasDetalladasExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use FPDF;


class ReportesVentas extends Controller
{
    public function ResumenVentasPorDocumento(Request $request)
    {
        $fechaInicio = $request->fechaInicio . ' 00:00:00';
        $fechaFin = $request->fechaFin . ' 23:59:59';
        $moneda = $request->moneda;

        $ventas = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('tipo_ventas', 'ventas.idtipo_venta', '=', 'tipo_ventas.id')
            ->join('roles', 'users.idrol', '=', 'roles.id')
            ->join('sucursales', 'ventas.idsucursal', '=', 'sucursales.id')
            ->select(
                'ventas.num_comprobante as Factura',
                'ventas.id',
                'sucursales.nombre as Nombre_sucursal',
                'ventas.fecha_hora',
                DB::raw("'$moneda' as Tipo_Cambio"),
                'tipo_ventas.nombre_tipo_ventas as Tipo_venta',
                'ventas.idtipo_venta',
                'roles.nombre AS nombre_rol',
                'users.usuario',
                'personas.nombre',
                'ventas.total AS importe_BS',
                DB::raw("ROUND((ventas.total / $moneda), 2) AS importe_usd"),
                'ventas.estado'
            )
            ->selectRaw("
            CASE
                WHEN ventas.idtipo_venta = 2 THEN
                COALESCE(
                    (
                    SELECT cc.saldo_restante
                    FROM cuotas_credito cc
                    WHERE cc.idcredito = ventas.id
                    ORDER BY cc.numero_cuota DESC
                    LIMIT 1
                    ),
                    ventas.total
                )
                ELSE 0
            END AS saldo_restante
            ")
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
            ->orderBy('ventas.fecha_hora', 'desc');

        if ($request->has('estadoVenta') && $request->estadoVenta !== 'Todos') {
            $estado_venta = $request->estadoVenta;

            // Convertir texto a nÃºmero
            if ($estado_venta === 'Registrado') {
                $ventas->where('ventas.estado', '=', 1);
            } elseif ($estado_venta === 'Anulado') {
                $ventas->where('ventas.estado', '=', 0);
            }
        }

        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $ventas->where('ventas.idsucursal', '=', $sucursal);
        }

        if ($request->has('ejecutivoCuentas') && $request->ejecutivoCuentas !== 'undefined') {
            $ejecutivoCuentas = $request->ejecutivoCuentas;
            $ventas->where('ventas.idusuario', '=', $ejecutivoCuentas);
        }

        if ($request->has('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = $request->idcliente;
            $ventas->where('ventas.idcliente', '=', $cliente);
        }

        $ventas = $ventas->get();

        $total_importeBs = 0;
        $total_importeUSD = 0;

        foreach ($ventas as $venta) {
            // Solo sumar ventas registradas (estado = 1)
            if ($venta->estado == 1) {
                $total_importeBs += $venta->importe_BS;
                $total_importeUSD += $venta->importe_usd;
            }
        }

        return [
            'ventas' => $ventas,
            'total_BS' => number_format($total_importeBs, 2, '.', ''),
            'total_USD' => number_format($total_importeUSD, 2, '.', ''),
            'cantidad_ventas' => $ventas->count(),
            'ventas_registradas_contado' => $ventas->where('estado', 1)->count(),
            'ventas_registradas_credito' => $ventas->where('estado', 2)->count(),
            'ventas_anuladas' => $ventas->where('estado', 0)->count()
        ];
    }
    public function ventasPorProducto(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $fechaInicio = $fechaInicio . ' 00:00:00';
        $fechaFin = $fechaFin . ' 23:59:59';
        $ventas = Venta::join('detalle_ventas', 'ventas.id', 'detalle_ventas.idventa')
            ->join('personas', 'personas.id', '=', 'ventas.idcliente')
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
            ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->select(
                'ventas.fecha_hora',
                'personas.nombre',
                'detalle_ventas.*',
                'articulos.codigo',
                'articulos.descripcion',
                'categorias.nombre as nombre_categoria',
                'marcas.nombre as nombre_marca',
                'industrias.nombre as nombre_industria',
                'medidas.descripcion_medida as medida'
            )
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);

        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $ventas->where('sucursales.id', $sucursal);
        }

        if ($request->has('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = $request->idcliente;
            $ventas->where('ventas.idcliente', $cliente);
        }
        if ($request->has('articulo') && $request->articulo !== 'undefined') {
            $articulo = $request->articulo;
            $ventas->where('detalle_ventas.idarticulo', $articulo);
        }
        if ($request->has('marca') && $request->marca !== 'undefined') {
            $idmarca = $request->marca;
            $ventas->where('articulos.idmarca', $idmarca);

        }
        if ($request->has('linea') && $request->linea !== 'undefined') {
            $idlinea = $request->linea;
            $ventas->where('articulos.idcategoria', $idlinea);

        }
        if ($request->has('industria') && $request->industria !== 'undefined') {
            $idindustria = $request->industria;
            $ventas->where('articulos.idindustria', $idindustria);

        }
        $ventas = $ventas->get();
        return ['resultados' => $ventas];
    }

    public function ResumenVentasPorDocumentoDetallado(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $fechaInicio = $fechaInicio . ' 00:00:00';
        $fechaFin = $fechaFin . ' 23:59:59';
        $moneda = $request->moneda;
        $ventas = DetalleVenta::select(
            'articulos.codigo as codigo_item',
            'articulos.nombre as nombre_articulo',
            'ventas.num_comprobante as Numventa',
            'ventas.id',
            'ventas.total as Importe Bs',
            'ventas.fecha_hora as Fecha',
            'personas.id as id_cliente',
            'personas.nombre as Cliente',
            'users.usuario as Vendedor',
            'detalle_ventas.descuento', // <-- aquÃ­ agregas
            'tipo_ventas.nombre_tipo_ventas as Tipo de venta',
            'roles.nombre as Ejecutivo de Venta',
            'sucursales.nombre as Sucursal',
            'articulos.nombre',
            'detalle_ventas.cantidad',
            'detalle_ventas.precio',
            'categorias.nombre as nombre_categoria',
            'marcas.nombre as nombre_marca',
            'industrias.nombre as nombre_industria',
            'medidas.descripcion_medida as medida',
            'personas.num_documento as nit',

            DB::raw("ROUND((detalle_ventas.precio / detalle_ventas.cantidad), 2) AS precio_unitario"),
            DB::raw("'$moneda' as Tipo_cambio"),
            DB::raw("ROUND((detalle_ventas.precio / $moneda), 2) AS importe_usd")
        )
            ->join('ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('tipo_ventas', 'ventas.idtipo_venta', '=', 'tipo_ventas.id')
            ->join('roles', 'users.idrol', '=', 'roles.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')

            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
            ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->orderBy('personas.nombre')
            ->orderBy('ventas.fecha_hora')
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);
        if ($request->has('estadoVenta')) {
            $estado_venta = $request->estadoVenta;
            if ($estado_venta !== 'Todos') {
                $ventas->where('ventas.estado', '=', $estado_venta);
            }
        }

        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $ventas->where('sucursales.id', $sucursal);
        }

        if ($request->has('ejecutivoCuentas') && $request->ejecutivoCuentas !== 'undefined') {
            $ejecutivoCuentas = $request->ejecutivoCuentas;
            $ventas->where('ventas.idusuario', $ejecutivoCuentas);
        }

        if ($request->has('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = $request->idcliente;
            $ventas->where('ventas.idcliente', $cliente);
        }
        $ventas = $ventas->get();

        $totalVentasPorCliente = [];

        foreach ($ventas as $venta) {
            $idCliente = $venta->id_cliente;
            $cantidadVenta = $venta->cantidad;
            $precioVenta = $venta->precio;

            if (!isset($totalVentasPorCliente[$idCliente])) {
                $totalVentasPorCliente[$idCliente] = [
                    'total_cantidad' => 0,
                    'total_precio' => 0,
                    'index' => null,
                ];
            }

            $totalVentasPorCliente[$idCliente]['total_cantidad'] += $cantidadVenta;
            $totalVentasPorCliente[$idCliente]['total_precio'] += $precioVenta;
            $totalVentasPorCliente[$idCliente]['index'] = $venta->id;
        }
        foreach ($ventas as $venta) {
            $idCliente = $venta->id_cliente;

            if (isset($totalVentasPorCliente[$idCliente]) && $venta->id == $totalVentasPorCliente[$idCliente]['index']) {
                $venta->total_cantidad_cliente = $totalVentasPorCliente[$idCliente]['total_cantidad'];
                $venta->total_precio_cliente = $totalVentasPorCliente[$idCliente]['total_precio'];
            }
        }

        return [
            'ventas' => $ventas,
        ];
    }

    public function reporteArticulosVendidos(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $fechaInicio = $fechaInicio . ' 00:00:00';
        $fechaFin = $fechaFin . ' 23:59:59';

        $query = DetalleVenta::select(
            'articulos.id as id_articulo',
            'articulos.nombre as nombre_articulo',
            DB::raw('SUM(detalle_ventas.cantidad) as cantidad_total'),
            DB::raw('DATE(ventas.fecha_hora) as fecha_venta')
        )
            ->join('ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
            ->groupBy('articulos.id', 'articulos.nombre', DB::raw('DATE(ventas.fecha_hora)'))
            ->orderBy('fecha_venta', 'asc');

        if ($request->has('estadoVenta')) {
            $estado_venta = $request->estadoVenta;
            if ($estado_venta !== 'Todos') {
                $query->where('ventas.estado', '=', $estado_venta);
            }
        }
        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $query->where('sucursales.id', $sucursal);
        }
        if ($request->has('ejecutivoCuentas') && $request->ejecutivoCuentas !== 'undefined') {
            $ejecutivoCuentas = $request->ejecutivoCuentas;
            $query->where('ventas.idusuario', $ejecutivoCuentas);
        }
        if ($request->has('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = $request->idcliente;
            $query->where('ventas.idcliente', $cliente);
        }
        if ($request->has('moneda') && $request->moneda !== 'undefined') {
            // Si necesitas filtrar por moneda, agrega aquÃ­ la lÃ³gica
        }

        $resultados = $query->get();
        return response()->json(['articulos_vendidos' => $resultados]);
    }

    public function descargarReporteGeneralPDF(Request $request)
    {
        // ---------------- CONSULTA ----------------
        $query = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')

            // Ãšltima cuota por venta
            ->leftJoin(DB::raw('(
                SELECT c1.idcredito, c1.saldo_restante
                FROM cuotas_credito c1
                INNER JOIN (
                    SELECT idcredito, MAX(numero_cuota) AS max_cuota
                    FROM cuotas_credito
                    GROUP BY idcredito
                ) c2
                ON c1.idcredito = c2.idcredito
                AND c1.numero_cuota = c2.max_cuota
            ) AS cc'), 'cc.idcredito', '=', 'ventas.id')

            ->select(
                'ventas.id',
                'ventas.num_comprobante',
                'ventas.fecha_hora',
                'personas.nombre as cliente',
                'ventas.total',
                'users.usuario as vendedor',
                'ventas.estado',
                'ventas.idtipo_venta',
                'cc.saldo_restante',
                'sucursales.nombre as sucursal_nombre'
            );

        // ---------------- FILTROS ----------------
        $filtros = [];

        if ($request->filled('sucursal') && $request->sucursal !== 'undefined') {
            $query->where('ventas.idsucursal', $request->sucursal);
            $sucursal = Sucursales::find($request->sucursal);
            $filtros[] = 'Sucursal: ' . ($sucursal ? $sucursal->nombre : 'Desconocida');
        }

        if ($request->filled('tipoReporte')) {
            if ($request->tipoReporte === 'dia' && $request->filled('fechaSeleccionada')) {
                $query->whereBetween('ventas.fecha_hora', [
                    $request->fechaSeleccionada . ' 00:00:00',
                    $request->fechaSeleccionada . ' 23:59:59'
                ]);
                $filtros[] = 'Fecha: ' . $request->fechaSeleccionada;
            } elseif ($request->tipoReporte === 'mes' && $request->filled('mesSeleccionado')) {
                $mes = $request->mesSeleccionado;
                $query->whereBetween('ventas.fecha_hora', [
                    $mes . '-01 00:00:00',
                    date('Y-m-t', strtotime($mes . '-01')) . ' 23:59:59'
                ]);
                $filtros[] = 'Mes: ' . date('F Y', strtotime($mes . '-01'));
            }
        }

        if ($request->filled('estadoVenta') && $request->estadoVenta !== 'Todos' && $request->estadoVenta !== 'undefined') {
            $this->aplicarFiltroEstadoVenta($query, $request->estadoVenta);
            $filtros[] = 'Estado: ' . $request->estadoVenta;
        }

        if ($request->filled('idcliente') && $request->idcliente !== 'undefined') {
            $query->where('ventas.idcliente', $request->idcliente);
            $filtros[] = 'Cliente ID: ' . $request->idcliente;
        }


        $idUsuarioFiltro = $this->obtenerIdUsuarioFiltro($request);
        if ($idUsuarioFiltro) {
            $query->where('ventas.idusuario', $idUsuarioFiltro);
            $vendedorObj = User::find($idUsuarioFiltro);
            $filtrosTexto[] = 'Vendedor: ' . ($vendedorObj ? $vendedorObj->usuario : 'Desconocido');
        }

        $ventas = $query->orderBy('ventas.fecha_hora', 'asc')->get();

        $tipoReporteTexto = 'Ventas Generales';
        if ($request->tipoReporte === 'dia') {
            $tipoReporteTexto = 'Ventas Diarias';
        } elseif ($request->tipoReporte === 'mes') {
            $tipoReporteTexto = 'Ventas Mensuales';
        }

        $pdf = new PDFVentas();
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $nombreSucursal = 'Todas';
        if ($request->filled('sucursal') && $request->sucursal !== 'undefined') {
            $sucursalObj = Sucursales::find($request->sucursal);
            $nombreSucursal = $sucursalObj ? $sucursalObj->nombre : 'Desconocida';
        }

        $nombreVendedor = 'Todos';
        if ($idUsuarioFiltro) {
            $vendedorObj = User::find($idUsuarioFiltro);
            $nombreVendedor = $vendedorObj ? $vendedorObj->usuario : 'Desconocido';
        }

        $periodoTexto = 'Todos';
        if ($request->tipoReporte === 'dia' && $request->filled('fechaSeleccionada')) {
            $periodoTexto = $request->fechaSeleccionada;
        } elseif ($request->tipoReporte === 'mes' && $request->filled('mesSeleccionado')) {
            $periodoTexto = date('F Y', strtotime($request->mesSeleccionado . '-01'));
        }

        $filtroExtra = [];
        if ($request->filled('estadoVenta') && $request->estadoVenta !== 'Todos' && $request->estadoVenta !== 'undefined') {
            $filtroExtra[] = 'Estado: ' . $request->estadoVenta;
        }
        if ($request->filled('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = Persona::find($request->idcliente);
            $filtroExtra[] = 'Cliente: ' . ($cliente ? $cliente->nombre : $request->idcliente);
        }
        $filtroExtraTexto = !empty($filtroExtra) ? implode(' | ', $filtroExtra) : 'Sin filtros';

        $this->renderVentasPdfHeader(
            $pdf,
            'REPORTE DE ' . strtoupper($tipoReporteTexto),
            $nombreSucursal,
            $nombreVendedor,
            $periodoTexto,
            $filtroExtraTexto
        );

        $renderHeaderTabla = function () use ($pdf) {
            $pdf->SetFillColor(236, 240, 241);
            $pdf->SetTextColor(52, 73, 94);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(22, 7, 'Nro Comp.', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Fecha y Hora', 1, 0, 'C', true);
            $pdf->Cell(38, 7, 'Cliente', 1, 0, 'C', true);
            $pdf->Cell(22, 7, 'Total', 1, 0, 'C', true);
            $pdf->Cell(28, 7, 'Vendedor', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Tipo Venta', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Estado', 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0);
        };

        $renderHeaderTabla();

        $totalVentasRegistradas = 0;
        $contadorFilas = 0;

        foreach ($ventas as $venta) {
            if ($pdf->GetY() > 265) {
                $pdf->AddPage();
                $renderHeaderTabla();
            }

            $tipoVenta = ($venta->idtipo_venta == 1) ? 'Contado' : 'Credito';
            $estadoTexto = 'Registrado';

            if ($venta->estado == 0) {
                $pdf->SetTextColor(192, 57, 43);
                $pdf->SetFillColor(255, 235, 235);
                $estadoTexto = 'Anulado';
            } else {
                $pdf->SetTextColor(0, 0, 0);
                if ($venta->idtipo_venta == 2 && $venta->saldo_restante !== null && (float) $venta->saldo_restante > 0) {
                    $estadoTexto = 'Pendiente Bs ' . number_format((float) $venta->saldo_restante, 2);
                } else {
                    $totalVentasRegistradas += $venta->total;
                }

                if ($contadorFilas % 2 === 0) {
                    $pdf->SetFillColor(255, 255, 255);
                } else {
                    $pdf->SetFillColor(249, 249, 249);
                }
            }

            $contadorFilas++;

            $pdf->Cell(22, 6, $venta->num_comprobante, 1, 0, 'L', true);
            $pdf->Cell(30, 6, date('d/m/Y H:i', strtotime($venta->fecha_hora)), 1, 0, 'L', true);
            $pdf->Cell(38, 6, utf8_decode(mb_strimwidth($venta->cliente ?? '-', 0, 25, '...')), 1, 0, 'L', true);
            $pdf->Cell(22, 6, number_format($venta->total, 2), 1, 0, 'R', true);
            $pdf->Cell(28, 6, utf8_decode(mb_strimwidth($venta->vendedor ?? '-', 0, 20, '...')), 1, 0, 'L', true);
            $pdf->Cell(20, 6, utf8_decode($tipoVenta), 1, 0, 'L', true);
            $pdf->Cell(30, 6, utf8_decode($estadoTexto), 1, 1, 'C', true);

            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 8, 'Total de ventas registradas: ' . number_format($totalVentasRegistradas, 2), 0, 1, 'R');


        // ================= NOMBRE DINÃMICO =================

        // 1?? Tipo
        $tipo = 'General';

        if ($request->tipoReporte === 'dia') {
            $tipo = 'Dia';
        } elseif ($request->tipoReporte === 'mes') {
            $tipo = 'Mes';
        }

        // 2?? Fecha del filtro
        $fechaFiltro = date('Y-m-d'); // por defecto hoy

        if ($request->tipoReporte === 'dia' && $request->filled('fechaSeleccionada')) {
            $fechaFiltro = $request->fechaSeleccionada;
        }

        if ($request->tipoReporte === 'mes' && $request->filled('mesSeleccionado')) {
            $fechaFiltro = $request->mesSeleccionado;
        }

        // 3?? Nombre sucursal
        $nombreSucursal = 'Todas';

        if ($request->filled('sucursal') && $request->sucursal !== 'undefined') {
            $sucursalObj = Sucursales::find($request->sucursal);
            $nombreSucursal = $sucursalObj ? $sucursalObj->nombre : 'Desconocida';
        }

        // Limpiar espacios y caracteres raros para nombre de archivo
        $nombreSucursal = str_replace([' ', '/', '\\'], '_', $nombreSucursal);

        // 4?? Construir nombre final
        $nombreArchivo = "ReporteGeneralVentas_{$tipo}_{$fechaFiltro}_{$nombreSucursal}.pdf";

        // Descargar
        $pdf->Output('D', $nombreArchivo);
        exit;
    }

    public function descargarVentasDetalladasPDF(Request $request)
    {
        $query = Venta::with(['detalles.producto', 'sucursal', 'usuario.persona', 'cliente'])
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->leftJoin(DB::raw('(
            SELECT c1.idcredito, c1.saldo_restante
            FROM cuotas_credito c1
            INNER JOIN (
                SELECT idcredito, MAX(numero_cuota) AS max_cuota
                FROM cuotas_credito
                GROUP BY idcredito
            ) c2
            ON c1.idcredito = c2.idcredito
            AND c1.numero_cuota = c2.max_cuota
        ) AS cc'), 'cc.idcredito', '=', 'ventas.id')
            ->select(
                'ventas.*',
                'ventas.idtipo_venta',
                'cc.saldo_restante',
                'sucursales.nombre as sucursal_nombre'
            );

        $filtros = [];

        // Filtro Sucursal
        if ($request->filled('sucursal') && $request->sucursal !== 'undefined') {
            $query->where('ventas.idsucursal', $request->sucursal);
            $sucursal = Sucursales::find($request->sucursal);
            $filtros[] = 'Sucursal: ' . ($sucursal ? $sucursal->nombre : 'Desconocida');
        }

        // Filtro FECHA (Este es el que hace que funcione por dÃ­a)
        if ($request->filled('tipoReporte')) {
            if ($request->tipoReporte === 'dia' && $request->filled('fechaSeleccionada')) {
                $query->whereBetween('ventas.fecha_hora', [
                    $request->fechaSeleccionada . ' 00:00:00',
                    $request->fechaSeleccionada . ' 23:59:59'
                ]);
                $filtros[] = 'Fecha: ' . $request->fechaSeleccionada;
            } elseif ($request->tipoReporte === 'mes' && $request->filled('mesSeleccionado')) {
                $mes = $request->mesSeleccionado;
                $query->whereBetween('ventas.fecha_hora', [
                    $mes . '-01 00:00:00',
                    date('Y-m-t', strtotime($mes . '-01')) . ' 23:59:59'
                ]);
                $filtros[] = 'Mes: ' . date('F Y', strtotime($mes . '-01'));
            }
        }

        // Filtro Estado
        if ($request->filled('estadoVenta') && $request->estadoVenta !== 'Todos' && $request->estadoVenta !== 'undefined') {
            $this->aplicarFiltroEstadoVenta($query, $request->estadoVenta);
            $filtros[] = 'Estado: ' . $request->estadoVenta;
        }

        // Filtro Cliente
        if ($request->filled('idcliente') && $request->idcliente !== 'undefined') {
            $query->where('ventas.idcliente', $request->idcliente);
            $filtros[] = 'Cliente ID: ' . $request->idcliente;
        }

        $idUsuarioFiltro = $this->obtenerIdUsuarioFiltro($request);
        if ($idUsuarioFiltro) {
            $query->where('ventas.idusuario', $idUsuarioFiltro);
            $vendedorObj = User::find($idUsuarioFiltro);
            $filtrosTexto[] = 'Vendedor: ' . ($vendedorObj ? $vendedorObj->usuario : 'Desconocido');
        }

        $ventas = $query->orderBy('ventas.fecha_hora', 'desc')->get();

        $pdf = new PDFDetalleVentas();
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $nombreSucursal = 'Todas';
        if ($request->filled('sucursal') && $request->sucursal !== 'undefined') {
            $sucursalObj = Sucursales::find($request->sucursal);
            $nombreSucursal = $sucursalObj ? $sucursalObj->nombre : 'Desconocida';
        }

        $nombreVendedor = 'Todos';
        if ($idUsuarioFiltro) {
            $vendedorObj = User::find($idUsuarioFiltro);
            $nombreVendedor = $vendedorObj ? $vendedorObj->usuario : 'Desconocido';
        }

        $periodoTexto = 'Todos';
        $tipoDetalleTexto = 'VENTAS DETALLADAS';
        if ($request->tipoReporte === 'dia' && $request->filled('fechaSeleccionada')) {
            $periodoTexto = $request->fechaSeleccionada;
            $tipoDetalleTexto = 'VENTAS DIARIAS DETALLADAS';
        } elseif ($request->tipoReporte === 'mes' && $request->filled('mesSeleccionado')) {
            $periodoTexto = date('F Y', strtotime($request->mesSeleccionado . '-01'));
            $tipoDetalleTexto = 'VENTAS MENSUALES DETALLADAS';
        }

        $filtroExtra = [];
        if ($request->filled('estadoVenta') && $request->estadoVenta !== 'Todos' && $request->estadoVenta !== 'undefined') {
            $filtroExtra[] = 'Estado: ' . $request->estadoVenta;
        }
        if ($request->filled('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = Persona::find($request->idcliente);
            $filtroExtra[] = 'Cliente: ' . ($cliente ? $cliente->nombre : $request->idcliente);
        }
        $filtroExtraTexto = !empty($filtroExtra) ? implode(' | ', $filtroExtra) : 'Sin filtros';

        $this->renderVentasPdfHeader(
            $pdf,
            'REPORTE DE ' . $tipoDetalleTexto,
            $nombreSucursal,
            $nombreVendedor,
            $periodoTexto,
            $filtroExtraTexto
        );

        $totalVentasRegistradas = 0;

        foreach ($ventas as $venta) {
            $descuentoTotalDetalle = 0;

            foreach ($venta->detalles as $d) {
                $descuentoUnitario = $d->descuento ?? 0;
                $descuentoTotalDetalle += $descuentoUnitario * $d->cantidad;
            }

            $descuentoAdicionalAplicado = max(0, ($venta->descuento_total ?? 0) - $descuentoTotalDetalle);

            $clienteNombre = $venta->cliente->nombre ?? 'S/N';
            $clienteRecortado = mb_strimwidth(utf8_decode($clienteNombre), 0, 30, '...');
            $saldoRestante = $venta->saldo_restante;

            $estadoTexto = 'Registrado';
            if ($venta->estado == 0) {
                $estadoTexto = 'Anulado';
            } elseif ($venta->idtipo_venta == 2 && $saldoRestante !== null && (float) $saldoRestante > 0) {
                $estadoTexto = 'Saldo Faltante Bs ' . number_format((float) $saldoRestante, 2);
            }

            if ($pdf->GetY() > 225) {
                $pdf->AddPage();
            }

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(52, 73, 94);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(190, 7, utf8_decode('VENTA NRO: ' . $venta->num_comprobante), 0, 1, 'L', true);

            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(236, 240, 241);
            $pdf->Cell(95, 6, 'Fecha: ' . date('d/m/Y H:i', strtotime($venta->fecha_hora)), 1, 0, 'L', true);
            $pdf->Cell(95, 6, 'Vendedor: ' . ($venta->usuario->persona->nombre ?? ''), 1, 1, 'L', true);
            $pdf->Cell(95, 6, 'Sucursal: ' . utf8_decode($venta->sucursal_nombre), 1, 0, 'L', true);
            $pdf->Cell(95, 6, 'Cliente: ' . $clienteRecortado, 1, 1, 'L', true);
            $pdf->Cell(95, 6, 'Desc. Adicional: ' . number_format($descuentoAdicionalAplicado, 2), 1, 0, 'L', true);
            $pdf->Cell(95, 6, 'Importe Total: ' . number_format($venta->total, 2), 1, 1, 'L', true);

            if ($venta->estado == 0) {
                $pdf->SetTextColor(192, 57, 43);
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->Cell(190, 6, 'Estado: ' . utf8_decode($estadoTexto), 1, 1, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(3);

            $w_cant = 25;
            $w_cod = 25;
            $w_prod = 65;
            $w_prec = 25;
            $w_desc = 25;
            $w_sub = 25;

            $renderDetalleHeader = function () use ($pdf, $w_cant, $w_cod, $w_prod, $w_prec, $w_desc, $w_sub) {
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetFillColor(236, 240, 241);
                $pdf->SetTextColor(52, 73, 94);
                $pdf->Cell($w_cant, 7, 'Cant.', 1, 0, 'C', true);
                $pdf->Cell($w_cod, 7, utf8_decode('Codigo'), 1, 0, 'C', true);
                $pdf->Cell($w_prod, 7, 'Producto', 1, 0, 'C', true);
                $pdf->Cell($w_prec, 7, 'P. Unitario', 1, 0, 'C', true);
                $pdf->Cell($w_desc, 7, 'Desc.', 1, 0, 'C', true);
                $pdf->Cell($w_sub, 7, 'Subtotal', 1, 1, 'C', true);
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(0, 0, 0);
            };

            $renderDetalleHeader();

            $sumaSubtotalesVenta = 0;
            $contadorDetalle = 0;

            foreach ($venta->detalles as $d) {
                if ($pdf->GetY() > 265) {
                    $pdf->AddPage();
                    $renderDetalleHeader();
                }

                $modo = strtolower($d->modo_venta ?? 'unidad');
                $plural = ($d->cantidad > 1 && substr($modo, -1) != 's') ? 's' : '';
                $textoCantidad = $d->cantidad . ' ' . $modo . $plural;

                $producto = $d->producto;
                $codigoProducto = $producto->codigo ?? '-';
                $nombreProducto = $producto->nombre ?? 'Articulo ' . $d->idarticulo;
                $nombreRecortado = mb_strimwidth(utf8_decode($nombreProducto), 0, 35, '...');
                $unidadesPorCaja = (isset($producto->unidad_envase) && $producto->unidad_envase > 0) ? $producto->unidad_envase : 1;

                $subtotalLinea = 0;
                $precioUnitario = $d->precio;

                if ($modo == 'caja') {
                    $subtotalLinea = $d->cantidad * $unidadesPorCaja * $precioUnitario;
                } elseif ($modo == 'docena') {
                    $subtotalLinea = $d->cantidad * 12 * $precioUnitario;
                } else {
                    $subtotalLinea = $d->cantidad * $precioUnitario;
                }

                $descuentoUnitario = $d->descuento ?? 0;
                $descuentoTotalProducto = $descuentoUnitario * $d->cantidad;
                $totalLinea = $subtotalLinea - $descuentoTotalProducto;
                $sumaSubtotalesVenta += $totalLinea;

                if ($contadorDetalle % 2 === 0) {
                    $pdf->SetFillColor(255, 255, 255);
                } else {
                    $pdf->SetFillColor(249, 249, 249);
                }
                $contadorDetalle++;

                $pdf->Cell($w_cant, 6, utf8_decode($textoCantidad), 1, 0, 'C', true);
                $pdf->Cell($w_cod, 6, utf8_decode($codigoProducto), 1, 0, 'C', true);
                $pdf->Cell($w_prod, 6, $nombreRecortado, 1, 0, 'L', true);
                $pdf->Cell($w_prec, 6, number_format($precioUnitario, 2), 1, 0, 'R', true);
                $pdf->Cell($w_desc, 6, number_format($descuentoTotalProducto, 2), 1, 0, 'R', true);
                $pdf->Cell($w_sub, 6, number_format($totalLinea, 2), 1, 1, 'R', true);
            }

            if ($venta->estado != 0) {
                $totalVentasRegistradas += $sumaSubtotalesVenta;
            }

            $pdf->Ln(5);
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Line(150, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(2);
        $pdf->Cell(0, 8, utf8_decode('Total de ventas: ' . number_format($totalVentasRegistradas, 2)), 0, 1, 'R');


        $pdf->Output('D', 'ventas_detalladas_' . date('Ymd_His') . '.pdf');
        exit;
    }

    private function renderVentasPdfHeader($pdf, $titulo, $sucursal, $vendedor, $periodo, $filtros)
    {
        $rutaLogo = public_path('img/logoPrincipal.png');
        if (!file_exists($rutaLogo)) {
            $rutaLogo = public_path('logo.png');
        }
        if (!file_exists($rutaLogo)) {
            $rutaLogo = public_path('img/logo.png');
        }
        if (!file_exists($rutaLogo)) {
            $rutaLogo = public_path('images/logo.png');
        }

        if (file_exists($rutaLogo)) {
            $pdf->Image($rutaLogo, 10, 5, 20);
        }

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 10, utf8_decode($titulo), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, utf8_decode('Fecha de generación: ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Rect(10, $pdf->GetY(), 190, 16, 'F');

        $pdf->SetX(12);
        $pdf->Cell(25, 8, utf8_decode('Sucursal:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(70, 8, utf8_decode(substr($sucursal, 0, 35)), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 8, utf8_decode('Vendedor:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(58, 8, utf8_decode(substr($vendedor, 0, 30)), 0, 1, 'L');

        $pdf->SetX(12);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 8, utf8_decode('Período:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(70, 8, utf8_decode(substr($periodo, 0, 35)), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 8, utf8_decode('Filtros:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(58, 8, utf8_decode(substr($filtros, 0, 30)), 0, 1, 'L');
        $pdf->Ln(5);
    }

    private function aplicarFiltroEstadoVenta($query, $estadoVenta)
    {
        if ($estadoVenta === 'Registrado') {
            $query->where('ventas.estado', 1);
        } elseif ($estadoVenta === 'Anulado') {
            $query->where('ventas.estado', 0);
        } elseif ($estadoVenta !== '' && $estadoVenta !== 'undefined' && $estadoVenta !== 'Todos') {
            $query->where('ventas.estado', $estadoVenta);
        }
    }

    private function obtenerIdUsuarioFiltro(Request $request)
    {
        if ($request->filled('idusuario') && $request->idusuario !== 'undefined') {
            return $request->idusuario;
        }

        if ($request->filled('ejecutivoCuentas') && $request->ejecutivoCuentas !== 'undefined') {
            return $request->ejecutivoCuentas;
        }

        return null;
    }

    public function exportarVentasGeneralExcel(Request $request)
    {
        $filters = $request->only([
            'sucursal',
            'tipoReporte',
            'fechaSeleccionada',
            'mesSeleccionado',
            'estadoVenta',
            'idcliente',
            'idusuario'
        ]);
        $filename = 'ventas_general_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new VentasGeneralExport($filters), $filename);
    }

    public function exportarVentasDetalladasExcel(Request $request)
    {
        $filters = $request->only([
            'sucursal',
            'tipoReporte',
            'fechaSeleccionada',
            'mesSeleccionado',
            'estadoVenta',
            'idcliente',
            'idusuario'
        ]);
        $filename = 'ventas_detalladas_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new VentasDetalladasExport($filters), $filename);
    }
}
class PDFVentas extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, utf8_decode('Reporte Generado por el sistema - Pagina ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
}
class PDFDetalleVentas extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, utf8_decode('Reporte Generado por el sistema - Pagina ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
}
