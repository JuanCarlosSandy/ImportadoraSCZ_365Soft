<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <style>

        /* ==========================================
           CONFIGURACIÓN DE PÁGINA
        ========================================== */

        @page {
            margin: 20px 25px 45px 25px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
        }

        /* ==========================================
           ENCABEZADO
        ========================================== */

        .header {
            width: 100%;
            border-bottom: 2px solid #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 7px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .td-logo {
            width: 15%;
            text-align: left;
        }

        .logo {
            width: 60px;
            height: auto;
        }

        .title {
            width: 65%;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }

        .fecha-gen {
            width: 20%;
            font-size: 9px;
            text-align: right;
            color: #666;
        }

        /* ==========================================
           INFORMACIÓN DEL CONTROL
        ========================================== */

        .info {
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .info strong {
            color: #2c3e50;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table td {
            width: 50%;
            border: none;
            padding: 4px 0;
            text-align: left;
            vertical-align: middle;
            line-height: 1.3;
        }

        /* ==========================================
           ESTADOS
        ========================================== */

        .estado {
            display: inline-block;
            padding: 3px 5px;
            font-size: 8px;
            line-height: 11px;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
        }

        .pendiente {
            background-color: #f39c12;
            color: #ffffff;
        }

        .verificado {
            background-color: #27ae60;
            color: #ffffff;
        }

        .anulado {
            background-color: #e74c3c;
            color: #ffffff;
        }

        .sin-diferencia {
            background-color: #3498db;
            color: #ffffff;
        }

        /* ==========================================
           TABLA DE PRODUCTOS
        ========================================== */

        .tabla-detalle {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .tabla-detalle thead {
            display: table-header-group;
        }

        .tabla-detalle tbody {
            display: table-row-group;
        }

        .tabla-detalle th {
            background-color: #34495e;
            color: #ffffff;
            padding: 6px 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            line-height: 12px;
            border: 1px solid #34495e;
        }

        .tabla-detalle td {
            padding: 5px 4px;
            font-size: 9px;
            line-height: 12px;
            border-bottom: 1px solid #dddddd;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .tabla-detalle tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .tabla-detalle tr {
            page-break-inside: avoid;
        }

        .tabla-detalle th,
        .tabla-detalle td {
            page-break-inside: avoid;
        }

        /* ==========================================
           ALINEACIONES
        ========================================== */

        .text-left {
            text-align: left !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-green {
            color: #16833b;
            font-weight: bold;
        }

        .text-red {
            color: #dc2626;
            font-weight: bold;
        }

        /* ==========================================
           SALTO DE PÁGINA MANUAL
        ========================================== */

        .page-break {
            page-break-after: always;
        }

        /* ==========================================
           ENCABEZADO PÁGINAS ADICIONALES
        ========================================== */

        .header-secundario {
            width: 100%;
            border-bottom: 1px solid #2c3e50;
            padding-bottom: 7px;
            margin-bottom: 10px;
        }

        .header-secundario-titulo {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            color: #2c3e50;
        }

        .header-secundario-info {
            font-size: 9px;
            text-align: center;
            color: #666666;
            margin-top: 4px;
        }

        /* ==========================================
           PIE DE PÁGINA
        ========================================== */

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 20px;
            border-top: 1px solid #cccccc;
            font-size: 9px;
            color: #666666;
            padding-top: 5px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            padding: 0;
            border: none;
        }

        .footer-left {
            text-align: left;
        }

        .footer-right {
            text-align: right;
        }

        .page-number:before {
            content: counter(page);
        }

    </style>
</head>

<body>

    {{-- ==========================================
         PIE DE PÁGINA FIJO
    ========================================== --}}

    <div class="footer">

        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Reporte generado por el sistema
                </td>

                <td class="footer-right">
                    Página <span class="page-number"></span>
                </td>
            </tr>
        </table>

    </div>

    {{-- ==========================================
         DIVIDIR PRODUCTOS POR PÁGINA
    ========================================== --}}

    @php

        // Cantidad máxima de productos por bloque.
        // Se deja espacio para nombres de varias líneas.

        $productosPorPagina = 28;

        $paginas = $control->detalles->chunk(
            $productosPorPagina
        );

        $mostrarStock = (int) $rolUsuario === 4;

    @endphp

    @foreach($paginas as $indicePagina => $detalles)

        {{-- ======================================
             ENCABEZADO PRINCIPAL
        ====================================== --}}

        @if($indicePagina == 0)

            <div class="header">

                <table class="header-table">
                    <tr>

                        <td class="td-logo">

                            @if(file_exists(public_path('img/logoPrincipal.png')))

                                <img
                                    src="{{ public_path('img/logoPrincipal.png') }}"
                                    class="logo"
                                >

                            @endif

                        </td>

                        <td class="title">
                            DETALLE DE CONTROL DE INVENTARIO
                        </td>

                        <td class="fecha-gen">
                            Generado:<br>
                            {{ date('d/m/Y H:i') }}
                        </td>

                    </tr>
                </table>

            </div>

            {{-- ==================================
                 INFORMACIÓN GENERAL
            ================================== --}}

            <div class="info">

                <table class="info-table">

                    <tr>

                        <td>
                            <strong>Almacén:</strong>

                            {{ $control->almacen->nombre_almacen }}
                        </td>

                        <td>
                            <strong>Responsable:</strong>

                            {{ $control->usuario->usuario }}
                        </td>

                    </tr>

                    <tr>

                        <td>
                            <strong>Fecha:</strong>

                            {{ $control->fechahora }}
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

        @else

            {{-- ==================================
                 ENCABEZADO PÁGINAS SIGUIENTES
            ================================== --}}

            <div class="header-secundario">

                <div class="header-secundario-titulo">

                    DETALLE DE CONTROL DE INVENTARIO

                </div>

                <div class="header-secundario-info">

                    Control N° {{ $control->id }}

                    |

                    Almacén:
                    {{ $control->almacen->nombre_almacen }}

                    |

                    Continuación

                </div>

            </div>

        @endif

        {{-- ======================================
             TABLA DE PRODUCTOS
        ====================================== --}}

        <table class="tabla-detalle">

            <thead>

                <tr>

                    @if($mostrarStock)

                        <th style="width: 12%;">
                            Código
                        </th>

                        <th style="width: 32%;">
                            Artículo
                        </th>

                        <th style="width: 10%;">
                            Stock<br>Sistema
                        </th>

                        <th style="width: 10%;">
                            Stock<br>Actual
                        </th>

                        <th style="width: 10%;">
                            Stock<br>Físico
                        </th>

                        <th style="width: 10%;">
                            Diferencia
                        </th>

                        <th style="width: 16%;">
                            Estado
                        </th>

                    @else

                        <th style="width: 15%;">
                            Código
                        </th>

                        <th style="width: 48%;">
                            Artículo
                        </th>

                        <th style="width: 17%;">
                            Stock Físico
                        </th>

                        <th style="width: 20%;">
                            Estado
                        </th>

                    @endif

                </tr>

            </thead>

            <tbody>

                @foreach($detalles as $d)

                    @php

                        $diferencia =
                            $d->stockfisico -
                            $d->stocksistema;

                    @endphp

                    <tr>

                        {{-- CÓDIGO --}}

                        <td class="text-left">

                            {{ $d->articulo->codigo }}

                        </td>

                        {{-- ARTÍCULO --}}

                        <td class="text-left">

                            {{ $d->articulo->nombre }}

                        </td>

                        {{-- STOCK SISTEMA Y ACTUAL --}}

                        @if($mostrarStock)

                            <td>

                                {{ $d->stocksistema }}

                            </td>

                            <td>

                                {{ $d->stock_actual }}

                            </td>

                        @endif

                        {{-- STOCK FÍSICO --}}

                        <td>

                            {{ $d->stockfisico }}

                        </td>

                        {{-- DIFERENCIA --}}

                        @if($mostrarStock)

                            <td>

                                <strong class="{{ $diferencia >= 0 ? 'text-green' : 'text-red' }}">

                                    {{ $diferencia }}

                                </strong>

                            </td>

                        @endif

                        {{-- ESTADO --}}

                        <td>

                            @if($d->estado == 1)

                                <span class="estado pendiente">

                                    NO AJUSTADO

                                </span>

                            @elseif($d->estado == 2)

                                <span class="estado verificado">

                                    VERIFICADO

                                </span>

                            @elseif($d->estado == 3)

                                <span class="estado sin-diferencia">

                                    SIN DIFERENCIA

                                </span>

                            @else

                                <span class="estado anulado">

                                    ANULADO

                                </span>

                            @endif

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

        {{-- ======================================
             SALTO DE PÁGINA
             NO GENERAR SALTO EN EL ÚLTIMO BLOQUE
        ====================================== --}}

        @if(!$loop->last)

            <div class="page-break"></div>

        @endif

    @endforeach

</body>

</html>