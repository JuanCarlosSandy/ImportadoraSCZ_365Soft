<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de Ingreso</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nota de Ingreso</h1>
        <p>Fecha: {{ $ingreso->created_at->format('d/m/Y') }}</p>
        <p>Número: {{ $ingreso->num_comprobante }}</p>
    </div>

    <div class="details">
        <p><strong>Proveedor:</strong> {{ $ingreso->nombre }}</p>
        <p><strong>RUC/DNI:</strong> {{ $ingreso->num_documento }}</p>
        <p><strong>Dirección:</strong> {{ $ingreso->direccion }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Artículo</th>
                <th>Cant.</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCalculado = 0; @endphp
            @foreach($detalles as $detalle)
            @php
                $tipoCompra = $detalle->tipo_compra ?? 'Caja';
                $unidadPaquete = $detalle->unidad_x_paquete ?? 1;
                if($tipoCompra === 'Unidad') {
                    $subtotal = $detalle->cantidad * $detalle->precio;
                    $tipoTexto = $detalle->cantidad > 1 ? 'Unidades' : 'Unidad';
                } else {
                    $subtotal = $detalle->cantidad * $detalle->precio * $unidadPaquete;
                    $tipoTexto = $detalle->cantidad > 1 ? 'Cajas' : 'Caja';
                }
                $totalCalculado += $subtotal;
            @endphp
            <tr>
                <td>{{ $detalle->articulo }}</td>
                <td style="text-align:center;">
                    {{ $detalle->cantidad }} {{ $tipoTexto }}
                </td>
                <td>{{ number_format($detalle->precio, 2) }}</td>
                <td>{{ number_format($subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" align="right"><strong>Total:</strong></td>
                <td>{{ number_format($totalCalculado, 2) }}</td>
            </tr>
        </tfoot>
        <tfoot>
            <tr>
                <td colspan="3" align="right"><strong>Total:</strong></td>
                <td>{{ number_format($ingreso->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px;">
        <p>__________________________</p>
        <p>Firma de recepción</p>
    </div>
</body>
</html>