<?php

namespace App\Http\Controllers;

use App\Venta;
use App\Factura;
use App\Leyendas;
use App\MotivoAnulacion;
use App\User;
use Illuminate\Http\Request;
use SoapClient;
use Illuminate\Support\Facades\DB;


class FacturaController extends Controller
{
    public function verificarComunicacion()
    {
        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->verificarComunicacion();
        if ($res->RespuestaComunicacion->transaccion == true) {
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
        } else {
            $msg = "Falló la comunicación";
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
    }

    public function cuis()
    {
        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->cuis();
        $res->RespuestaCuis->codigo;
        $_SESSION['scuis'] = $res->RespuestaCuis->codigo;
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function cufd()
    {
        if (!isset($_SESSION['scufd'])) {
            require "SiatController.php";
            $siat = new SiatController();
            $res = $siat->cufd();
            if ($res->RespuestaCufd->transaccion == true) {
                $cufd = $res->RespuestaCufd->codigo;
                $codigoControl = $res->RespuestaCufd->codigoControl;
                $direccion = $res->RespuestaCufd->direccion;
                $fechaVigencia = $res->RespuestaCufd->fechaVigencia;
                $_SESSION['scufd'] = $cufd;
                $_SESSION['scodigoControl'] = $codigoControl;
                $_SESSION['sdireccion'] = $direccion;
                $_SESSION['sfechaVigenciaCufd'] = $fechaVigencia;
            } else {
                $res = false;
            }
        } else {
            $fechaVigencia = substr($_SESSION['sfechaVigenciaCufd'], 0, 16);
            $fechaVigencia = str_replace("T", " ", $fechaVigencia);
            if ($fechaVigencia < date('Y-m-d H:i')) {
                require "SiatController.php";
                $siat = new SiatController();
                $res = $siat->cufd();
                if ($res->RespuestaCufd->transaccion == true) {
                    $cufd = $res->RespuestaCufd->codigo;
                    $codigoControl = $res->RespuestaCufd->codigoControl;
                    $direccion = $res->RespuestaCufd->direccion;
                    $fechaVigencia = $res->RespuestaCufd->fechaVigencia;
                    $_SESSION['scufd'] = $cufd;
                    $_SESSION['scodigoControl'] = $codigoControl;
                    $_SESSION['sdireccion'] = $direccion;
                    $_SESSION['sfechaVigenciaCufd'] = $fechaVigencia;
                } else {
                    $res = false;
                }
            } else {
                $res['transaccion'] = true;
                $res['codigo'] = $_SESSION['scufd'];
                $res['fechaVigencia'] = $_SESSION['sfechaVigenciaCufd'];
                $res['direccion'] = $_SESSION['sdireccion'];
            }
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function anulacionFactura($cuf)
    {
        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->anulacionFactura($cuf);
        //echo json_encode($res, JSON_UNESCAPED_UNICODE);
        var_dump($res);
    }

    public function obtenerDatosMotivoAnulacion(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $motivo_anulaciones = MotivoAnulacion::select('id', 'descripcion', 'codigo')
            ->orderBy('id', 'desc')
            ->get();

        return ['motivo_anulaciones' => $motivo_anulaciones];
    }

    public function obtenerLeyendaAleatoria()
    {
        $leyenda = Leyendas::where('codigoActividad', 461021)
            ->inRandomOrder()
            ->value('descripcionLeyenda');

        return response()->json(['descripcionLeyenda' => $leyenda]);
    }

public function obtenerUltimoNumero()
{
    // Obtener el usuario autenticado
    $idUsuario = auth()->id();

    // Obtener la sucursal del usuario
    $sucursal = User::where('id', $idUsuario)->value('idsucursal');

    if (!$sucursal) {
        return response()->json(['error' => 'Sucursal no encontrada'], 404);
    }

    // Obtener el último número de comprobante (numérico) para FACTURA
    $max = Venta::join('users', 'ventas.idusuario', '=', 'users.id')
        ->where('users.idsucursal', $sucursal)
        ->where('ventas.tipo_comprobante', 'FACTURA')
        ->select(DB::raw('MAX(CAST(ventas.num_comprobante AS UNSIGNED)) as max_num'))
        ->value('max_num');

    // Si no hay registros, se empieza en 0
    $ultimoNumero = $max !== null ? intval($max) : 0;

    return response()->json([
        'ultimoNumero' => $ultimoNumero,
        'siguienteNumero' => $ultimoNumero + 1 // 👈 opcional
    ]);
}

    public function obtenerNumeroPorVenta($idventa)
    {
        // Buscar la venta por ID
        $venta = Venta::where('id', $idventa)->select('num_comprobante')->first();

        if (!$venta) {
            return response()->json(['error' => 'Venta no encontrada'], 404);
        }

        // Convertir a entero en caso de que sea string
        $numComprobante = intval($venta->num_comprobante);

        return response()->json(['num_comprobante' => $numComprobante]);
    }
}
