<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo General de Crédito</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Recibo General de Crédito</h1>
        <p>Fecha: {{ date('d/m/Y') }}</p>
    </div>

    <div class="details">
        <p><strong>Cliente:</strong> {{ $cliente->nombre }}</p>
        <p><strong>Número de Venta:</strong> {{ $venta->num_comprobante ?? 'N/A' }}</p>
        <p><strong>Fecha de Venta:</strong> {{ $venta->fecha_hora ? \Carbon\Carbon::parse($venta->fecha_hora)->format('d/m/Y') : 'N/A' }}</p>
        <p><strong>Total de Cuotas:</strong> {{ $totalCuotas }}</p>
    </div>

    <table>
    <thead>
        <tr>
            <th>Descripción</th>
            <th>Monto</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Monto Total del Crédito</td>
            <td>{{ number_format($credito->cuotas->sum('precio_cuota'), 2) }}</td>
        </tr>
        <tr>
            <td>Monto Pagado</td>
            <td>{{ number_format($cuotasPagadas->sum('precio_cuota'), 2) }}</td>
        </tr>
        <tr>
            <td>Saldo Pendiente</td>
            <td>{{ number_format($credito->total, 2) }}</td>
        </tr>
    </tbody>
</table>

    <p class="total">Cuotas Pagadas: {{ $cuotasPagadas->count() }}</p>

    <h3>Todas las Cuotas</h3>
<table>
    <thead>
        <tr>
            <th>Número de Cuota</th>
            <th>Fecha de Pago</th>
            <th>Monto</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($credito->cuotas->sortBy('fecha_pago') as $index => $cuota)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}</td>
            <td>{{ number_format($cuota->precio_cuota, 2) }}</td>
            <td>{{ $cuota->estado === 'Pagado' ? 'Pagado' : 'Pendiente' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p>Total de cuotas: {{ $credito->cuotas->count() }}</p>
<p>Cuotas pagadas: {{ $cuotasPagadas->count() }}</p>
<p>Cuotas pendientes: {{ $credito->cuotas->count() - $cuotasPagadas->count() }}</p>
</body>
</html>