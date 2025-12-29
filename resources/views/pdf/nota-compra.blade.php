<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nota de Compra</title>

<style>
    @page { margin: 40px 40px; }
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        font-size: 10px;
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        font-size: 9px;
    }

    th {
        background: #0b4f77;
        color: white;
        padding: 6px;
        text-align: center;
        border: 1px solid #0b4f77;
    }

    td {
        border: 1px solid #ccc;
        padding: 4px;
    }

    /* ---------------- CABECERA ---------------- */
    .header {
        background: #0b4f77;
        color: white;
        padding: 20px 25px;
        border-radius: 5px;
    }

    .logo img {
        height: 60px;
    }

    .empresa-info {
        font-size: 10px;
        line-height: 15px;
        margin-left: 10px;
    }

    .title-box {
        text-align: right;
        font-size: 14px;
        font-weight: bold;
    }

    .title-details {
        font-size: 10px;
        line-height: 15px;
        margin-top: 5px;
    }

    .divider {
        width: 100%;
        height: 2px;
        background: #0b4f77;
        margin: 8px 0 5px;
    }
</style>

</head>
<body>

<!-- ================= CABECERA ================= -->
<table width="100%" cellspacing="0" cellpadding="0"
    style="background:#ffffff !important; color:#000000 !important; border-collapse:collapse; border:0 !important;">
    <tr style="border:0 !important; padding:0; margin:0;">

        <!-- Logo + Empresa -->
        <td width="50%" valign="middle"
            style="border:0 !important; padding:4px 10px 2px 10px; color:#000000 !important;">

            <div style="display:flex; align-items:center;">

                <img src="{{ public_path('./../public/img/logoPrincipal.png') }}" 
                     style="height:50px; margin-top: 7px;">

                <div style="font-size:10px; line-height:14px; margin-left:68px; color:#000000 !important;">
                    <strong>IMPORTACIONES SEMO</strong><br>
                    Cochabamba – Bolivia<br>
                </div>

            </div>

        </td>

        <!-- Título + Datos -->
        <td width="50%" valign="top" align="right"
            style="border:0 !important; padding:4px 10px 2px 10px; color:#000000 !important;">

            <div style="font-size:14px; font-weight:bold; margin-bottom:2px; color:#000000 !important;">
                NOTA DE COMPRA
            </div>

            <div style="font-size:10px; line-height:13px; color:#000000 !important;">
                Fecha: <strong>{{ $ingreso->fecha_hora }}</strong><br>
                Registrado por: <strong>{{ $ingreso->usuario }}</strong><br>
                Tipo Comprobante: <strong>{{ $ingreso->tipo_comprobante }}</strong><br>
                Numero Comprobante: <strong>{{ $ingreso->num_comprobante }}</strong>
            </div>

        </td>

    </tr>
</table>




<!-- Divider -->
<div class="divider"></div>

<!-- ================= DETALLES ================= -->
<h3 style="font-size:11px; margin-top:15px;">Detalles de la Compra</h3>

<table>
    <thead>
        <tr>
            <th style="width:10%">Cjs</th>
            <th style="width:40%">Artículo</th>
            <th style="width:10%">Cant x Caja</th>
            <th style="width:20%">Precio Unitario</th>
            <th style="width:25%">Subtotal</th>
        </tr>
    </thead>

    <tbody>
        @foreach($detalles as $d)
        <tr>
            <td style="text-align:center;">{{ $d->cantidad }}</td>
            <td>{{ $d->articulo }} ({{ $d->codigo }})</td>
            <td style="text-align:center;">{{ $d->unidad_x_paquete }}</td>
            <td style="text-align:right;">{{ number_format($d->precio, 2) }}</td>
            <td style="text-align:right;">{{ number_format($d->cantidad * $d->precio * $d->unidad_x_paquete, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- ================= TOTAL ================= -->
<h3 style="text-align:right; margin-top:10px; font-size:12px;">
    TOTAL: <strong>Bs. {{ number_format($ingreso->total, 2) }}</strong>
</h3>

</body>
</html>
