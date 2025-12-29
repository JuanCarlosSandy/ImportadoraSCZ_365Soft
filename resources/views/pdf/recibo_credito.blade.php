<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Cuota</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            line-height: 1.6;
            color: #000;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
        }
        .container {
            border: 1px solid #000;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin-bottom: 10px;
        }
        .details {
            margin-bottom: 30px;
            border: 1px solid #000;
            padding: 15px;
        }
        .details p {
            margin: 5px 0;
        }
        .section-title {
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .total {
            font-weight: bold;
            text-align: right;
            font-size: 1.1em;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recibo de Cuota</h1>
            <p>Fecha: {{ date('d/m/Y') }}</p>
        </div>

        <div class="details">
            <p><strong>Cliente:</strong> {{ $cliente->nombre }}</p>
            <p><strong>Número de Venta:</strong> {{ $venta->num_comprobante ?? 'N/A' }}</p>
            <p><strong>Fecha de Venta:</strong> {{ $venta->fecha_hora ? \Carbon\Carbon::parse($venta->fecha_hora)->format('d/m/Y') : 'N/A' }}</p>
            <p><strong>Número de Cuota:</strong> {{ $numeroCuotaActual }} de {{ $totalCuotas }}</p>
        </div>

        <h3 class="section-title">Detalle de Pago Cuota N° {{ $numeroCuotaActual }}</h3>

        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monto de la Cuota</td>
                    <td>{{ number_format($cuota->precio_cuota, 2) }}</td>
                </tr>
                <tr>
                    <td>Fecha de Pago</td>
                    <td>{{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Estado</td>
                    <td>{{ $cuota->estado }}</td>
                </tr>
                @if($cuota->fecha_cancelado)
                <tr>
                    <td>Fecha de Cancelación</td>
                    <td>{{ \Carbon\Carbon::parse($cuota->fecha_cancelado)->format('d/m/Y') }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <p class="total">Total de cuotas restantes: {{ $cuotasRestantes->count() }}</p>
    </div>
</body>
</html>