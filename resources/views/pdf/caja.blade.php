<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Caja</title>
    <style>
        @page {
            size: letter portrait;
            /* Tamaño carta, orientación vertical */
            margin: 5mm;
            /* Margen de la página */
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }

        header {
            text-align: center;
            margin-bottom: 10px;
        }

        header h1 {
            font-size: 22px;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        header h4 {
            font-size: 14px;
            margin: 2px 0;
            font-weight: normal;
            color: #555;
        }

        .info-caja {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .info-caja div {
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #34495e;
            color: #fff;
            font-weight: bold;
        }

        tr:nth-child(even) td {
            background-color: #f8f8f8;
        }

        .text-right {
            text-align: right;
        }

        .saldo-negativo {
            color: #e74c3c;
            font-weight: bold;
        }

        .saldo-positivo {
            color: #27ae60;
            font-weight: bold;
        }

        tfoot td {
            font-weight: bold;
            background-color: #ecf0f1;
        }

        /* Para que las filas no se corten entre páginas */
        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

<header>
    <table style="width: 100%; border: none; margin-bottom: 10px;">
        <tr>
            <!-- Columna izquierda: logo -->
            <td style="border: none; text-align: left; vertical-align: middle;">
                <img src="{{ public_path('img/logoPrincipal.png') }}" alt="Logo Empresa" style="height: 100px;">
            </td>

            <!-- Columna derecha: textos -->
            <td style="border: none; text-align: right; vertical-align: middle;">
                <h1 style="margin: 0; color: #2c3e50;">Reporte de Cartera</h1>
                <h4 style="margin: 2px 0; color: #555;">Fecha Apertura: {{ \Carbon\Carbon::parse($caja->fecha_apertura)->format('d/m/Y H:i') }}</h4>
                <h4 style="margin: 2px 0; color: #555;">Tipo de Reporte: {{ ucfirst($tipo) }}</h4>
            </td>
        </tr>
    </table>
</header>

    <table>
        <thead>
            <tr>
                <th>Fecha / Hora</th>
                <th>Detalle</th>
                <th>Tipo de Pago</th>
                <th>Banco</th>
                <th class="text-right">Monto</th>
                <th class="text-right">Saldo Actual</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historial as $item)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y H:i') }}</td>
                                <td style="
                    color: 
                        @if(stripos($item['detalle'], 'egreso') !== false || stripos($item['detalle'], 'gasto') !== false) #e74c3c
                        @else #333
                        @endif
                ">
                                    {{ $item['detalle'] }}
                                </td>
                                <td class="text-center" style="
                        font-weight: bold;
                        color: 
                            @if(strtolower($item['tipo_pago']) === 'efectivo') #27ae60
                            @elseif(strtolower($item['tipo_pago']) === 'banco') #2980b9
                            @else #7f8c8d
                            @endif
                    ">
                                    {{ ucfirst($item['tipo_pago']) }}
                                </td>
                                <td>{{ $item['nombre_banco'] ?? '-' }}</td>
                                <td class="text-right">
                                    @if($item['monto'] < 0)
                                        <span class="saldo-negativo">{{ number_format($item['monto'], 2) }}</span>
                                    @else
                                        <span class="saldo-positivo">{{ number_format($item['monto'], 2) }}</span>
                                    @endif
                                </td>
                              <td class="text-right">
    @if(
        stripos($item['detalle'], 'egreso') !== false ||
        stripos($item['detalle'], 'gasto') !== false ||
        stripos($item['detalle'], 'Anulación de venta crédito') !== false
    )
        <span class="saldo-negativo">
            -{{ number_format(abs($item['saldo_actual']), 2) }}
        </span>
    @else
        <span class="saldo-positivo">
            {{ number_format($item['saldo_actual'], 2) }}
        </span>
    @endif
</td>

                            </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>