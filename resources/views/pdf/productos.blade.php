<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Artículos</title>
    <style>
        
        @page {
            margin: 1cm; 
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 10px;
            
            margin-top: 3.5cm; 
            margin-bottom: 1.5cm;
        }

        
        header {
            position: fixed;
            top: -0.5cm; 
            left: 0cm;
            right: 0cm;
            height: 3cm;
            border-bottom: 2px solid #0a3248;
        }

        .header-logo {
            width: 20%;
            float: left;
        }
        .header-logo img {
            max-width: 100%;
            max-height: 60px;
        }
        .header-content {
            width: 80%;
            float: right;
            text-align: right;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0a3248;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
            color: #333;
        }
        .report-date {
            font-size: 10px;
            color: #777;
            margin-top: 2px;
        }

        
        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1cm;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            text-align: center;
            font-size: 9px;
            color: #555;
        }

        
        .page-number:after {
            content: "Página " counter(page);
        }

        
        .table-container {
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }
        th {
            background-color: #0a3248;
            color: #ffffff;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
            font-size: 9px;
            border: 1px solid #0a3248;
        }
        td {
            border: 1px solid #cccccc;
            padding: 6px;
            color: #333;
            vertical-align: middle;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .stock-brot { color: #d9534f; font-weight: bold; } 
        .stock-ok { color: #333; }

    </style>
</head>
<body>

    @php
        $empresa = \App\Empresa::first();
        $logoPath = public_path('img/logoPrincipal.png');
    @endphp

    <header>
        <div class="header-logo">
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @else
                @endif
        </div>
        <div class="header-content">
            <div class="company-name">{{ 'BROKEN' }}</div> {{-- O usa $empresa->nombre --}}
            <div class="report-title">REPORTE GENERAL DE ARTÍCULOS</div>
            <div class="report-date">
                Fecha de emisión: {{ date('d/m/Y H:i') }} | 
                Usuario: {{ auth()->user()->usuario ?? 'Sistema' }}
            </div>
        </div>
    </header>

    <footer>
        <span class="page-number"></span> | {{ 'BROKEN' }}
    </footer>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="10%">Código</th>
                    <th width="25%">Nombre</th>
                    <th width="15%">Categoría</th>
                    <th width="15%">Proveedor</th>
                    <th width="10%" class="text-right">Costo Compra</th>
                    <th width="10%" class="text-right">Precio por Unidad</th>    
                    <th width="10%" class="text-right">Precio por Docena</th>
                    <th width="10%" class="text-right">Precio por Paquete</th>
                </tr>
            </thead>
            <tbody>
                @foreach($articulos as $index => $articulo)
                    <tr>
                        <td>{{ $articulo->codigo }}</td>
                        <td class="text-bold">
                            {{ $articulo->nombre }}
                        </td>
                        <td>{{ $articulo->nombre_categoria }}</td>
                        <td>{{ $articulo->nombre_proveedor }}</td>
                        
                        <td class="text-right text-bold">
                            {{ number_format($articulo->precio_costo_unid, 2) }} Bs.
                        </td>
                        <td class="text-right text-bold">
                            {{ number_format($articulo->precio_uno, 2) }} Bs.
                        </td>
                        <td class="text-right text-bold">
                            {{ number_format($articulo->precio_dos, 2) }} Bs.
                        </td>
                        <td class="text-right text-bold">
                            {{ number_format($articulo->precio_tres, 2) }} Bs.
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</body>
</html>