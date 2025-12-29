<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            padding: 20px;
            border-bottom: 2px solid #ccc;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
        }

        #logo {
            width: 100px;
            float: right;
        }

        .title {
            text-align: center;
            margin-bottom: 20px;
            color: #555;
        }

        .customer-details {
            margin-bottom: 20px;
        }

        .customer-details table,
        .details-table table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            /* Eliminar todos los bordes */
        }

        .customer-details th,
        .customer-details td,
        .details-table th,
        .details-table td {
            border: none;
            /* Eliminar todos los bordes */
            padding: 8px;
            text-align: left;
            font-size: 12px;

        }

        .customer-details th {
            background-color: #f2f2f2;
        }

        .section-title {
            padding: 10px;

            border-radius: 5px;
            margin-bottom: 10px;
        }

        .details-table {
            margin-bottom: 20px;
        }

        .details-table th {
            background-color: #f2f2f2;
        }

        .total {
            /* text-align: right; */
            font-size: 1.2em;
            color: #000;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="company-details">
                    <img src="img/logocodensa.png" alt="CompartiendoCodigo" id="logo">
                    <p><b>365 Group</b></p>
                    <p>Cochabamba, Bolivia</p>
                    <p>Teléfono: (+591) 999999999</p>
                    <p>Email: Group365@gmail.com</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <h3 class="title">COMPROBANTE DE PAGO</h3>

        <!-- Detalles del Cliente -->
        <div class="customer-details">
            @if ($venta)
                <table>
                    <tr>
                        <th colspan="2">Datos del Cliente</th>
                    </tr>
                    <tr>
                        <td><b>Nombre:</b> {{ $venta->nombre }}</td>
                        <td><b>Nro documento:</b> {{ $venta->num_documento }}</td>

                    </tr>
                    <tr>
                        <td><b>Direccion:</b> {{ $venta->direccion}}</td>
                        <td><b>Telefono:</b>{{ $venta->telefono }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <b>Cancelo
                                {{ $totalPagado }}
                            </b>
                        </td>

                    </tr>

                </table>
            @else
                <p>No se encontraron detalles del cliente.</p>
            @endif
        </div>

        <!-- Cuotas Pagadas -->
        <div class="section-title"><b>
                Detalle de pago
            </b>
        </div>
        <div class="details-table">
            @if ($cuotasPagadas->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>Número de Cuota</th>
                            <th>Fecha de Pago</th>
                            <th>Monto Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cuotasPagadas as $cuota)
                            <tr>
                                <td>{{ $cuota->numero_cuota }}</td>
                                <td>{{ $cuota->fecha_pago }}</td>
                                <td>{{ $cuota->monto_pagado }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No hay cuotas pagadas registradas.</p>
            @endif
        </div>

        <!-- Cuotas Pendientes -->
        <div class="section-title">
            <b>
                Cuotas Pendientes

            </b>
        </div>
        <div class="details-table">
            @if ($cuotasPendientes->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>Número de Cuota</th>
                            <th>Fecha de Vencimiento</th>
                            <th>Monto a Pagar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cuotasPendientes as $cuota)
                            <tr>
                                <td>{{ $cuota->numero_cuota }}</td>
                                <td>{{ $cuota->fecha_vencimiento }}</td>
                                <td>{{ $cuota->monto_pendiente }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No hay cuotas pendientes registradas.</p>
            @endif
        </div>

        <!-- Total Pagado -->
        <div class="total">
            <b>TOTAL PAGADO:</b>
            {{ $totalPagado }}
        </div>
    </div>
</body>

</html>