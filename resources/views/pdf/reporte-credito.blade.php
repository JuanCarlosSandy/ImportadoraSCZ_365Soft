<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta de Créditos</title>

    <style>
        @page {
            margin: 40px 40px;
        }

        body {
            margin: 0;
            padding: 0;
        }

        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }


        body {
            font-family: Arial, sans-serif;
            font-size: 16px;
            color: #333;
        }

        /* ---------------- CABECERA ---------------- */
        .header {
            background: #0b4f77;
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-radius: 5px;
        }

        .logo img {
            height: 90px;
            /* 🔥 LOGO MÁS GRANDE */
        }

        .empresa-info {
            margin-top: 8px;
            font-size: 15px;
            line-height: 16px;
            color: black;
        }

        .title-box {
            text-align: right;
            font-size: 17px;
            font-weight: bold;
            color: black;
        }

        .title-details {
            font-size: 15px;
            line-height: 15px;
            margin-top: 5px;
            color: black;
        }

        /* separador */
        .divider {
            width: 100%;
            height: 2px;
            background: #0b4f77;
            margin: 8px 0 8px 0;
        }

        /* ---------------- TABLAS ---------------- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 15px;
        }

        th {
            background: #0b4f77;
            color: white;
            padding: 7px;
            text-align: center;
            border: 1px solid #0b4f77;
            font-size: 15px;
        }


        td {
            border: 1px solid #ccc;
            padding: 4px;
            word-break: break-word;
            white-space: normal;
        }

        /* ---------------- PANEL DE MES ---------------- */
        .mes-panel {
            width: 100%;
            background: #e6e6e6;
            border: 1px solid #bbb;
            border-top: none;
            margin-top: 0;
        }

        .mes-panel-row {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .mes-nombre {
            text-transform: uppercase;
        }

        .mes-totales {
            text-align: right;
            font-size: 15px;
            line-height: 12px;
        }

        /* ---------------- TEXTO ROJO ---------------- */
        .rojo {
            color: #d93025 !important;
            font-weight: bold;
        }

        /* ---------------- TEXTO VERDE (Saldo a Favor) ---------------- */
        .verde {
            color: #28a745 !important;
            font-weight: bold;
        }

        .amarillo {
            background-color: #fff3b0 !important;
            font-weight: bold;
        }
    </style>

</head>

<body>
    <table width="100%" cellspacing="0" cellpadding="0" style="background:#ffffff !important;
           color:#000000 !important;
           padding:0;
           border-collapse: collapse;
           border:0 !important;">
        <tr style="border:0 !important;">

            <!-- Logo e información de empresa -->
            <td width="50%" valign="middle"
                style="border:0 !important; padding:10px 15px 0px 15px; color:#000000 !important;">

                <div style="display:flex; align-items:center; border:0 !important;">

                    <!-- Logo compacto -->
                    <img src="{{ public_path('./../public/img/logoPrincipal.png') }}"
                        style="height:60px; margin-top: 5px;">

                    <!-- Texto, sin saltos hacia abajo -->
                    <div style="
    font-size:14px;
    line-height:15px;
    margin-left:100px;
    margin-top:0px;
    color:#000000 !important;
">
                        <strong>{{ $empresa->nombre ?? 'Nombre de la Empresa' }}</strong><br>
                        {{ $empresa->direccion ?? 'Dirección de la Empresa' }}<br>
                        {{ $empresa->telefono ?? 'Teléfono de la Empresa' }}
                    </div>


                </div>

            </td>

            <!-- Información del cliente -->
            <td width="50%" valign="top" align="right"
                style="border:0 !important; padding:10px 15px 0px 15px; color:#000000 !important;">

                <div style="font-size:18px; font-weight:bold; margin-bottom:2px; color:#000000 !important;">
                    ESTADO DE CUENTA
                </div>

                <div style="font-size:15px; line-height:16px; color:#000000 !important;">
                    Fecha emisión: <strong>{{ $fecha_generacion }}</strong><br>
                    Cliente: <strong>{{ $cliente->nombre }}</strong><br>
                    CI/NIT: {{ $cliente->num_documento }}<br>

                    @if(!empty($cliente->telefono))
                        Teléfono: {{ $cliente->telefono }}<br>
                    @endif

                    @if(!empty($cliente->direccion))
                        Dirección: {{ $cliente->direccion }}<br>
                    @endif

                    @if(isset($saldoFavorCliente) && $saldoFavorCliente > 0)
                        <span style="color:#28a745; font-weight:bold;">
                            Saldo a Favor: {{ number_format($saldoFavorCliente, 2) }}
                        </span><br>
                    @endif
                </div>

            </td>

        </tr>
    </table>

    <!-- Divider -->
    <div style="width:100%; height:2px; background:#0b4f77; margin:8px 0 5px;"></div>
    <!-- ================= CONTENIDO ================= -->
    @foreach($movimientos as $mes => $dataMes)

        <!-- TABLA DE MOVIMIENTOS -->
        <table>
            <thead>
                <tr>
                    <th style="width: 17%">Fecha</th>
                    <th style="width: 8%">Tipo</th>
                    <th style="width: 22%">Descripción</th>
                    <th style="width: 15%">Cuenta</th>
                    <th style="width: 18%">Importe</th>
                    <th style="width: 20%">Total</th>
                </tr>
            </thead>


            <tbody>
                @foreach($dataMes['items'] as $m)

                    @php
                        $esCobro = $m['tipo'] === 'COBRAR';
                        $clase = $esCobro ? 'rojo' : '';

                        // si viene desde PHP con color amarilla
                        if (!empty($m['color']) && $m['color'] === 'yellow') {
                            $clase = 'amarillo'; // class nueva
                        }

                        // 🔥 Si es saldo a favor, cambiar a verde
                        if (!empty($m['es_saldo_favor'])) {
                            $clase = 'verde';
                        }

                        $importe = $esCobro
                            ? '-' . number_format((float) $m['importe'], 2)
                            : number_format((float) $m['importe'], 2);


                        $desc = strtoupper(trim($m['descripcion']));
                        $desc = str_replace('CLIENTES', '', $desc);
                        $desc = trim($desc);
                        $comprobante = $m['num_comprobante'] ?? null;
                        $comprobante = $m['num_comprobante'] ?? null;

                        // 🔥 Calcular saldo para mostrar (nunca negativo en la columna Total)
                        /*$saldoMostrar = $m['saldo'];
                        if ($saldoMostrar !== null && (float)$saldoMostrar < 0) {
                            $saldoMostrar = 0; // Si es negativo, mostrar 0 (el saldo a favor se muestra aparte)
                        }*/
                    @endphp

                    <tr>
                        <td class="{{ $clase }}">{{ date('d/m/Y H:i:s', strtotime($m['fecha'])) }}</td>

                        <td class="{{ $clase }}" style="text-align:center;">
                            {{ $m['tipo'] }}
                        </td>

                        <td class="{{ $clase }}">
                            CLIENTES
                            <br>
                            <span style="font-size:11px;">
                                {{ $desc }}
                                @if($comprobante)
                                    <br>N° {{ $comprobante }}
                                @endif
                            </span>
                        </td>

                        <td class="{{ $clase }}">
                            {{ $m['cuenta'] ?? '-' }}
                            @if(!empty($m['banco']))
                                <br><span style="font-size:11px;">{{ $m['banco'] }}</span>
                            @endif
                        </td>

                        <td class="{{ $clase }}">
                            {{-- 🔥 Si se aplicó saldo a favor, mostrar el cálculo --}}
                            @if(!empty($m['saldo_favor_aplicado']))
                                <span style="font-size:10px; color:#666;">
                                    {{ number_format((float) $m['saldo_original'], 2) }}
                                </span>
                                <br>
                                <span style="font-size:10px; color:#28a745;">
                                    - {{ number_format((float) $m['saldo_favor_aplicado'], 2) }} (S.F.)
                                </span>
                                <br>
                                <strong>=
                                    {{ number_format((float) $m['saldo_original'] - (float) $m['saldo_favor_aplicado'], 2) }}</strong>
                            @else
                                {{ $importe }}
                            @endif
                        </td>

                        <td class="{{ $clase }}" style="text-align:right;">

                            @php
                                $total = (float) $m['total_acumulado'];
                            @endphp

                            @if($total < 0)
                                {{-- 🔥 SALDO A FAVOR --}}
                                <span style="color:#28a745; font-weight:bold;">
                                    0.00
                                </span>
                                <br>
                                <span style="font-size:11px; color:#28a745;">
                                    Saldo a favor: {{ number_format(abs($total), 2) }}
                                </span>
                            @else
                                {{ number_format($total, 2) }}
                            @endif

                        </td>
                    </tr>

                @endforeach
            </tbody>
        </table>

        <table style="width:100%; border-collapse: collapse; margin-top:0;">
            <tr>
                <!-- Columna izquierda: Mes -->
                <td colspan="3" style="
                                    background:#c2c2c2;
                                    font-weight:bold;
                                    text-transform:uppercase;
                                    padding:6px;
                                    border-left:1px solid #bbb;
                                    border-bottom:1px solid #bbb;
                                ">
                    {{ strtoupper($mes) }}
                </td>

                <!-- Columna derecha: Totales -->
                <td colspan="3" style="
                                    background:#c2c2c2;
                                    padding:6px;
                                    text-align:right;
                                    border-right:1px solid #bbb;
                                    border-bottom:1px solid #bbb;
                                    font-size:15px;
                                ">
                    Ventas: {{ number_format($dataMes['totalVenta'], 2) }}
                    | Saldo Restante: {{ number_format($dataMes['totalCobros'], 2) }}
                </td>
            </tr>
        </table>

    @endforeach


    {{-- 🔥 Mostrar saldo a favor del cliente al final del reporte --}}
    @if(isset($saldoFavorCliente))
        <table style="width:100%; border-collapse: collapse; margin-top:15px;">
            <tr>
                <td colspan="6" style="
                        background:#d4edda;
                        padding:10px;
                        text-align:center;
                        border:2px solid #28a745;
                        font-size:16px;
                    ">
                    <span style="color:#28a745; font-weight:bold;">
                        SALDO A FAVOR DEL CLIENTE: {{ number_format($saldoFavorCliente, 2) }}
                    </span>
                </td>
            </tr>
        </table>
    @endif

</body>

</html>