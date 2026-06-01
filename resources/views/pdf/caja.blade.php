<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Caja</title>

    <style>
        @page {
            size: letter portrait;
            margin: 10mm 8mm 18mm 8mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #2d3748;
            line-height: 1.2;
        }

        * {
            box-sizing: border-box;
        }

        /* ========================= */
        /* HEADER */
        /* ========================= */

        .header {
            width: 100%;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .header-table {
            width: 100%;
            border: none;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        .logo-section {
            width: 18%;
            text-align: left;
        }

        .logo {
            height: 45px;
        }

        .title-section {
            width: 82%;
            text-align: center;
        }

        .report-title {
            margin: 0;
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .report-subtitle {
            margin-top: 4px;
            font-size: 8px;
            color: #64748b;
        }

        .report-subtitle span {
            font-weight: bold;
            color: #334155;
        }

        /* ========================= */
        /* SECTION TITLES */
        /* ========================= */

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 18px;
            margin-bottom: 8px;
            padding-left: 8px;
            border-left: 4px solid #2563eb;
        }

        /* ========================= */
        /* SUMMARY TABLE */
        /* ========================= */

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 18px;
            border: 1px solid #dbe2ea;
        }

        .summary-table th {
            width: 25%;
            background: #f8fafc;
            color: #334155;
            font-size: 10px;
            text-align: left;
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-table td {
            width: 25%;
            padding: 6px;
            font-weight: bold;
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
        }

        /* ========================= */
        /* DETAIL TABLE */
        /* ========================= */

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        .detail-table thead th {
            background: #1e293b;
            color: white;
            font-size: 8px;
            font-weight: bold;
            padding: 6px 5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            border: none;
        }

        .detail-table tbody td {
            padding: 4px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .detail-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ========================= */
        /* COLORS */
        /* ========================= */

        .text-success {
            color: #16a34a;
            font-weight: bold;
        }

        .text-danger {
            color: #dc2626;
            font-weight: bold;
        }

        .text-warning {
            color: #ea580c;
            font-weight: bold;
        }

        .text-neutral {
            color: #64748b;
            font-weight: bold;
        }

        /* ========================= */
        /* BADGES */
        /* ========================= */

        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }

        .badge-efectivo {
            background: #dcfce7;
            color: #166534;
        }

        .badge-qr {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-compuesto {
            background: #ffedd5;
            color: #c2410c;
        }

        .badge-otros {
            background: #e2e8f0;
            color: #475569;
        }

        tr {
            page-break-inside: avoid;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;

            height: 20px;

            font-size: 8px;
            color: #64748b;

            border-top: 1px solid #cbd5e1;

            padding-top: 5px;
        }

        .footer .left {
            float: left;
        }

        .footer .right {
            float: right;
        }

        .pagenum:before {
            content: counter(page);
        }

        .pagecount:before {
            content: counter(pages);
        }
    </style>
</head>

<body>

    <!-- ========================= -->
    <!-- HEADER -->
    <!-- ========================= -->

    <div class="header">

        <table class="header-table">
            <tr>

                <!-- LOGO -->
                <td class="logo-section">
                    <img src="{{ public_path('img/logoPrincipal.png') }}" class="logo">
                </td>

                <!-- TITULO -->
                <td class="title-section">

                    <h1 class="report-title">
                        REPORTE DE CAJA
                    </h1>

                    <div class="report-subtitle">
                        <span>Fecha Generación:</span>
                        {{ now()->format('d/m/Y H:i') }}
                        &nbsp;&nbsp;&nbsp;&nbsp;

                        <span>Tipo Reporte:</span>
                        {{ ucfirst($tipo) }}
                    </div>

                </td>

            </tr>
        </table>

    </div>

    <!-- ========================= -->
    <!-- RESUMEN -->
    <!-- ========================= -->

    <div class="section-title">
        Resumen de Caja
    </div>

    <table class="summary-table">

        <tr>
            <th>Fecha Apertura</th>
            <td>
                {{ \Carbon\Carbon::parse($resumenCaja['fechaApertura'])->format('d/m/Y H:i') }}
            </td>

            <th>Fecha Cierre</th>
            <td>
                @if($resumenCaja['fechaCierre'])
                    {{ \Carbon\Carbon::parse($resumenCaja['fechaCierre'])->format('d/m/Y H:i') }}
                @else
                    -
                @endif
            </td>
        </tr>

        <tr>
            <th>Saldo Inicial</th>
            <td>
                Bs {{ number_format($resumenCaja['saldoInicial'], 2) }}
            </td>

            <th>Cobros Efectivo</th>
            <td class="text-success">
                Bs {{ number_format($resumenCaja['ventasContado'], 2) }}
            </td>
        </tr>

        <tr>
            <th>Cobros QR</th>
            <td class="text-success">
                Bs {{ number_format($resumenCaja['ventasQR'], 2) }}
            </td>

            <th>Cobros Totales</th>
            <td class="text-success">
                Bs {{ number_format($resumenCaja['saldototalventas'], 2) }}
            </td>
        </tr>

        <tr>
            <th>Depósitos Extras</th>
            <td>
                Bs {{ number_format($resumenCaja['depositos'], 2) }}
            </td>

            <th>Salidas Extras</th>
            <td class="text-danger">
                Bs {{ number_format($resumenCaja['salidas'], 2) }}
            </td>
        </tr>

        <tr>
            <th>Saldo Caja</th>
            <td class="text-success">
                Bs {{ number_format($resumenCaja['saldoCaja'], 2) }}
            </td>

            <th>Monto Arqueo</th>
            <td>
                Bs {{ number_format($resumenCaja['monto_arqueo'], 2) }}
            </td>
        </tr>

        <tr>
            <th>Saldo Faltante</th>
            <td class="text-danger">
                Bs {{ number_format($resumenCaja['saldoFaltante'], 2) }}
            </td>

            <th>Saldo Sobrante</th>
            <td class="text-success">
                Bs {{ number_format($resumenCaja['saldoSobrante'], 2) }}
            </td>
        </tr>

    </table>

    <!-- ========================= -->
    <!-- DETALLE -->
    <!-- ========================= -->

    <div class="section-title">
        Detalle de Movimientos
    </div>

    <table class="detail-table">

        <thead>
            <tr>
                <th style="width: 15%;">Fecha / Hora</th>
                <th style="width: 28%;">Detalle</th>
                <th style="width: 25%;">Productos Vendidos</th>
                <th style="width: 13%;">Tipo Pago</th>
                <th style="width: 12%;" class="text-right">Monto</th>
                <th style="width: 12%;" class="text-right">Saldo</th>
            </tr>
        </thead>

        <tbody>

            @foreach($historial as $item)

                <tr>

                    <!-- FECHA -->
                    <td>
                        {{ \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y H:i') }}
                    </td>

                    <!-- DETALLE -->
                    <td style="
                            color:
                            @if(
                                    stripos($item['detalle'], 'egreso') !== false ||
                                    stripos($item['detalle'], 'gasto') !== false
                                )
                                    #dc2626
                            @else
                                #334155
                            @endif
                        ">
                        {{ $item['detalle'] }}
                    </td>

                    <!-- PRODUCTOS -->
                    <td style="font-size: 8px; color: #475569; white-space: pre-line;">
                        {{ $item['productos'] ?? '-' }}
                    </td>

                    <!-- TIPO PAGO -->
                    <td class="text-center">

                        @php
                            $badgeClass = 'badge-otros';

                            if (strtolower($item['tipo_pago']) === 'efectivo') {
                                $badgeClass = 'badge-efectivo';
                            }

                            if (strtolower($item['tipo_pago']) === 'qr') {
                                $badgeClass = 'badge-qr';
                            }

                            if (strtolower($item['tipo_pago']) === 'compuesto') {
                                $badgeClass = 'badge-compuesto';
                            }
                        @endphp

                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst($item['tipo_pago']) }}
                        </span>

                    </td>

                    <!-- MONTO -->
                    <td class="text-right">

                        @if(
                                $item['monto'] < 0 ||
                                stripos($item['detalle'], 'Anulación de venta') !== false
                            )

                            <span class="text-danger">
                                {{ number_format($item['monto'], 2) }}
                            </span>

                        @else

                            <span class="text-success">
                                {{ number_format($item['monto'], 2) }}
                            </span>

                        @endif

                    </td>

                    <!-- SALDO -->
                    <td class="text-right">

                        @if(stripos($item['detalle'], 'Anulación de venta') !== false)

                            <span class="text-neutral">
                                {{ number_format($item['saldo_actual'], 2) }}
                            </span>

                        @elseif(
                                stripos($item['detalle'], 'egreso') !== false ||
                                stripos($item['detalle'], 'gasto') !== false
                            )
                                <span class="text-danger">
                                    {{ number_format($item['saldo_actual'], 2) }}
                                </span>
                          @else                        <span class="text-success">
                            {{ number_format($item['saldo_actual'], 2) }}
                        </span>

                    @endif

                    </td>

                  </tr>

              @endforeach
    
        </tbody>

    </table>
 <div cla   ss="footer">
 
       <div class="left">
            Reporte de Caja generado por el sistema
         </div>


</div>

</body>

</html>