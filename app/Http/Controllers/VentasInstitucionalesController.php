<?php

namespace App\Http\Controllers;

use App\ArticulosVariable;
use App\FacturaInstitucional;
use App\VariableTemporal;
use App\VentasInstitucionales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CifrasEnLetrasController;
use Illuminate\Support\Facades\Log;
use App\Notifications\NotifyAdmin;
use FPDF;
use chillerlan\QRCode\{QRCode, QROptions};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use SebastianBergmann\Environment\Console;
use SimpleXMLElement;
use SoapClient;
use TheSeer\Tokenizer\Exception;
use App\Helpers\CustomHelpers;
use App\Medida;
use App\Rol;
use Illuminate\Support\Facades\File;
use Phar;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class VentasInstitucionalesController extends Controller
{
    public function registrarVariable(Request $request)
    {
        if (!$request->ajax()) return redirect('/');
         
        $variables = new VariableTemporal();
        $variables->idventainstitucional = $request->idventainstitucional;
        $variables->nombre = $request->nombre;
       
        $variables->save();

        return response()->json(['id' => $variables->id]);
    }

    public function registrarArticuloVariable(Request $request)
    {
        if (!$request->ajax()) return redirect('/');


        $articulo = new ArticulosVariable();
        $articulo->idvariable = $request->idvariable;
        $articulo->idarticulo = $request->idarticulo;
        $articulo->cantidad = $request->cantidad;
        $articulo->precio = $request->precio;
        $articulo->descuento = $request->descuento;
        $articulo->save();

        $idArticuloVariable = $articulo->id;

        $idVariable = $articulo->idvariable;

        return response()->json(['idArticuloVariable' => $idArticuloVariable, 'idVariable' => $idVariable, 'message' => 'Registro exitoso']);
    }

    public function registrarArticuloVariable2(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $articulo = new ArticulosVariable();
        $articulo->idvariable = $request->idvariable;
        $articulo->idarticulo = $request->idarticulo;
        $articulo->cantidad = $request->cantidad;
        $articulo->precio = $request->precio;
        $articulo->descuento = $request->descuento;
        $articulo->save();

        $idArticuloVariable = $articulo->id;

        $idVariable = $articulo->idvariable;

        return response()->json(['idArticuloVariable' => $idArticuloVariable, 'idVariable' => $idVariable, 'message' => 'Registro exitoso']);
    }

    public function registrarVentaInstitucional()
    {
        try {
            $ventaInstitucional = new VentasInstitucionales();
            $ventaInstitucional->estado = 0;
            $ventaInstitucional->save();

            // Obtén el ID del registro recién creado
            $idVentaInstitucional = $ventaInstitucional->id;

            return response()->json(['idVentaInstitucional' => $idVentaInstitucional, 'message' => 'Registro exitoso']);
        } catch (\Exception $e) {
            // Registra el error en los logs
            \Log::error($e);

            // Retorna una respuesta de error
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function registrarModificacionVentasInstitucionales(Request $request)
    {
        /*$idventa = $request->idventa;
        $idventaInstitu = $request->id;
        dd($idventa);*/
        try {
            $ventaInstitucional = VentasInstitucionales::where('id', $request->id)->first();

            if ($ventaInstitucional) {
                $ventaInstitucional->idventa = $request->idventa;
                $ventaInstitucional->estado = 1;

                $ventaInstitucional->save();
            } else {
                // Si no existe, puedes manejarlo según tus necesidades
                return response()->json(['error' => 'No se encontró el registro de VentaInstitucional'], 404);
            }

            return response()->json(['message' => 'Modificación registrada con éxito']);
        } catch (\Exception $e) {
            // Maneja cualquier excepción que pueda ocurrir
            \Log::error($e);

            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }


        public function obtenerArticulosPorVariableTemporal(Request $request)
        {
            $idvariable = $request->get('idvariable');

            $nombreVariable = VariableTemporal::find($idvariable)->nombre;

            $articulos = ArticulosVariable::join('articulos', 'articulo_variables.idarticulo', '=', 'articulos.id')
            ->select(
                'articulo_variables.*',
                'articulos.nombre as articulo'
            )
            ->where('articulo_variables.idvariable', $idvariable)
            ->get();
            
            return response()->json(['nombreVariable' => $nombreVariable, 'articulos' => $articulos]);
        }

    public function excluirArticulo(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $id = $request->get('id');
        
        $articulo = ArticulosVariable::findOrFail($id);
        $articulo->delete();

        return response()->json(['message' => 'Artículo eliminado exitosamente']);
    }

    public function listarFacturas(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $facturas = FacturaInstitucional::join('personas', 'factura_institucionals.idcliente', '=', 'personas.id')
            ->select('factura_institucionals.*', 'personas.nombre as razonSocial', 'personas.email as email', 'personas.num_documento as documentoid', 'personas.complemento_id as complementoid')
            ->orderBy('factura_institucionals.id', 'desc')->paginate(6);
        }
        else{
            $facturas = FacturaInstitucional::join('personas', 'factura_institucionals.idcliente', '=', 'personas.id')
            ->select('factura_institucionals.*','personas.nombre', 'personas.email', 'personas.num_documento', 'personas.complemento.id')
            ->orderBy('factura_institucionals.id', 'desc')->paginate(6);
        }
        

        return [
            'pagination' => [
                'total'        => $facturas->total(),
                'current_page' => $facturas->currentPage(),
                'per_page'     => $facturas->perPage(),
                'last_page'    => $facturas->lastPage(),
                'from'         => $facturas->firstItem(),
                'to'           => $facturas->lastItem(),
            ],
            'facturas' => $facturas
        ];
    }

    public function imprimirFactura($id, $idventainstitucional){

        /*$resultados = DB::table('ventas_institucionales')
            ->join('variable_temporals', 'ventas_institucionales.id', '=', 'variable_temporals.idventainstitucional')
            ->join('articulo_variables', 'variable_temporals.id', '=', 'articulo_variables.idvariable')
            ->join('articulos', 'articulo_variables.idarticulo', '=', 'articulos.id')
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->select(
                'variable_temporals.nombre as variable_nombre',
                'articulos.idcategoria as codigo',
                'categorias.nombre',
                'articulos.idmedida',
                DB::raw('SUM(articulo_variables.cantidad) as total_cantidad'),
                DB::raw('SUM(articulo_variables.precio) as subtotal'),
                DB::raw('SUM(articulo_variables.descuento) as descuento'),
                DB::raw('ROUND(SUM(articulo_variables.precio) / SUM(articulo_variables.cantidad), 2) as precio_unitario')
            )
            ->where('ventas_institucionales.id', $idventainstitucional)
            ->groupBy('variable_temporals.nombre, articulos.idcategoria, categorias.nombre, articulos.idmedida')
            ->get();*/

            $resultados = DB::select("
            SELECT
                variable_temporals.nombre AS variable_nombre,
                articulos.idcategoria AS codigo,
                categorias.nombre AS nombre_categoria,
                articulos.idmedida AS idmedida,
                SUM(articulo_variables.cantidad) AS total_cantidad,
                SUM(articulo_variables.cantidad * articulo_variables.precio) AS subtotal,
                SUM(articulo_variables.descuento) AS descuento,
                ROUND(SUM(articulo_variables.cantidad * articulo_variables.precio) / SUM(articulo_variables.cantidad), 2) AS precio_unitario
            FROM ventas_institucionales
            JOIN variable_temporals ON ventas_institucionales.id = variable_temporals.idventainstitucional
            JOIN articulo_variables ON variable_temporals.id = articulo_variables.idvariable
            JOIN articulos ON articulo_variables.idarticulo = articulos.id
            JOIN categorias ON articulos.idcategoria = categorias.id
            WHERE ventas_institucionales.id = ?
            GROUP BY variable_temporals.nombre, articulos.idcategoria, categorias.nombre, articulos.idmedida
        ", [$idventainstitucional]);


        $facturas = FacturaInstitucional::join('personas', 'factura_institucionals.idcliente', '=', 'personas.id')
        ->select('factura_institucionals.*','personas.nombre as razonSocial', 'personas.email as email', 'personas.num_documento as documentoid', 'personas.complemento_id as complementoid')
        ->where('factura_institucionals.id', '=', $id)
        ->orderBy('factura_institucionals.id', 'desc')->paginate(6);

        Log::info('Resultado', [
            //'facturas' => $facturas,
            'idFactura' => $id,
        ]);

        $xml = $facturas[0]->productos;
        $archivoXML = new SimpleXMLElement($xml);
        $nitEmisor = $archivoXML->cabecera[0]->nitEmisor;
        $numeroFactura = str_pad($archivoXML->cabecera[0]->numeroFactura, 5, "0", STR_PAD_LEFT);
        $cuf = $archivoXML->cabecera[0]->cuf;
        $direccion = $archivoXML->cabecera[0]->direccion;
        $telefono = $archivoXML->cabecera[0]->telefono;
        $municipio = $archivoXML->cabecera[0]->municipio;
        $fechaEmision =  $archivoXML->cabecera[0]->fechaEmision;
        $documentoid =  $archivoXML->cabecera[0]->numeroDocumento;
        $razonSocial =  $archivoXML->cabecera[0]->nombreRazonSocial;
        $codigoCliente =  $archivoXML->cabecera[0]->codigoCliente;
        $montoTotal =  $archivoXML->cabecera[0]->montoTotal;
        $descuentoAdicional =  $archivoXML->cabecera[0]->descuentoAdicional;
        $leyenda =  $archivoXML->cabecera[0]->leyenda;
        $complementoid = $archivoXML->cabecera[0]->complemento;

        
        $totalpagar = number_format(floatval($montoTotal),2);
        $totalpagar = str_replace(',','', $totalpagar);
        $totalpagar = str_replace('.',',', $totalpagar);
        $cifrasEnLetras = new CifrasEnLetrasController();
        $letra=($cifrasEnLetras->convertirBolivianosEnLetras($totalpagar));


        $url = 'https://pilotosiat.impuestos.gob.bo/consulta/QR?nit='.$nitEmisor.'&cuf='.$cuf.'&numero='.$numeroFactura.'&t=2';
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => false,
            'scale' => 10,
        ]);
        $qrCode = new QRCode($options);
        $qrCode->render($url, public_path('qr/qrcode.png'));

        
        $pdf = new FPDF('P','mm','Letter');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(60,4, utf8_decode('CONTAB SRL'),0,0,'C');
        $pdf->Cell(40,4, '',0,0,'C');
        $pdf->Cell(27,4, '',0,0,'C');
        $pdf->Cell(38,4, 'NIT',0,0,'L');
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(32,4, $nitEmisor,0,1,'L');

        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(60,4, utf8_decode('CASA MATRIZ'),0,0,'C');
        $pdf->Cell(40,4, '',0,0,'C');
        $pdf->Cell(27,4, '',0,0,'C');
        $pdf->Cell(38,4, utf8_decode('FACTURA N°'),0,0,'L');
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(32,4, $numeroFactura,0,1,'L');
        
        $pdf->Cell(60,4, utf8_decode('N° Punto de Venta 0'),0,0,'C');
        $pdf->Cell(40,4, '',0,0,'C');
        $pdf->Cell(27,4, '',0,0,'C');
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(38,4, utf8_decode('CÓD. AUTORIZACIÓN'),0,0,'L');
        $pdf->SetFont('Arial','',8);
        $y=$pdf->GetY();
        $pdf->MultiCell(32,4, $cuf,0,'L');
        
        $pdf->SetY($y+4);
        $pdf->MultiCell(60,3, utf8_decode($direccion),0,'C');

        $pdf->Cell(60,4, utf8_decode('Teléfono: '.$telefono),0,1,'C');
        $pdf->Cell(60,4, utf8_decode($municipio),0,1,'C');

        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,6, utf8_decode('FACTURA'),0,1,'C');
        
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(0,4, utf8_decode('(Con Derecho a Crédito Fiscal)'),0,1,'C');

        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(40,5, utf8_decode('Fecha:'),0,0,'L');
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(60,5, $fechaEmision,0,0,'L');
        
        $pdf->Cell(27,5, '',0,0,'C');
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(38,5, 'NIT/CI/CEX:    ',0,0,'R');
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(32,5, $documentoid."-".$complementoid,0,1,'L');

        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(40,5, utf8_decode('Nombre/Razón Social:'),0,0,'L');
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(60,5, utf8_decode($razonSocial),0,0,'L');
        $pdf->Cell(27,5, '',0,0,'C');
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(38,5, 'Cod. Cliente:    ',0,0,'R');
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(32,5, $documentoid,0,1,'L');

        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',8);
        $y=$pdf->GetY();
        $pdf->MultiCell(25,3.5, utf8_decode('CÓDIGO PRODUCTO / SERVICIO'),1,'C');
        $pdf->SetY($y);
        $pdf->SetX(35);
        $pdf->MultiCell(25,3.5, utf8_decode("\nCANTIDAD\n "),1,'C');
        $pdf->SetY($y);
        $pdf->SetX(60);
        $pdf->MultiCell(20,3.5, utf8_decode("\nUNIDAD DE MEDIDA"),1,'C');
        $pdf->SetY($y);
        $pdf->SetX(80);
        $pdf->MultiCell(50,3.5, utf8_decode("\nDESCRIPCIÓN\n "),1,'C');
        $pdf->SetY($y);
        $pdf->SetX(130);
        $pdf->MultiCell(25,3.5, utf8_decode("\nPRECIO UNITARIO"),1,'C');
        $pdf->SetY($y);
        $pdf->SetX(155);
        $pdf->MultiCell(25,3.5, utf8_decode("\nDESCUENTO\n "),1,'C');
        $pdf->SetY($y);
        $pdf->SetX(180);
        $pdf->MultiCell(27,3.5, utf8_decode("\nSUBTOTAL\n "),1,'C');
        

        $pdf->SetFont('Arial','',8);
        $detalle = $archivoXML->detalle;
        $sumaSubTotales = 0.0;

        foreach ($resultados as $resultado) {
            $pdf->Cell(25,5, $resultado->codigo,1,0,'L');
            $pdf->Cell(25,5, $resultado->total_cantidad,1,0,'R');
            $pdf->Cell(20,5, $resultado->idmedida,1,0,'L');
            $pdf->Cell(50,5, $resultado->variable_nombre,1,0,'L');
            $pdf->Cell(25,5, number_format(floatval($resultado->precio_unitario),2),1,0,'R');
            $pdf->Cell(25,5, number_format(floatval($resultado->descuento),2),1,0,'R');
            $pdf->Cell(27,5, number_format(floatval($resultado->subtotal),2),1,1,'R');

            //Sumar el subTotal actual
            $sumaSubTotales += floatval($resultado->subtotal);
        }

        $pdf->Cell(120,5, '',0,0,'L');
        $pdf->Cell(50,5, 'SUBTOTAL Bs.',1,0,'R');
        $pdf->Cell(27,5, number_format(floatval($sumaSubTotales),2),1,1,'R');

        $pdf->Cell(120,5, '',0,0,'L');
        $pdf->Cell(50,5, 'DESCUENTO Bs.',1,0,'R');
        $pdf->Cell(27,5, number_format(floatval($descuentoAdicional),2),1,1,'R');

        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(120,5,'Son: '.ucfirst($letra),0,0,'L');
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(50,5, 'TOTAL Bs.',1,0,'R');
        $pdf->Cell(27,5, number_format(floatval(($montoTotal)),2),1,1,'R');

        $pdf->Cell(120,5, '',0,0,'L');
        $pdf->Cell(50,5, 'MONTO GIFT CARD Bs.',1,0,'R');
        $pdf->Cell(27,5, '0.00',1,1,'R');

        $pdf->Cell(120,5, '',0,0,'L');
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(50,5, 'MONTO A PAGAR Bs.',1,0,'R');
        $pdf->Cell(27,5,  number_format(floatval(($montoTotal)),2),1,1,'R');

        $pdf->Cell(120,5, '',0,0,'L');
        $pdf->Cell(50,5, utf8_decode('IMPORTE BASE CRÉDITO FISCAL'),1,0,'R');
        $pdf->Cell(27,5,  number_format(floatval(($montoTotal)),2),1,1,'R');
-
        $pdf->Ln(10);
        $y = $pdf->GetY();
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(170,5, utf8_decode('ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS, EL USO ILÍCITO SERÁ SANCIONADO PENALMENTE DE ACUERDO A LEY'),0,1,'C');
        $pdf->Image(public_path('qr/qrcode.png'), 182, $y-3, 25, 'PNG');
        
        $pdf->Ln(4);
        $pdf->Cell(170,5, utf8_decode($leyenda),0,1,'C');

        $pdf->Ln(2);
        $pdf->Cell(170,5, utf8_decode('"Este documento es la Representación Gráfica de un Documento Fiscal Digital emitido en una modalidad de facturación en línea"'),0,1,'C');

        $pdf->Output(public_path('docs/facturaCarta.pdf'), 'F');
        return response()->download(public_path('docs/facturaCarta.pdf'));
    }


}
