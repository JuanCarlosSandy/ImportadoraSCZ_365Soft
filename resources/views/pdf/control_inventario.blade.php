<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }

        /* 🔷 HEADER */
        .header {
            width: 100%;
            border-bottom: 2px solid #2c3e50;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
        }

        .td-logo {
            padding-left: 0;
        }

        .logo {
            width: 60px;
            display: block;
            margin-left: -80px;
            /* 🔥 AJUSTA ESTE VALOR */
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .fecha-gen {
            font-size: 10px;
            text-align: right;
            color: #666;
        }

        /* 🔷 INFO */
        .info {
            margin-top: 10px;
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .info strong {
            color: #2c3e50;
        }

        .info table td {
            padding: 3px 0;
        }

        .info-table {
            width: 100%;
            border: none;
            margin-top: 5px;
        }

        .info-table td {
            border: none !important;
            /* 🔥 quita líneas */
            background: transparent !important;
            /* 🔥 quita fondo */
            padding: 3px 0;
            text-align: left;
        }

        .info-table tr {
            background: transparent !important;
        }

        /* 🔷 BADGE ESTADO */
        .estado {
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: bold;
        }

        .pendiente {
            background: #f39c12;
            color: #fff;
        }

        .verificado {
            background: #27ae60;
            color: #fff;
        }

        .anulado {
            background: #e74c3c;
            color: #fff;
        }

        /* 🔷 RESUMEN */
        .resumen {
            margin: 10px 0;
        }

        .box {
            display: inline-block;
            width: 23%;
            text-align: center;
            padding: 6px;
            border-radius: 6px;
            font-weight: bold;
            color: #fff;
        }

        .gray {
            background: #7f8c8d;
        }

        .green {
            background: #27ae60;
        }

        .yellow {
            background: #f1c40f;
            color: #000;
        }

        .red {
            background: #e74c3c;
        }

        /* 🔷 TABLA */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #34495e;
            color: white;
            padding: 6px;
            font-size: 11px;
        }

        td {
            padding: 5px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        /* 🔷 FOOTER */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
        }

        .footer .left {
            float: left;
        }

        .footer .right {
            float: right;
        }

        .page-number:before {
            content: counter(page);
        }
    </style>
</head>

<body>

    <!-- 🔷 HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 20%;">
                    <img src="{{ public_path('img/logoPrincipal.png') }}" class="logo">
                </td>

                <td style="width: 60%;" class="title">
                    DETALLE DE CONTROL DE INVENTARIO
                </td>

                <td style="width: 20%;" class="fecha-gen">
                    Generado: {{ date('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- 🔷 INFO -->
    <div class="info">
        <table class="info-table">
            <tr>
                <td>
                    <strong>Almacén:</strong> {{ $control->almacen->nombre_almacen }}
                </td>
                <td>
                    <strong>Responsable:</strong> {{ $control->usuario->usuario }}
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Fecha:</strong> {{ $control->fechahora }}
                </td>
                <td>
                    <strong>Estado:</strong>
                    <span class="estado {{ $estadoGeneral == 'AJUSTADO' ? 'verificado' : 'pendiente' }}">
                        {{ $estadoGeneral }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- 🔷 TABLA -->
    <table>
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Artículo</th>
                @if($rolUsuario == 4)
                    <th>Stock Sistema</th>
                    <th>Stock Actual</th>
                @endif
                <th>Stock Físico</th>
                @if($rolUsuario == 4)
                    <th>Diferencia</th>
                @endif
                <th>Estado</th>
            </tr>
        </thead>

        <tbody>
            @foreach($control->detalles as $d)
                <tr>
                    <td style="text-align:left;">{{ $d->articulo->codigo }}</td>
                    <td style="text-align:left;">{{ $d->articulo->nombre }}</td>
                    @if($rolUsuario == 4)
                        <td>{{ $d->stocksistema }}</td>
                        <td>{{ $d->stock_actual }}</td>
                    @endif
                    <td>{{ $d->stockfisico }}</td>
                    @if($rolUsuario == 4)
                        <td>
                            <strong style="color: {{ ($d->stockfisico - $d->stocksistema) >= 0 ? 'green' : 'red' }}">
                                {{ $d->stockfisico - $d->stocksistema }}
                            </strong>
                        </td>
                    @endif

                    <td>
                        @if($d->estado == 1)
                            <span class="estado pendiente">NO AJUSTADO</span>
                        @elseif($d->estado == 2)
                            <span class="estado verificado">VERIFICADO</span>
                        @elseif($d->estado == 3)
                            <span class="estado sin diferencia">SIN DIFERENCIA</span>
                        @else
                            <span class="estado anulado">ANULADO</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 🔷 FOOTER -->
    <div class="footer">
        <div class="left">
            Reporte generado por el sistema
        </div>

        <div class="right">
            Página <span class="page-number"></span> </div>
    </div>

</body>

</html>