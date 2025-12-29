<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Compra</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .receipt-container {
            width: 100%;
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            border: 2px solid #000;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .header, .footer {
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
            text-align: left;
        }
        .header p {
            margin: 0;
            font-size: 16px;
            text-align: left;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: left;
        }
        .table thead th {
            background-color: #007bff;
            color: #fff;
        }
        .total {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
        }
        .info-table td {
            padding: 5px 10px;
        }
        h1 {
            font-size: 24px;
            text-align: left;
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        <div class="header">
            <h1>Boleta de Compra</h1>
        </div>

        <div class="content">
            <table class="table table-borderless info-table">
                <tbody>
                    <tr>
                        <td><strong>Proveedor:</strong></td>
                        <td>{{ $ingreso->nombre }}</td>
                        <td><strong>Tipo Comprobante:</strong></td>
                        <td>{{ $ingreso->tipo_comprobante }}</td>
                    </tr>
                    <tr>
                        <td><strong>Serie:</strong></td>
                        <td>{{ $ingreso->serie_comprobante }}</td>
                        <td><strong>Número:</strong></td>
                        <td>{{ $ingreso->num_comprobante }}</td>
                    </tr>
                    <tr>
                        <td><strong>Fecha:</strong></td>
                        <td>{{ \Carbon\Carbon::parse($ingreso->created_at)->format('d/m/Y') }}</td>
                        <td><strong>Total:</strong></td>
                        <td>{{ number_format($ingreso->total, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="content">
            <h1>Detalles</h1>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Artículo</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->articulo }}</td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>{{ number_format($detalle->precio, 2) }}</td>
                        <td>{{ number_format($detalle->cantidad * $detalle->precio, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="content">
            <p class="total">Total: {{ number_format($ingreso->total, 2) }}</p>
        </div>
    </div>

</body>
</html>
