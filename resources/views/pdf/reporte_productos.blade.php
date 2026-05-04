<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 10px;
        margin-bottom: 50px; /* 🔥 espacio para footer */
    }

    .header {
        text-align: center;
        position: relative;
        margin-bottom: 40px; /* 🔥 AUMENTA ESTO */
    }

    .logo {
        position: absolute;
        left: 0;
        top: 0;
        width: 60px;
    }

    .titulo {
        font-size: 16px;
        font-weight: bold;
        color: #2c3e50;
    }

    .subtitulo {
        font-size: 10px;
    }

    .filtros {
        background: #ecf0f1;
        padding: 8px;
        margin-top: 15px;
        margin-bottom: 10px;
    }

    .filtros span {
        margin-right: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #34495e;
        color: white;
    }

    th, td {
        border: 1px solid #000;
        padding: 4px;
    }

    tbody tr:nth-child(even) {
        background: #f5f5f5;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 20px;
        font-size: 9px;
        text-align: right;
    }

    .pagenum:before {
        content: counter(page);
    }
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <img src="{{ public_path('img/logoPrincipal.png') }}" class="logo">

    <div class="titulo">REPORTE DE PRODUCTOS VENDIDOS</div>
    <div class="subtitulo">
        Fecha de generación: {{ date('d/m/Y H:i:s') }}
    </div>
</div>

<!-- FILTROS -->
<div class="filtros">
    <span><strong>Sucursal:</strong> {{ $sucursal }}</span>
    <span><strong>Desde:</strong> {{ $fechaInicio }}</span>
    <span><strong>Hasta:</strong> {{ $fechaFin }}</span>
</div>

<!-- TABLA -->
<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cant.</th>
            <th>Precio</th>
            <th>Subtotal</th>
            <th>Comprobante</th>
            <th>Fecha</th>
            <th>Usuario</th>
            <th>Estado</th>
            <th>Pago</th>
        </tr>
    </thead>

    <tbody>
        @foreach($productos as $item)
        <tr>
            <td>{{ $item->producto }}</td>
            <td class="text-center">{{ $item->cantidad }}</td>
            <td class="text-right">{{ number_format($item->precio_unitario, 2) }}</td>
            <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
            <td class="text-center">{{ $item->num_comprobante }}</td>
            <td class="text-center">{{ $item->fecha_hora }}</td>
            <td>{{ $item->vendedor }}</td>
            <td class="text-center">
                {{ $item->estado == 1 ? 'Registrado' : 'Anulado' }}
            </td>
            <td class="text-center">{{ $item->tipo_pago }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<script type="text/php">
if (isset($pdf)) {

    $font = $fontMetrics->getFont("Arial", "normal");

    $text = "Reporte generado por el sistema - Página {PAGE_NUM} de {PAGE_COUNT}";

    $size = 8;

    // 🔥 calcular ancho del texto para centrar
    $width = $fontMetrics->get_text_width($text, $font, $size);

    // 🔥 ancho de página A4 horizontal ≈ 842 puntos
    $x = (842 - $width) / 2;

    // 🔥 altura (ajusta si necesitas)
    $y = 570;

    $pdf->page_text($x, $y, $text, $font, $size, [0,0,0]);
}
</script>

</body>
</html>
