<?php

namespace App\Http\Controllers;

use App\DetalleVenta;
use App\Moneda;
use App\Venta;
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
            
            // Convertir texto a número
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
            'detalle_ventas.descuento', // <-- aquí agregas
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
            // Si necesitas filtrar por moneda, agrega aquí la lógica
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

        // Última cuota por venta
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
        $query->where('users.idsucursal', $request->sucursal);
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
        $query->where('ventas.estado', $request->estadoVenta);
        $filtros[] = 'Estado: ' . $request->estadoVenta;
    }

    if ($request->filled('idcliente') && $request->idcliente !== 'undefined') {
        $query->where('ventas.idcliente', $request->idcliente);
        $filtros[] = 'Cliente ID: ' . $request->idcliente;
    }

    $ventas = $query->orderBy('ventas.fecha_hora', 'asc')->get();

    // ---------------- PDF ----------------
$pdf = new PDFVentas();
$pdf->AliasNbPages();
$pdf->AddPage();

/* ========= HEADER ÚNICO ========= */
$pdf->SetFillColor(11, 79, 119);
$pdf->SetTextColor(255);

// Altura total del header
$headerHeight = 22;

// 1️⃣ Fondo azul (UN SOLO BLOQUE)
$pdf->Cell(0, $headerHeight, '', 0, 1, 'L', true);

// 2️⃣ Volver arriba del header
$pdf->SetY($pdf->GetY() - $headerHeight);
$pdf->SetX(10);

// 3️⃣ Título (izquierda)
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(120, 10, utf8_decode('REPORTE GENERAL DE VENTAS'), 0, 0, 'L');

// 4️⃣ Logo (derecha, DENTRO del header)
$pdf->Image(
    public_path('img/logoPrincipal.png'),
    165,                  // X (derecha)
    $pdf->GetY() + 1,     // Y alineado
    28                    // ancho
);

// 5️⃣ Segunda línea dentro del mismo header
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(120, 10, utf8_decode('Fecha de generación: ' . date('d/m/Y H:i')), 0, 1, 'L');

// 6️⃣ Salir del header
$pdf->Ln(4);

// Separador inferior
$pdf->SetDrawColor(11, 79, 119);
$pdf->SetLineWidth(0.6);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(6);


    /* ========= FILTROS ========= */
    if (count($filtros) > 0) {
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 5, utf8_decode('Filtros aplicados:'), 0, 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, utf8_decode(implode(' | ', $filtros)), 0, 1);
        $pdf->Ln(4);
    }

    /* ========= CABECERA TABLA ========= */
    $pdf->SetFillColor(11, 79, 119);
    $pdf->SetTextColor(255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(22, 8, 'Nro Comp.', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Fecha y Hora', 1, 0, 'C', true);
    $pdf->Cell(38, 8, 'Cliente', 1, 0, 'C', true);
    $pdf->Cell(22, 8, 'Total', 1, 0, 'C', true);
    $pdf->Cell(28, 8, 'Vendedor', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Tipo Venta', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Estado', 1, 1, 'C', true);

    /* ========= DATOS ========= */
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0);
    $totalVentasRegistradas = 0;

    foreach ($ventas as $venta) {

        $tipoVenta = ($venta->idtipo_venta == 1) ? 'Contado' : 'Crédito';
        $fill = false;

        if ($venta->estado == 0) {
            $pdf->SetTextColor(217, 48, 37);
            $estadoTexto = 'Anulado';
        } else {
            if ($venta->idtipo_venta == 2 && $venta->saldo_restante !== null && (float)$venta->saldo_restante > 0) {
                $pdf->SetTextColor(0);
                $pdf->SetFillColor(255, 243, 176);
                $estadoTexto = 'Pendiente Bs' . number_format((float)$venta->saldo_restante, 2);
                $fill = true;
            } else {
                $pdf->SetTextColor(11, 122, 59);
                $estadoTexto = 'Registrado';
                $totalVentasRegistradas += $venta->total;
            }
        }

        $pdf->Cell(22, 8, $venta->num_comprobante, 1, 0, 'L', $fill);
        $pdf->Cell(30, 8, date('d/m/Y H:i', strtotime($venta->fecha_hora)), 1, 0, 'L', $fill);
        $pdf->Cell(38, 8, utf8_decode(mb_strimwidth($venta->cliente ?? '-', 0, 25, '...')), 1, 0, 'L', $fill);
        $pdf->Cell(22, 8, number_format($venta->total, 2), 1, 0, 'R', $fill);
        $pdf->Cell(28, 8, utf8_decode(mb_strimwidth($venta->vendedor ?? '-', 0, 20, '...')), 1, 0, 'L', $fill);
       $pdf->Cell(20, 8, utf8_decode($tipoVenta), 1, 0, 'L', $fill);


        $pdf->Cell(30, 8, utf8_decode($estadoTexto), 1, 1, 'C', $fill);

        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);
    }

    /* ========= TOTAL ========= */
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, 'Total de ventas registradas: ' . number_format($totalVentasRegistradas, 2), 0, 1, 'R');

    // Descargar
    $pdf->Output('D', 'reporte_general_ventas_' . date('Ymd_His') . '.pdf');
    exit;
}



    public function descargarVentasDetalladasPDF(Request $request)
    {
        $query = Venta::with(['detalles.producto', 'sucursal', 'usuario.persona', 'cliente'])
    ->join('users', 'ventas.idusuario', '=', 'users.id')
    ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')

    // 🔹 SUBCONSULTA: última cuota por venta
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
        if ($request->filled('sucursal') && $request->sucursal !== 'undefined') {
            $query->where('users.idsucursal', $request->sucursal);
            $sucursal = Sucursales::find($request->sucursal);
            $sucursalNombre = $sucursal ? $sucursal->nombre : 'Desconocida';
            $filtros[] = "Sucursal: {$sucursalNombre}";
        }

        if ($request->filled('tipoReporte')) {
            if ($request->tipoReporte === 'dia' && $request->filled('fechaSeleccionada')) {
                $fechaInicio = $request->fechaSeleccionada . ' 00:00:00';
                $fechaFin = $request->fechaSeleccionada . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);
                $filtros[] = "Fecha: {$request->fechaSeleccionada}";
            } else if ($request->tipoReporte === 'mes' && $request->filled('mesSeleccionado')) {
                $mesSeleccionado = $request->mesSeleccionado;
                $fechaInicio = $mesSeleccionado . '-01 00:00:00';
                $fechaFin = date('Y-m-t', strtotime($mesSeleccionado . '-01')) . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);
                $filtros[] = "Mes: " . date('F Y', strtotime($mesSeleccionado . '-01'));
            }
        }

        if ($request->filled('estadoVenta') && $request->estadoVenta !== 'Todos' && $request->estadoVenta !== 'undefined') {
            $query->where('ventas.estado', $request->estadoVenta);
            $filtros[] = "Estado: {$request->estadoVenta}";
        }

        if ($request->filled('idcliente') && $request->idcliente !== 'undefined') {
            $query->where('ventas.idcliente', $request->idcliente);
            $filtros[] = "Cliente: {$request->idcliente}";
        }

        $ventas = $query->orderBy('ventas.fecha_hora', 'desc')->get();

        $pdf = new PDFDetalleVentas();
        $pdf->AliasNbPages();
        $pdf->AddPage();

        /* ========= HEADER AZUL ========= */
        $pdf->SetFillColor(11, 79, 119);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Arial', 'B', 14);

        // Fondo del header (toda la fila)
        $pdf->Cell(0, 14, '', 0, 1, 'L', true);

        // Volver arriba para escribir encima
        $pdf->SetY($pdf->GetY() - 14);
        $pdf->SetX(10);

        // Título
        $pdf->Cell(130, 14, utf8_decode('REPORTE DETALLADO DE VENTAS'), 0, 0, 'L');

        // Logo a la derecha (MISMA FILA)
        $headerY = $pdf->GetY();

$pdf->Image(
    public_path('img/logoPrincipal.png'),
    165,
    $headerY + 2,  // centrado visual
    20
);



        $pdf->Ln(14);

        // Fecha (segunda franja azul)
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, utf8_decode('Fecha de generación: ' . date('d/m/Y H:i')), 0, 1, 'L', true);

        // Línea separadora
        $pdf->Ln(4);
        $pdf->SetDrawColor(11, 79, 119);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(6);

        // Reset colores
        $pdf->SetTextColor(0);

        $clienteNombre = $venta->cliente->nombre ?? 'S/N';
        $clienteRecortado = mb_strimwidth(utf8_decode($clienteNombre), 0, 30, '...');


        // Mostrar filtros aplicados
        if (count($filtros) > 0) {
            $pdf->SetFont('Arial', '', 9);
            foreach ($filtros as $filtro) {
                $pdf->Cell(0, 5, utf8_decode($filtro), 0, 1);
            }
            $pdf->Ln(3);
        }

        $totalVentasRegistradas = 0;

        foreach ($ventas as $venta) {

                // -------- TIPO DE VENTA --------
                $tipoVenta = ($venta->idtipo_venta == 1) ? 'Contado' : 'Crédito';

                // ✅ CLIENTE
                $clienteNombre = $venta->cliente->nombre ?? 'S/N';
                $clienteRecortado = mb_strimwidth(utf8_decode($clienteNombre), 0, 30, '...');

                // ✅ SALDO (YA VIENE DEL QUERY)
                $saldoRestante = $venta->saldo_restante;

                // -------- COLOR ANULADO --------
                if ($venta->estado == 0) {
                    $pdf->SetTextColor(255, 0, 0);
                } else {
                    $pdf->SetTextColor(0);
                    $totalVentasRegistradas += $venta->total;
                }

                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetFillColor(230, 230, 230);
                $pdf->Cell(0, 7, utf8_decode("Venta Nro: {$venta->num_comprobante}"), 0, 1, 'L', true);

                $pdf->SetFont('Arial', '', 10);

                // -------- ESTADO TEXTO --------
                if ($venta->estado == 0) {
                    $estadoTexto = 'Anulado';
                } else {
                    if (
                        $venta->idtipo_venta == 2 &&
                        $saldoRestante !== null &&
                        (float)$saldoRestante > 0
                    ) {
                        $estadoTexto = 'Saldo Faltante Bs ' . number_format((float)$saldoRestante, 2);
                    } else {
                        $estadoTexto = 'Registrado';
                    }
                }

                $pdf->Cell(60, 6, 'Fecha: ' . date('d/m/Y H:i', strtotime($venta->fecha_hora)), 0, 0);
                $pdf->Cell(60, 6, 'Vendedor: ' . ($venta->usuario->persona->nombre ?? ''), 0, 1);
                $pdf->Cell(60, 6, 'Sucursal: ' . utf8_decode($venta->sucursal_nombre), 0, 1);
                $pdf->Cell(60, 6, 'Cliente: ' . $clienteRecortado, 0, 1);
                $pdf->Cell(60, 6, 'Importe: ' . number_format($venta->total, 2), 0, 1);
                $pdf->Cell(60, 6, 'Tipo de venta: ' . utf8_decode($tipoVenta), 0, 1);
                $pdf->Cell(60, 6, 'Estado: ' . utf8_decode($estadoTexto), 0, 1);


            $pdf->Ln(2);

            // Cabecera del detalle
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(11, 79, 119);
            $pdf->SetTextColor(255);
            $pdf->Cell(90, 7, 'Producto', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Cant.', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Precio  ', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Descuento  ', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Subtotal  ', 1, 1, 'C', true);

            // Detalles
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0);
            foreach ($venta->detalles as $d) {
                $subtotal = ($d->precio * $d->cantidad) - $d->descuento;
                $nombreProducto = $d->producto->nombre ?? '-';
                $nombreRecortado = mb_strimwidth(utf8_decode($nombreProducto), 0, 45, '...');
                $pdf->Cell(90, 6, $nombreRecortado, 1);
                $pdf->Cell(20, 6, $d->cantidad, 1, 0, 'C');
                $pdf->Cell(25, 6, number_format($d->precio  , 2), 1, 0, 'R');
                $pdf->Cell(25, 6, number_format($d->descuento  , 2), 1, 0, 'R');
                $pdf->Cell(30, 6, number_format($subtotal  , 2), 1, 1, 'R');
            }
            $pdf->Ln(5);
            $pdf->SetTextColor(0); // Restablecer color
        }

        // Total de ventas registradas
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Total de ventas: ' . number_format($totalVentasRegistradas, 2)), 0, 1, 'R');

        $pdf->Output('D', 'ventas_detalladas_' . date('Ymd_His') . '.pdf');
        exit;
    }

    public function exportarVentasGeneralExcel(Request $request)
    {
        $filters = $request->only([
            'sucursal',
            'tipoReporte',
            'fechaSeleccionada',
            'mesSeleccionado',
            'estadoVenta',
            'idcliente'
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
            'idcliente'
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
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}
class PDFDetalleVentas extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}