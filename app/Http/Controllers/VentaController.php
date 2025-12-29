<?php

namespace App\Http\Controllers;
use App\Almacen;
use App\Categoria;
use App\Venta;
use NumberFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use App\Persona;
use App\Articulo;
use App\Inventario;
use App\DetalleVenta;
use App\User;
use App\Ingreso;
use App\CreditoVenta;
use App\CuotasCredito;
use PDF;

use App\Empresa;
use App\Autorizaciondescuento;
use App\Caja;
use App\TransaccionesCaja;
use App\Factura;
use App\FacturaFueraLinea;
use App\FacturaInstitucional;
use App\Helpers\CustomHelpers;
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
use App\Medida;
use App\MotivoAnulacion;
use App\PuntoVenta;
use App\Rol;
use App\Sucursales;
use Illuminate\Support\Facades\File;
use Phar;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;



use function Ramsey\Uuid\v1;

class VentaController extends Controller
{
    private $fecha_formato;

    public function __construct()
    {
        session_start();
    }
public function index(Request $request)
{
    if (!$request->ajax()) {
        return redirect('/');
    }

    $buscar = $request->buscar;
    $usuario = \Auth::user();
    $idrol = $usuario->idrol;
    $idsucursal = $usuario->idsucursal;

    // Obtener codigoPuntoVenta
    $codigoPuntoVenta = '';
    if (!empty($usuario->idpuntoventa)) {
        $puntoVenta = PuntoVenta::find($usuario->idpuntoventa);
        if ($puntoVenta) {
            $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
        }
    }

    // Obtener codigoSucursal
    $codigoSucursal = '';
    $sucursal = Sucursales::find($idsucursal);
    if ($sucursal) {
        $codigoSucursal = $sucursal->codigoSucursal;
    }

    // =====================================
    // CONSULTA PRINCIPAL INCLUYENDO TODOS LOS CLIENTES
    // =====================================
$query = Persona::leftJoin('ventas', 'personas.id', '=', 'ventas.idcliente')
    ->leftJoin('users', 'ventas.idusuario', '=', 'users.id')
    ->leftJoin('sucursales', 'users.idsucursal', '=', 'sucursales.id')
    ->leftJoin('facturas', 'ventas.id', '=', 'facturas.idventa')

    // 🔴 JOIN para excluir proveedores
    ->leftJoin('proveedores', 'proveedores.id', '=', 'personas.id')

    // 🔴 JOIN para excluir usuarios
    ->leftJoin('users as u2', 'u2.id', '=', 'personas.id')

    ->select(
        'ventas.tipo_comprobante',
        DB::raw('COALESCE(ventas.idcliente, personas.id) as idcliente'),
        'ventas.idtipo_venta',
        'ventas.id',
        'ventas.serie_comprobante',
        'ventas.num_comprobante',
        'ventas.fecha_hora',
        'ventas.impuesto',
        'ventas.total',
        'ventas.descuento_total',
        'ventas.estado',
        'users.usuario',
        'personas.nombre as razonSocial',
        'personas.num_documento as documentoid',
        'facturas.id as idFactura',
        'facturas.numeroFactura',
        'facturas.cuf',
        'facturas.cufd',
        'facturas.codigoControl',
        'facturas.correo',
        'facturas.fechaEmision',
        'sucursales.nombre as nombre_sucursal'
    )
    ->selectRaw("
        CASE 
        WHEN ventas.idtipo_venta = 2 THEN 
            COALESCE(
                (
                    SELECT cc.saldo_restante
                    FROM cuotas_credito cc
                    WHERE cc.idcredito = ventas.id
                    ORDER BY cc.numero_cuota DESC
                    LIMIT 1
                ),
                ventas.total
            )
        ELSE 0
    END as saldo_restante
    ")

    // ✅ AQUÍ SE EXCLUYEN
    ->whereNull('proveedores.id')
    ->whereNull('u2.id')
->where(function ($q) {
    $q->where('ventas.estado', '<>', 0)
      ->orWhereNull('ventas.id');
})
    ->orderBy('ventas.fecha_hora', 'desc');

    // 🔹 FILTROS POR ROL
    if ($idrol == 4) {
        // ve todo
    } elseif ($idrol == 1) {
        $query->where('users.idsucursal', $idsucursal);
    } else {
        $query->where('ventas.idusuario', $usuario->id);
    }

    // 🔍 FILTRO BÚSQUEDA
    if (!empty($buscar)) {
        $query->where(function ($q) use ($buscar) {
            $q->where('ventas.num_comprobante', 'like', "%$buscar%")
              ->orWhere('personas.num_documento', 'like', "%$buscar%")
              ->orWhere('personas.nombre', 'like', "%$buscar%")
              ->orWhere('ventas.fecha_hora', 'like', "%$buscar%")
              ->orWhere('users.usuario', 'like', "%$buscar%");
        });
    }

    if ($request->filled('tipo_venta')) {
        $query->where('ventas.idtipo_venta', $request->tipo_venta);
    }

    // ✅ SIN PAGINACIÓN
    $ventas = $query->get();

    return [
        'ventas' => $ventas,
        'usuario' => $usuario,
        'codigoPuntoVenta' => $codigoPuntoVenta,
        'codigoSucursal' => $codigoSucursal,
    ];
}




    public function indexFactura(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = $request->buscar;
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;
        $idsucursal = $usuario->idsucursal;

        // Obtener el codigoPuntoVenta
        $codigoPuntoVenta = '';
        if (!empty($usuario->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($usuario->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        // Obtener el codigoSucursal
        $codigoSucursal = '';
        $sucursal = Sucursales::find($idsucursal);
        if ($sucursal) {
            $codigoSucursal = $sucursal->codigoSucursal;
        }

        $query = Venta::leftJoin('facturas', 'ventas.id', '=', 'facturas.idventa')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->select(
                'ventas.tipo_comprobante as tipo_comprobante',
                'ventas.idcliente',
                'ventas.id',
                'ventas.serie_comprobante',
                'ventas.num_comprobante',
                'ventas.fecha_hora',
                'ventas.impuesto',
                'ventas.total',
                'ventas.estado',
                'ventas.descuento_total',
                'ventas.idtipo_venta',
                'users.usuario',
                'personas.nombre as razonSocial',
                'personas.num_documento as documentoid',
                'facturas.id as idFactura',
                'facturas.numeroFactura',
                'facturas.cuf',
                'facturas.cufd',
                'facturas.codigoControl',
                'facturas.correo',
                'facturas.fechaEmision',
                'sucursales.nombre as nombre_sucursal',
                // 👇 Campo adicional: facturaValidada
                \DB::raw("
                CASE 
                    WHEN facturas.id IS NOT NULL THEN 1
                    ELSE 0
                END as facturaValidada
            ")
            )
            ->where('ventas.tipo_comprobante', '=', 'FACTURA')
            ->orderBy('ventas.fecha_hora', 'desc');

        // 🔹 FILTROS POR ROL
        if ($idrol == 4) {
            // Rol 4: muestra TODO (sin filtro)
        } elseif ($idrol == 1) {
            // Rol 1: muestra ventas de su sucursal
            $query->where('users.idsucursal', $idsucursal);
        } else {
            // Otros roles (por ejemplo vendedor): muestra solo sus ventas
            $query->where('ventas.idusuario', $usuario->id);
        }

        // 🔍 FILTRO DE BÚSQUEDA
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('ventas.num_comprobante', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.num_documento', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('ventas.fecha_hora', 'like', '%' . $buscar . '%')
                    ->orWhere('users.usuario', 'like', '%' . $buscar . '%');
            });
        }

        $ventas = $query->paginate(10);

        return [
            'pagination' => [
                'total' => $ventas->total(),
                'current_page' => $ventas->currentPage(),
                'per_page' => $ventas->perPage(),
                'last_page' => $ventas->lastPage(),
                'from' => $ventas->firstItem(),
                'to' => $ventas->lastItem(),
            ],
            'ventas' => $ventas,
            'usuario' => $usuario,
            'codigoPuntoVenta' => $codigoPuntoVenta,
            'codigoSucursal' => $codigoSucursal,
        ];
    }


    public function indexRecibo(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = $request->buscar;
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;
        $idsucursal = $usuario->idsucursal;

        // Obtener el codigoPuntoVenta
        $codigoPuntoVenta = '';
        if (!empty($usuario->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($usuario->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        // Obtener el codigoSucursal
        $codigoSucursal = '';
        $sucursal = Sucursales::find($idsucursal);
        if ($sucursal) {
            $codigoSucursal = $sucursal->codigoSucursal;
        }

        $query = Venta::join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->select(
                'ventas.tipo_comprobante as tipo_comprobante',
                'ventas.idcliente',
                'ventas.id',
                'ventas.tipo_comprobante',
                'ventas.serie_comprobante',
                'ventas.num_comprobante',
                'ventas.fecha_hora',
                'ventas.impuesto',
                'ventas.total',
                'ventas.descuento_total',
                'ventas.estado',
                'ventas.idtipo_venta',
                'users.usuario',
                'personas.nombre as razonSocial',
                'personas.num_documento as documentoid',
                'sucursales.nombre as nombre_sucursal'
            )
            ->where('ventas.tipo_comprobante', '=', 'RESIVO')
            ->orderBy('ventas.fecha_hora', 'desc');

        // 🔹 FILTROS POR ROL
        if ($idrol == 4) {
            // Rol 4: puede ver TODO (sin filtro adicional)
        } elseif ($idrol == 1) {
            // Rol 1: ve solo ventas de su sucursal
            $query->where('users.idsucursal', $idsucursal);
        } else {
            // Otros roles (vendedor, etc): solo sus ventas
            $query->where('ventas.idusuario', $usuario->id);
        }

        // 🔍 FILTRO DE BÚSQUEDA
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('ventas.num_comprobante', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.num_documento', 'like', '%' . $buscar . '%')
                    ->orWhere('personas.nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('ventas.fecha_hora', 'like', '%' . $buscar . '%')
                    ->orWhere('users.usuario', 'like', '%' . $buscar . '%');
            });
        }

        $ventas = $query->paginate(10);

        return [
            'pagination' => [
                'total' => $ventas->total(),
                'current_page' => $ventas->currentPage(),
                'per_page' => $ventas->perPage(),
                'last_page' => $ventas->lastPage(),
                'from' => $ventas->firstItem(),
                'to' => $ventas->lastItem(),
            ],
            'ventas' => $ventas,
            'usuario' => $usuario,
            'codigoPuntoVenta' => $codigoPuntoVenta,
            'codigoSucursal' => $codigoSucursal,
        ];
    }




    public function ventaOffline(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $idtipoventa = $request->idtipo_venta;

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        $usuario = \Auth::user();

        if ($buscar == '') {
            $facturasOffline = FacturaFueraLinea::join('ventas', 'factura_fuera_lineas.idventa', '=', 'ventas.id')
                ->join('personas', 'factura_fuera_lineas.idcliente', '=', 'personas.id')
                ->join('users', 'ventas.idusuario', '=', 'users.id')
                ->select(
                    'factura_fuera_lineas.*',
                    'ventas.tipo_comprobante as tipo_comprobante',
                    'ventas.serie_comprobante',
                    'ventas.num_comprobante as num_comprobante',
                    'ventas.fecha_hora as fecha_hora',
                    'ventas.impuesto as impuesto',
                    'ventas.total as total',
                    'ventas.estado as estado',
                    'personas.nombre as razonSocial',
                    'personas.email as email',
                    'personas.num_documento as documentoid',
                    'personas.complemento_id as complementoid',
                    'users.usuario as usuario'
                )
                ->orderBy('factura_fuera_lineas.id', 'desc')->paginate(3);
        } else {
            $facturasOffline = FacturaFueraLinea::join('ventas', 'factura_fuera_lineas.idventa', '=', 'ventas.id')
                ->join('personas', 'factura_fuera_lineas.idcliente', '=', 'personas.id')
                ->join('users', 'ventas.idusuario', '=', 'users.id')
                ->select(
                    'factura_fuera_lineas.*',
                    'ventas.tipo_comprobante as tipo_comprobante',
                    'ventas.serie_comprobante',
                    'ventas.num_comprobante as num_comprobante',
                    'ventas.fecha_hora as fecha_hora',
                    'ventas.impuesto as impuesto',
                    'ventas.total as total',
                    'ventas.estado as estado',
                    'personas.nombre as razonSocial',
                    'personas.email as email',
                    'personas.num_documento as documentoid',
                    'personas.complemento_id as complementoid',
                    'users.usuario as usuario'
                )
                ->where('factura_fuera_lineas.' . $criterio, 'like', '%' . $buscar . '%')
                ->orderBy('factura_fuera_lineas.id', 'desc')->paginate(3);
        }

        return [
            'pagination' => [
                'total' => $facturasOffline->total(),
                'current_page' => $facturasOffline->currentPage(),
                'per_page' => $facturasOffline->perPage(),
                'last_page' => $facturasOffline->lastPage(),
                'from' => $facturasOffline->firstItem(),
                'to' => $facturasOffline->lastItem(),
            ],
            'facturasOffline' => $facturasOffline,
            'usuario' => $usuario
        ];
    }

    public function indexBuscar(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        $usuario = \Auth::user();
        $idRoles = $request->idRoles;
        $idAlmacen = $request->idAlmacen;
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $idRoles = ($idRoles == 0) ? null : $idRoles;
        $idAlmacen = ($idAlmacen == 0) ? null : $idAlmacen;

        if ($buscar == '') {
            $ventas = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
                ->join('users', 'ventas.idusuario', '=', 'users.id')
                ->join('detalle_ventas', 'ventas.id', '=', 'detalle_ventas.idventa')
                ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->select(
                    'ventas.id',
                    'ventas.tipo_comprobante',
                    'ventas.serie_comprobante',
                    'ventas.num_comprobante',
                    'ventas.fecha_hora',
                    'ventas.impuesto',
                    'ventas.total',
                    'ventas.estado',
                    'personas.nombre as cliente',
                    'users.usuario',
                    'users.idrol',
                    'detalle_ventas.idarticulo'
                )
                ->distinct()
                ->where(function ($query) use ($idRoles) {
                    if ($idRoles !== null) {
                        $query->where('users.idrol', $idRoles);
                    }
                })
                ->where(function ($query) use ($idAlmacen) {
                    if ($idAlmacen !== null) {
                        $query->where('inventarios.idalmacen', $idAlmacen);
                    }
                });

            // Filtrar por fechas solo si se proporcionan fechas distintas de la actual
            if ($fechaInicio !== now()->toDateString() || $fechaFin !== now()->addDay()->toDateString()) {
                $ventas->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);
            }

            $ventas = $ventas->orderBy('ventas.id', 'desc')->paginate(6);
        } else {
            $ventas = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
                ->join('users', 'ventas.idusuario', '=', 'users.id')
                ->join('detalle_ventas', 'ventas.id', '=', 'detalle_ventas.idventa')
                ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
                ->join('inventarios', 'articulos.id', '=', 'inventarios.idarticulo')
                ->select(
                    'ventas.id',
                    'ventas.tipo_comprobante',
                    'ventas.serie_comprobante',
                    'ventas.num_comprobante',
                    'ventas.fecha_hora',
                    'ventas.impuesto',
                    'ventas.total',
                    'ventas.estado',
                    'personas.nombre as cliente',
                    'users.usuario',
                    'users.idrol',
                    'detalle_ventas.idarticulo'
                )
                ->distinct()
                ->where(function ($query) use ($idRoles) {
                    if ($idRoles !== null) {
                        $query->where('users.idrol', $idRoles);
                    }
                })
                ->where(function ($query) use ($idAlmacen) {
                    if ($idAlmacen !== null) {
                        $query->where('inventarios.idalmacen', $idAlmacen);
                    }
                })
                ->where('personas.' . $criterio, 'like', '%' . $buscar . '%');

            // Filtrar por fechas
            if ($fechaInicio !== now()->toDateString() || $fechaFin !== now()->addDay()->toDateString()) {
                $ventas->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);
            }

            $ventas = $ventas->orderBy('ventas.id', 'desc')->paginate(6);
        }

        return [
            'pagination' => [
                'total' => $ventas->total(),
                'current_page' => $ventas->currentPage(),
                'per_page' => $ventas->perPage(),
                'last_page' => $ventas->lastPage(),
                'from' => $ventas->firstItem(),
                'to' => $ventas->lastItem(),
            ],
            'ventas' => $ventas,
            'usuario' => $usuario
        ];
    }



    public function obtenerCabecera(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $id = $request->id;
        $venta = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->leftJoin('facturas', 'ventas.id', '=', 'facturas.idventa') // 👈 LEFT JOIN CON FACTURAS
            ->select(
                'ventas.id',
                'ventas.tipo_comprobante',
                'ventas.serie_comprobante',
                'ventas.num_comprobante',
                'ventas.fecha_hora',
                'ventas.impuesto',
                'ventas.total',
                'ventas.estado',
                'ventas.idtipo_venta',
                'personas.nombre',
                'users.usuario',
                'ventas.descuento_total',
                'facturas.descuentoAdicional' // 👈 TRAER EL DESCUENTO ADICIONAL
            )
            ->where('ventas.id', '=', $id)
            ->orderBy('ventas.id', 'desc')
            ->take(1)
            ->get();

        return ['venta' => $venta];
    }
    public function obtenerDetalles(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $id = $request->id;

        $detalles = DetalleVenta::join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->select(
                'detalle_ventas.cantidad',
                'detalle_ventas.precio',
                'detalle_ventas.descuento', // porcentaje
                'detalle_ventas.modo_venta',
                'articulos.nombre as articulo',
                'articulos.unidad_envase',
                'articulos.codigo',

                // 🔹 Subtotal sin descuento (2 decimales)
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN detalle_ventas.modo_venta = 'caja' 
                                THEN detalle_ventas.precio * articulos.unidad_envase * detalle_ventas.cantidad
                            ELSE 
                                detalle_ventas.precio * detalle_ventas.cantidad
                        END
                    , 2) as subtotal_sin_descuento
                "),
                                // 🔹 Descuento en monto (2 decimales)
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN detalle_ventas.modo_venta = 'caja' 
                                THEN (detalle_ventas.precio * articulos.unidad_envase * detalle_ventas.cantidad) * (detalle_ventas.descuento / 100)
                            ELSE 
                                (detalle_ventas.precio * detalle_ventas.cantidad) * (detalle_ventas.descuento / 100)
                        END
                    , 2) as descuento_monto
                "),
                                // 🔹 Subtotal con descuento aplicado (2 decimales)
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN detalle_ventas.modo_venta = 'caja' 
                                THEN (detalle_ventas.precio * articulos.unidad_envase * detalle_ventas.cantidad) 
                                    - ((detalle_ventas.precio * articulos.unidad_envase * detalle_ventas.cantidad) * (detalle_ventas.descuento / 100))
                            ELSE 
                                (detalle_ventas.precio * detalle_ventas.cantidad) 
                                    - ((detalle_ventas.precio * detalle_ventas.cantidad) * (detalle_ventas.descuento / 100))
                        END
                    , 2) as subtotal
                ")
            )
            ->where('detalle_ventas.idventa', '=', $id)
            ->orderBy('detalle_ventas.id', 'asc')
            ->get();

        return ['detalles' => $detalles];
    }


    public function pdf(Request $request, $id)
    {
        $venta = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->select(
                'ventas.id',
                'ventas.tipo_comprobante',
                'ventas.serie_comprobante',
                'ventas.num_comprobante',
                'ventas.created_at',
                'ventas.impuesto',
                'ventas.total',
                'ventas.estado',
                'personas.nombre',
                'personas.tipo_documento',
                'personas.num_documento',
                'personas.direccion',
                'personas.email',
                'personas.telefono',
                'users.usuario'
            )
            ->where('ventas.id', '=', $id)
            ->orderBy('ventas.id', 'desc')->take(1)->get();

        $detalles = DetalleVenta::join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->select(
                'detalle_ventas.cantidad',
                'detalle_ventas.precio',
                'detalle_ventas.descuento',
                'articulos.nombre as articulo'
            )
            ->where('detalle_ventas.idventa', '=', $id)
            ->orderBy('detalle_ventas.id', 'desc')->get();

        $numventa = Venta::select('num_comprobante')->where('id', $id)->get();

        $pdf = \PDF::loadView('pdf.venta', ['venta' => $venta, 'detalles' => $detalles]);
        return $pdf->setPaper('a4', 'landscape')->download('venta-' . $numventa[0]->num_comprobante . '.pdf');
    }

    public function store(Request $request)
    {

        if (!$request->ajax()) {
            return redirect('/');
        }

        $idtipoventa = (int) $request->idtipo_venta;

        try {
            DB::beginTransaction();

            if (!$this->validarCajaAbierta()) {
                return ['id' => -1, 'caja_validado' => 'Debe tener una caja abierta'];
            }
            
            if ($request->tipo_comprobante === "RESIVO") {
                $venta = $this->crearVentaResivo($request);
            } else {
                $venta = $this->crearVenta($request);
            }

            $this->actualizarDescuentoUsuarioLogueado();
            //$this->actualizarPrecios($request->data);
            $this->actualizarCaja($request);
            $this->registrarDetallesVenta($venta, $request->data, $request->idAlmacen);
            $this->notificarAdministradores();
            // 🔹 SI ES VENTA A CRÉDITO => registrar solo el abono que hizo el cliente
            if ($idtipoventa === 2) {
                $this->registrarAbonoInicial($venta, $request);
            }

            DB::commit();

            return ['id' => $venta->id];
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'id'      => 0,
                'message' => 'Error al registrar venta',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    protected function registrarAbonoInicial($venta, Request $request)
    {
        $cuotas = $request->input('cuotas_credito', []);

        if (!is_array($cuotas) || empty($cuotas)) {
            return;
        }

        $primeraCuota = $cuotas[0];
        $usuario = \Auth::user();

        $montoPagado   = $primeraCuota['precio_cuota'] ?? 0;
        $saldoRestante = $primeraCuota['saldo_restante'] ?? 0;
        $idcobrador    = $usuario->id;
        $numeroCuota   = $primeraCuota['numero_cuota'] ?? 1;
        $idtipo_pago   = $primeraCuota['idtipo_pago'] ?? $venta->idtipo_pago;
        $idbanco = $request->idbanco ?? null;
        if ($montoPagado <= 0) {
            return;
        }

        // 🔹 Obtener la última caja de la sucursal del usuario
        $ultimaCaja = Caja::where('idsucursal', $usuario->idsucursal)
            ->latest()
            ->first();

        if (!$ultimaCaja) {
            throw new \Exception("No se encontró una caja para registrar el abono.");
        }

        // 🔥 ACTUALIZAR CAJA SOLO SI EL PAGO ES EFECTIVO
        if ($idtipo_pago == 1) {
            $ultimaCaja->ventasContado += $montoPagado;
            $ultimaCaja->saldototalventas += $montoPagado;
            $ultimaCaja->save();
        }

        // 🔥 REGISTRAR LA CUOTA
        CuotasCredito::create([
            'idcredito'       => $venta->id,
            'idcaja'          => $ultimaCaja->id,
            'idcobrador'      => $idcobrador,
            'numero_cuota'    => $numeroCuota,
            'fecha_pago' => isset($primeraCuota['fecha_pago'])
                ? Carbon::parse($primeraCuota['fecha_pago'])
                    ->setTimeFrom(Carbon::now())
                : now(),

            'fecha_cancelado' => isset($primeraCuota['fecha_cancelado'])
                ? Carbon::parse($primeraCuota['fecha_cancelado'])
                    ->setTimeFrom(Carbon::now())
                : now(),
            'precio_cuota'    => $montoPagado,
            'saldo_restante'  => $saldoRestante,
            'estado'          => $primeraCuota['estado'] ?? 'Pagado',
            'idtipo_pago'     => $idtipo_pago,
            'idbanco'         => $idbanco, 
        ]);
        if ($saldoRestante <= 0) {
            $venta->estado = 1;
        } else {
            $venta->estado = 2;
        }

        $venta->save();
    }


public function abonarCuota(Request $request)
{
    DB::beginTransaction();

    try {
        $venta = Venta::find($request->idventa);
        $usuario = \Auth::user();
        $montoIngresado = floatval($request->monto);
        $tipoPago = intval($request->idtipo_pago);
        // caja actual
        $caja = Caja::where('idsucursal', $usuario->idsucursal)->latest()->first();
        if (!$caja) {
            throw new \Exception("No existe caja abierta.");
        }
        if ($tipoPago === 7 && empty($request->idbanco)) {
            return ['error' => 'Debe seleccionar un banco antes de registrar el pago.'];
        }

        $idbanco = ($tipoPago === 7) ? ($request->idbanco ?: null) : null;

        // Si no existe venta, registrar directamente como saldo a favor del cliente
        if (!$venta) {
            $persona = Persona::where('num_documento', $request->idcliente)->first();

            if (!$persona) {
                return ['error' => 'Cliente no encontrado.'];
            }

            // Registrar saldo a favor en persona
            $persona->saldo_favor = ($persona->saldo_favor ?? 0) + $request->monto;
            $persona->save();

            // Registrar en CuotasCredito con idcredito nulo
            CuotasCredito::create([
                'idcredito'       => null,
                'idcaja'          => $caja->id, // si quieres puedes asignar caja abierta, o dejar null
                'idcobrador'      => $usuario->id,
                'numero_cuota'    => 0,
                'fecha_pago'      => now(),
                'fecha_cancelado' => now(),
                'precio_cuota'    => $request->monto,
                'descuento'       => 0,
                'saldo_restante'  => 0 - $request->monto,
                'estado'          => 'Saldo a Favor',
                'idtipo_pago'     => $tipoPago,
                'idbanco'         => ($tipoPago === 7) ? ($request->idbanco ?: null) : null,
                'idcliente'       => $persona->id,
            ]);
 // Actualizar caja
        if ($tipoPago == 1) {
            $caja->ventasContado += $request->monto;
            $caja->saldototalventas += $request->monto;
            $caja->saldoCaja += $request->monto;
        }
        if ($tipoPago == 7) {
            $caja->ventasQR += $request->monto;
            $caja->saldototalventas += $request->monto;
        }
        $caja->save();
            DB::commit();

            return [
                'success' => true,
                'tipo' => 'saldo_favor',
                'montoRegistrado' => $request->monto,
                'mensaje' => 'Monto registrado directamente como saldo a favor del cliente y en CuotasCredito.'
            ];
        }


        // ============================
        // Si la venta existe, se mantiene la lógica original
        // ============================


        $ultimaCuota = CuotasCredito::where('idcredito', $venta->id)
            ->orderByDesc('numero_cuota')
            ->first();

        $saldoActual = $ultimaCuota ? floatval($ultimaCuota->saldo_restante) : floatval($venta->total);
        $numeroCuota = $ultimaCuota ? $ultimaCuota->numero_cuota + 1 : 1;

        $montoRegistrado = $montoIngresado;
        $nuevoSaldo = $saldoActual - $montoRegistrado;
        $estadoCuota = ($nuevoSaldo < 0) ? 'Saldo a Favor' : 'Cancelado';

        if ($tipoPago === 5) { // descuento
            CuotasCredito::create([
                'idcredito'       => $venta->id,
                'idcaja'          => $caja->id,
                'idcobrador'      => $usuario->id,
                'numero_cuota'    => $numeroCuota,
                'fecha_pago'      => now(),
                'fecha_cancelado' => now(),
                'precio_cuota'    => 0,
                'descuento'       => $montoRegistrado,
                'saldo_restante'  => $nuevoSaldo,
                'estado'          => $estadoCuota,
                'idtipo_pago'     => 5,
                'idbanco'         => null,
            ]);

            $venta->descuento_total = ($venta->descuento_total ?? 0) + $montoRegistrado;
            $venta->total = max(($venta->total - $montoRegistrado), 0);
            $venta->estado = ($nuevoSaldo == 0) ? 1 : 2;
            $venta->save();

            DB::commit();

            return [
                'success' => true,
                'tipo' => 'descuento',
                'montoRegistrado' => $montoRegistrado,
                'nuevoSaldo' => $nuevoSaldo
            ];
        }

        // Cuota normal
        CuotasCredito::create([
            'idcredito'       => $venta->id,
            'idcaja'          => $caja->id,
            'idcobrador'      => $usuario->id,
            'numero_cuota'    => $numeroCuota,
            'fecha_pago'      => now(),
            'fecha_cancelado' => now(),
            'precio_cuota'    => $montoRegistrado,
            'descuento'       => 0,
            'saldo_restante'  => $nuevoSaldo,
            'estado'          => $estadoCuota,
            'idtipo_pago'     => $tipoPago,
            'idbanco'         => $idbanco,
        ]);

        // Actualizar caja
        if ($tipoPago == 1) {
            $caja->ventasContado += $montoRegistrado;
            $caja->saldototalventas += $montoRegistrado;
            $caja->saldoCaja += $montoRegistrado;
        }
        if ($tipoPago == 7) {
            $caja->ventasQR += $montoRegistrado;
            $caja->saldototalventas += $montoRegistrado;
        }
        $caja->save();

        $venta->estado = ($nuevoSaldo == 0) ? 1 : 2;
        $venta->save();
        $this->actualizarSaldoFavorPersona($venta, $nuevoSaldo);

        DB::commit();

        return ['success' => true];

    } catch (\Exception $e) {
        DB::rollBack();
        return ['error' => $e->getMessage()];
    }
}

private function actualizarSaldoFavorPersona($venta, $saldo)
{
    if ($saldo < 0) {
        $persona = Persona::find($venta->idcliente);
        if ($persona) {
            $persona->saldo_favor = ($persona->saldo_favor ?? 0) + abs($saldo);
            $persona->save();
        }
    }
}



public function indexFiltrar(Request $request)
{
    $buscar = $request->buscar;
    $criterio = $request->criterio;

    $tipoVenta = $request->tipo_venta;  // 👈 llega desde Vue

    $query = Venta::join('clientes','ventas.idcliente','=','clientes.id')
        ->select('ventas.*', 'clientes.nombre')
        ->orderBy('ventas.id', 'desc');

    // 🔥 FILTRO DE TIPO DE VENTA
    if ($tipoVenta == 1) {
        $query->where('ventas.idtipo_venta', 1); // Contado
    } 
    else if ($tipoVenta == 2) {
        $query->where('ventas.idtipo_venta', 2); // Crédito
    }

    // tu filtro normal
    if ($buscar != '') {
        $query->where("ventas.$criterio", 'like', "%$buscar%");
    }

    $ventas = $query->paginate(10);

    return [
        'pagination' => [
            'total'        => $ventas->total(),
            'current_page' => $ventas->currentPage(),
            'per_page'     => $ventas->perPage(),
            'last_page'    => $ventas->lastPage(),
            'from'         => $ventas->firstItem(),
            'to'           => $ventas->lastItem(),
        ],
        'ventas' => $ventas
    ];
}



    public function store2(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $idtipoventa = $request->idtipo_venta;

        dd($request->data); // <-- Muestra los datos recibidos


        try {
            DB::beginTransaction();

            if (!$this->validarCajaAbierta()) {
                return ['id' => -1, 'caja_validado' => 'Debe tener una caja abierta'];
            }

            if ($request->tipo_comprobante === "RESIVO") {
                // Crear venta con RESIVO
                $venta = $this->crearVentaResivo2($request);
            } else {
                // Crear venta regular (con factura)
                $venta = $this->crearVenta2($request);
            }

            //$this->actualizarCaja($request);
            $this->actualizarDescuentoUsuarioLogueado();
            $this->actualizarPrecios($request->data);
            $this->registrarDetallesVenta($venta, $request->data, $request->idAlmacen);
            $this->notificarAdministradores();

            DB::commit();
            return ['id' => $venta->id];
        } catch (Exception $e) {
            DB::rollBack();
        }
    }

    private function validarCajaAbierta()
    {
        $usuario = auth()->user(); // Obtener el usuario logueado
        $ultimaCaja = Caja::where('idsucursal', $usuario->idsucursal) // Filtrar por sucursal del usuario
            ->latest()
            ->first();
        return $ultimaCaja && $ultimaCaja->estado == '1'; // Verificar si la última caja está abierta
    }

    private function actualizarPrecios($detalles)
    {
        foreach ($detalles as $det) {

            if (!isset($det['idarticulo']) || !isset($det['precioseleccionado'])) {
                continue; // Evita errores si faltan datos
            }

            // Si el artículo es el 4648, no actualizar su precio
            if ($det['idarticulo'] == 4648) {
                continue;
            }

            // Convertir precio a decimal (float) con 4 decimales
            $precio = number_format((float) $det['precioseleccionado'], 4, '.', '');

            $articulo = Articulo::find($det['idarticulo']); // Buscar artículo por ID

            if ($articulo) {

                // 🔹 GUARDAR PRECIO ANTERIOR
                $precioOriginal = round(floatval($articulo->precio_uno), 4);
                $precioNuevo = round(floatval($precio), 4);

                // 🔹 SI EL PRECIO CAMBIA, ACTUALIZAR FECHA
                if ($precioOriginal !== $precioNuevo) {
                    $articulo->precio_actualizado_en = now();
                }

                // 🔹 ACTUALIZAR PRECIO
                $articulo->precio_uno = $precio;

                $articulo->save(); // Guardar cambios
            }
        }
    }

    private function calcularDescuentoMaximo($detalles)
    {
        $descuento = 0;
        foreach ($detalles as $ep => $det) {
            $descuento = $det['descuento'];
        }
        return $descuento;
    }

    private function crearVenta($request)
    {
        if ((int)$request->idtipo_pago === 7 && empty($request->idbanco)) {
            throw new \Exception("Debe seleccionar un banco antes de registrar la venta.");

        }
        $venta = new Venta();
        $venta->fill($request->only([
            'idcliente',
            'idtipo_pago',
            'idtipo_venta',
            'tipo_comprobante',
            'serie_comprobante',
            'num_comprobante',
            'descuento_total',
            'impuesto',
            'total',
            'idbanco'
        ]));

        // Usuario logueado
        $usuario = \Auth::user();


        // Asignar manualmente campos adicionales
        $venta->idusuario = $usuario->id;
        $venta->idsucursal = $usuario->idsucursal; // ✅ Nuevo: guardar la sucursal del usuario logueado
        $venta->fecha_hora = now()->setTimezone('America/La_Paz');

        // Asignar idalmacen desde el request (llega como idAlmacen)
        $venta->idalmacen = $request->idAlmacen;

        // Estado: 2 si es crédito, 1 si es contado
        $venta->estado = $request->idtipo_venta == 2 ? 2 : 1;

        // Obtener la última caja abierta de la sucursal del usuario logueado
        $ultimaCajaAbierta = Caja::where('idsucursal', $usuario->idsucursal)
            ->where('estado', '1') // Caja abierta
            ->latest()
            ->first();

        if (!$ultimaCajaAbierta) {
            throw new \Exception('No hay una caja abierta para la sucursal del usuario.');
        }

        $venta->idcaja = $ultimaCajaAbierta->id;
        if ($request->idtipo_venta == 1 && $request->idtipo_pago == 7) {
            // Pago contado por banco
            $venta->idbanco = $request->idbanco;  // Debe venir desde el frontend
        } else {
            // Cualquier otro caso NO lleva banco
            $venta->idbanco = null;
        }


        // Guardar la venta
        $venta->save();

        // 🔹 Si se usó saldo a favor del cliente, vaciarlo
        $saldoFavorUsado = $request->input('saldo_favor_usado', 0);
        if ($saldoFavorUsado > 0 && $request->idcliente) {
            $persona = \App\Persona::find($request->idcliente);
            if ($persona) {
                $persona->saldo_favor = 0;
                $persona->save();
            }
        }
/*
        // Si es crédito, registrar crédito y cuotas
        if ($request->idtipo_venta == 2) {
            $creditoventa = $this->crearCreditoVenta($venta, $request);
            $this->registrarCuotasCredito($creditoventa, $request->cuotaspago);
        }*/

        return $venta;
    }

    private function crearVenta2($request)
    {
        $venta = new Venta();
        $venta->fill($request->only([
            'idcliente',
            'idtipo_pago',
            'idtipo_venta',
            'tipo_comprobante',
            'serie_comprobante',
            'num_comprobante',
            'descuento_total',
            'impuesto',
            'total'
        ]));

        // Usuario logueado
        $usuario = \Auth::user();

        // Asignar manualmente campos adicionales
        $venta->idusuario = $usuario->id;
        $venta->idsucursal = $usuario->idsucursal; // ✅ Nuevo: guardar la sucursal del usuario
        $venta->fecha_hora = now()->setTimezone('America/La_Paz');

        // Asignar idalmacen desde el request (llega como idAlmacen)
        $venta->idalmacen = $request->idAlmacen;

        // Estado: 2 si es crédito, 1 si es contado
        $venta->estado = $request->idtipo_venta == 2 ? 2 : 1;

        // Obtener la última caja abierta de la sucursal del usuario logueado
        $ultimaCajaAbierta = Caja::where('idsucursal', $usuario->idsucursal)
            ->where('estado', '1') // Caja abierta
            ->latest()
            ->first();

        if (!$ultimaCajaAbierta) {
            throw new \Exception('No hay una caja abierta para la sucursal del usuario.');
        }

        $venta->idcaja = $ultimaCajaAbierta->id;

        // Guardar la venta
        $venta->save();

        // Si es crédito, registrar crédito y cuotas
        if ($request->idtipo_venta == 2) {
            $creditoventa = $this->crearCreditoVenta($venta, $request);
            $this->registrarCuotasCredito($creditoventa, $request->cuotaspago);
        }

        return $venta;
    }

    private function actualizarCaja($request)
    {
        $usuario = \Auth::user(); // Obtener el usuario logueado

        // Obtener la última caja de la sucursal del usuario logueado
        $ultimaCaja = Caja::where('idsucursal', $usuario->idsucursal)
            ->latest()
            ->first();

        if (!$ultimaCaja) {
            throw new \Exception('No se encontró una caja en la sucursal del usuario.');
        }

        if ($request->idtipo_pago == 1) {
            // Actualizar caja en ventas y ventas efectivo
            if ($request->idtipo_venta == 2) {
                // Sumar a ventas crédito
                $ultimaCaja->saldoCaja += $request->primer_precio_cuota;
            } else {
                // Sumar a ventas contado
                $ultimaCaja->ventasContado += $request->total;
                $ultimaCaja->saldoCaja += $request->total;
                $ultimaCaja->saldototalventas += $request->total;
            }
        } elseif ($request->idtipo_pago == 7) {
            if ($request->idtipo_venta == 2) {
                // Sumar a ventas crédito
                $ultimaCaja->ventasQR += $request->primer_precio_cuota;
                $ultimaCaja->saldototalventas += $request->primer_precio_cuota;
            } else {
                // Sumar a ventas contado
                //$ultimaCaja->ventasQR += $request->total;
                $ultimaCaja->saldototalventas += $request->total;
            }
        }

        $ultimaCaja->save();
    }


    private function registrarDetallesVenta($venta, $detalles, $idAlmacen)
    {
        $detallesAgrupados = [];

        foreach ($detalles as $det) {
            $id = $det['idarticulo'];

            if (!isset($detallesAgrupados[$id])) {
                $detallesAgrupados[$id] = [
                    'idarticulo' => $id,
                    'cantidad' => $det['cantidad'],
                    'precio' => $det['precioseleccionado'],
                    'descuento' => $det['descuento'],
                    'modoVenta' => $det['modoVenta'],
                    'unidad_envase' => $det['unidad_envase']
                ];
            } else {
                $detallesAgrupados[$id]['cantidad'] += $det['cantidad'];
                $detallesAgrupados[$id]['descuento'] += $det['descuento'];
            }
        }

        foreach ($detallesAgrupados as $det) {
            $detalleVenta = new DetalleVenta();
            $detalleVenta->idventa = $venta->id;
            $detalleVenta->idarticulo = $det['idarticulo'];
            $detalleVenta->cantidad = $det['cantidad'];
            $detalleVenta->precio = $det['precio'];
            $detalleVenta->descuento = $det['descuento']; // monto total acumulado
            $detalleVenta->modo_venta = $det['modoVenta'];
            $detalleVenta->save();

            // Actualizar inventario con la estructura necesaria
            $this->actualizarInventario($idAlmacen, [
                'idarticulo' => $det['idarticulo'],
                'cantidad' => $det['cantidad'],
                'modoVenta' => $det['modoVenta'],
                'unidad_envase' => $det['unidad_envase']

            ]);
        }

        $_SESSION['sidAlmacen'] = $idAlmacen;
        $_SESSION['sdetalle'] = $detallesAgrupados;
    }


    private function actualizarInventario($idAlmacen, $detalle)
{
    // 🔹 Determinar cuántas unidades reales descontar
    $cantidadReal = $detalle['modoVenta'] === 'caja'
        ? $detalle['cantidad'] * $detalle['unidad_envase']
        : $detalle['cantidad'];

    $cantidadRestante = $cantidadReal;
    $fechaActual = now();

    $inventarios = Inventario::where('idalmacen', $idAlmacen)
        ->where('idarticulo', $detalle['idarticulo'])
        ->orderBy('fecha_vencimiento', 'asc')
        ->get();

    foreach ($inventarios as $inventario) {

        if ($cantidadRestante <= 0) break;

        if ($inventario->saldo_stock >= $cantidadRestante) {
            // stock suficiente
            $inventario->saldo_stock -= $cantidadRestante;
            $cantidadRestante = 0;
        } else {
            // consumir todo el lote y seguir con el siguiente
            $cantidadRestante -= $inventario->saldo_stock;
            $inventario->saldo_stock = 0;
        }

        $inventario->save();
    }
}


    private function revertirInventario()
    {
        $idAlmacen = $_SESSION['sidAlmacen'];
        $detalles = $_SESSION['sdetalle'];

        foreach ($detalles as $detalle) {
            $inventario = Inventario::where('idalmacen', $idAlmacen)
                ->where('idarticulo', $detalle['idarticulo'])
                ->firstOrFail();
            $inventario->saldo_stock += $detalle['cantidad'];
            $inventario->save();
        }
    }


    public function eliminarVenta($id)
    {
        $ultimaCaja = Caja::latest()->first();

        try {
            $venta = Venta::findOrFail($id);
            $idTipoPago = $venta->idtipo_pago;

            // Disminuir el saldo de la caja dependiendo del tipo de pago
            switch ($idTipoPago) {
                case 1: // Efectivo
                    $ultimaCaja->ventasContado -= $venta->total;
                    $ultimaCaja->saldototalventas -= ($venta->total);
                    $ultimaCaja->saldoCaja -= $venta->total;
                    break;
                case 7: // QR
                    $ultimaCaja->ventasQR -= ($venta->total);
                    $ultimaCaja->saldototalventas -= ($venta->total);
                    break;
                case 2: // Tarjeta
                    $ultimaCaja->ventasTarjeta -= ($venta->total);
                    $ultimaCaja->saldototalventas -= ($venta->total);
                    break;
                default:
                    // Manejo de otros tipos de pago si es necesario
                    break;
            }

            $ultimaCaja->save();
            $venta->delete();
            $this->revertirInventario();
            return response()->json('Venta eliminada correctamente', 200);
        } catch (\Exception $e) {
            return response()->json('Error al eliminar la venta: ' . $e->getMessage(), 500);
        }
    }

    public function eliminarVentaFalloSiat($id)
    {
        $ultimaCaja = Caja::latest()->first();

        // Verificar que las variables de sesión estén definidas
        if (!isset($_SESSION['sidAlmacen']) || !isset($_SESSION['sdetalle'])) {
            return response()->json('Error: Variables de sesión no definidas.', 400);
        }

        // Obtener el ID del almacén y los detalles de la venta de la sesión
        $idAlmacen = $_SESSION['sidAlmacen'];
        $detalles = $_SESSION['sdetalle'];

        try {
            DB::beginTransaction();

            // Revertir el inventario
            foreach ($detalles as $det) {
                // Si el código de comida está en la tabla Inventario, aumentar el stock
                $disminuirStock = Inventario::join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
                    ->where('inventarios.idalmacen', $idAlmacen)
                    ->where('articulos.id', $det['idarticulo'])
                    ->firstOrFail();
                $disminuirStock->saldo_stock += $det['cantidad'];
                $disminuirStock->save();
            }

            // Eliminar todas las facturas relacionadas con la venta
            $facturas = Factura::where('idventa', $id)->get();
            foreach ($facturas as $factura) {
                $factura->delete();
            }

            // Eliminar todos los detalles de la venta
            $detallesVenta = DetalleVenta::where('idventa', $id)->get();
            foreach ($detallesVenta as $detalle) {
                $detalle->delete();
            }

            // Obtener el tipo de pago de la venta
            $venta = Venta::findOrFail($id);
            $idTipoPago = $venta->idtipo_pago;

            // Disminuir el saldo de la caja dependiendo del tipo de pago
            switch ($idTipoPago) {
                case 1: // Efectivo
                    $ultimaCaja->ventasContado -= $venta->total;
                    $ultimaCaja->saldototalventas -= ($venta->total - $venta->tarifaDelivery);
                    $ultimaCaja->saldoCaja -= $venta->total;
                    break;
                case 7: // QR
                    $ultimaCaja->ventasQR -= ($venta->total - $venta->tarifaDelivery);
                    $ultimaCaja->saldototalventas -= ($venta->total - $venta->tarifaDelivery);
                    break;
                case 2: // Tarjeta
                    $ultimaCaja->ventasTarjeta -= ($venta->total - $venta->tarifaDelivery);
                    $ultimaCaja->saldototalventas -= ($venta->total - $venta->tarifaDelivery);
                    break;
                default:
                    // Manejo de otros tipos de pago si es necesario
                    break;
            }

            $ultimaCaja->save();
            $venta->delete();

            DB::commit();
            return response()->json('Venta eliminada correctamente', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('Error al eliminar la venta: ' . $e->getMessage(), 500);
        }
    }

    private function notificarAdministradores()
    {
        $fechaActual = date('Y-m-d');
        $numVentas = Venta::whereDate('created_at', $fechaActual)->count();
        $numIngresos = Ingreso::whereDate('created_at', $fechaActual)->count();

        $arreglosDatos = [
            'ventas' => ['numero' => $numVentas, 'msj' => 'Ventas'],
            'ingresos' => ['numero' => $numIngresos, 'msj' => 'Ingresos']
        ];

        $allUsers = User::all();

        foreach ($allUsers as $notificar) {
            $user = User::findOrFail($notificar->id);
            $user->notify(new NotifyAdmin($arreglosDatos));
        }
    }


    public function desactivar(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        DB::beginTransaction();

        try {

            $venta = Venta::findOrFail($request->id);
            $venta->estado = '0';
            $venta->save();

            $idAlmacen = $venta->idalmacen;

            $detalles = DetalleVenta::where('idventa', $venta->id)->get();

            foreach ($detalles as $detalle) {

                $inventario = Inventario::where('idarticulo', $detalle->idarticulo)
                    ->where('idalmacen', $idAlmacen)
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->first();

                if (!$inventario) {
                    throw new \Exception('Inventario no encontrado');
                }

                $articulo = Articulo::find($detalle->idarticulo);
                if (!$articulo) {
                    throw new \Exception('Artículo no encontrado');
                }

                if (strtolower($detalle->modo_venta) === 'caja') {

                    $unidadesPorCaja = (int) $articulo->unidad_envase;
                    if ($unidadesPorCaja <= 0) {
                        throw new \Exception('unidad_envase no configurado');
                    }

                    $unidadesDevueltas = $detalle->cantidad * $unidadesPorCaja;
                    $inventario->saldo_stock += $unidadesDevueltas;

                } else {
                    // UNIDAD
                    $inventario->saldo_stock += $detalle->cantidad;
                }

                $inventario->save();
            }

            // Caja
            $usuario = Auth::user();
            $ultimaCaja = Caja::where('idsucursal', $usuario->idsucursal)
                ->where('estado', '1')
                ->latest()
                ->first();

            if ($ultimaCaja) {
                // Verificar si es venta a crédito (idtipo_venta = 2)
                if ($venta->idtipo_venta == 2) {

                    // Obtener cuotas PAGADAS
                    $cuotasPagadas = CuotasCredito::where('idcredito', $venta->id)
                        ->where('estado', 'Cancelado')
                        ->get();

                    $montoEfectivo = 0;
                    $montoQR = 0;

                    foreach ($cuotasPagadas as $cuota) {

                        if ($cuota->idtipo_pago == 1) {
                            $montoEfectivo += $cuota->precio_cuota;

                            $ultimaCaja->ventasContado -= $cuota->precio_cuota;

                        }

                        elseif ($cuota->idtipo_pago == 7) {
                            $montoQR += $cuota->precio_cuota;

                            $ultimaCaja->ventasQR -= $cuota->precio_cuota;
                        }
                    }

                    $totalPagado = $montoEfectivo + $montoQR;

                    // Solo si hubo pagos
                    if ($totalPagado > 0) {

                        $ultimaCaja->saldototalventas -= $totalPagado;

                        // Caja SOLO efectivo
                        $ultimaCaja->saldoCaja -= $montoEfectivo;

                        TransaccionesCaja::create([
                            'idcaja'      => $ultimaCaja->id,
                            'idusuario'   => $usuario->id,
                            'fecha'       => now()->setTimezone('America/La_Paz'),
                            'transaccion' => 'Anulación de venta crédito',
                            'importe'     => $totalPagado
                        ]);
                    }
                }else {

                    $ultimaCaja->saldototalventas -= $venta->total;
                    
                    // Restar según el tipo de pago
                    if ($venta->idtipo_pago == 1) {
                        // Tipo pago 1 = Efectivo: restar de ventasContado
                        $ultimaCaja->ventasContado -= $venta->total;

                    } elseif ($venta->idtipo_pago == 7) {
                        // Tipo pago 7 = QR/Banco: restar de ventasQR
                        $ultimaCaja->ventasQR -= $venta->total;
                    }
                    
                    // Actualizar saldo de caja
                    $ultimaCaja->saldoCaja -= $venta->total;

                    TransaccionesCaja::create([
                        'idcaja'      => $ultimaCaja->id,
                        'idusuario'   => $usuario->id,
                        'fecha'       => now()->setTimezone('America/La_Paz'),
                        'transaccion' => 'Anulación de venta',
                        'importe'     => $venta->total
                    ]);
                }
                
                $ultimaCaja->save();
            }

            DB::commit();

            return response()->json([
                'mensaje' => 'Venta anulada correctamente.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }



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
        $user = Auth::user();

        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $empresa = $sucursal->empresas;
        $codnit = $empresa->nit;
        $codSucursal = $sucursal->codigoSucursal;

        require 'SiatController.php';
        $siat = new SiatController();
        $res = $siat->cuis($puntoVenta, $codSucursal, $codnit);
        //dd($res);
        $res->RespuestaCuis->codigo;
        $_SESSION['scuis'] = $res->RespuestaCuis->codigo;
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function nuevoCufd()
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }
        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $empresa = $sucursal->empresas;
        $codnit = $empresa->nit;
        $codSucursal = $sucursal->codigoSucursal;

        require 'SiatController.php';
        $siat = new SiatController();
        $res = $siat->cufd($puntoVenta, $codSucursal, $codnit);
        // dd($res);

        if ($res->RespuestaCufd->transaccion == true) {
            $cufd = $res->RespuestaCufd->codigo;
            $codigoControl = $res->RespuestaCufd->codigoControl;
            $direccion = $res->RespuestaCufd->direccion;
            $fechaVigencia = $res->RespuestaCufd->fechaVigencia;

            $_SESSION['scufd'] = $cufd;
            $_SESSION['scodigoControl'] = $codigoControl;
            $_SESSION['sdireccion'] = $direccion;
            $_SESSION['sfechaVigenciaCufd'] = $fechaVigencia;

            $res = $res->RespuestaCufd;

            /*$res['transaccion'] = $res->RespuestaCufd->transaccion;
            $res['codigo'] = $_SESSION['scufd'];
            $res['fechaVigencia'] = $_SESSION['sfechaVigenciaCufd'];
            $res['direccion'] = $_SESSION['sdireccion'];
            $res['codigoControl'] = $_SESSION['scodigoControl'];*/
        } else {
            $res = false;
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }
    public function cufd()
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }
        //$puntoVenta = $user->idpuntoventa;
        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $empresa = $sucursal->empresas;
        $codnit = $empresa->nit;
        $codSucursal = $sucursal->codigoSucursal;

        if (!isset($_SESSION['scufd'])) {
            require "SiatController.php";
            $siat = new SiatController();
            $res = $siat->cufd($puntoVenta, $codSucursal, $codnit);
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
                $res = $siat->cufd($puntoVenta, $codSucursal);
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
                $res['codigoControl'] = $_SESSION['scodigoControl'];
            }
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }


    public function sincronizarActividades()
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;

        require 'SiatController.php';
        $siat = new SiatController();
        $res = $siat->sincronizarActividades($puntoVenta, $codSucursal);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }


    public function sincronizarParametricaTiposFactura()
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }
        //$puntoVenta = $user->idpuntoventa;
        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;

        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->sincronizarParametricaTiposFactura($puntoVenta, $codSucursal);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function sincronizarParametricaMotivoAnulacion()
    {
        $user = Auth::user();
        $puntoVenta = $user->idpuntoventa;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;

        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->sincronizarParametricaMotivoAnulacion($puntoVenta, $codSucursal);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function sincronizarParametricaEventosSignificativos()
    {
        $user = Auth::user();
        $puntoVenta = $user->idpuntoventa;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;

        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->sincronizarParametricaEventosSignificativos($puntoVenta, $codSucursal);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }



    public function sincronizarParametricaUnidadMedida()
    {
        $user = Auth::user();

        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;

        require 'SiatController.php';
        $siat = new SiatController();
        $res = $siat->sincronizarParametricaUnidadMedida($puntoVenta, $codSucursal);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function verificarNit($numeroDocumento)
    {
        $user = Auth::user();
        $sucursal = $user->sucursal;
        $empresa = $sucursal->empresas;
        $codnit = $empresa->nit;
        $codSucursal = $sucursal->codigoSucursal;

        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->verificarNit($codSucursal, $numeroDocumento, $codnit);
        if ($res->RespuestaVerificarNit->transaccion === true) {
            $mensaje = $res->RespuestaVerificarNit->mensajesList->descripcion;
        } else if ($res->RespuestaVerificarNit->transaccion === false) {
            $mensaje = $res->RespuestaVerificarNit->transaccion;
        }

        echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
    }

    public function anulacionFactura($cuf, $motivoSeleccionado, $opcionReposicionCaja, $id, $total)
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $empresa = $sucursal->empresas;
        $codnit = $empresa->nit;
        $codSucursal = $sucursal->codigoSucursal;

        require 'SiatController.php';
        $siat = new SiatController();
        $res = $siat->anulacionFactura($cuf, $motivoSeleccionado, $puntoVenta, $codSucursal, $codnit);

        if ($res->RespuestaServicioFacturacion->transaccion === true) {
            $mensaje = $res->RespuestaServicioFacturacion->codigoDescripcion;

            // ✅ Solo si la transacción fue exitosa, ejecutar la lógica de anulación
            $ultimaCaja = Caja::where('idsucursal', $user->idsucursal)
                ->where('estado', '1')  // caja abierta
                ->latest()
                ->first();

            if (!$ultimaCaja) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No hay una caja abierta en la sucursal. No se puede anular la venta.'
                ], 400);
            }

            $venta = Venta::findOrFail($id);
            $venta->estado = '0';
            $venta->save();

            $tipoReposicion = $opcionReposicionCaja;  // 'efectivo' o 'qr'
            if ($tipoReposicion === 'efectivo') {
                $ultimaCaja->saldoCaja -= $total;
                $ultimaCaja->save();

                TransaccionesCaja::create([
                    'idcaja' => $ultimaCaja->id,
                    'idusuario' => $user->id,
                    'fecha' => now()->setTimezone('America/La_Paz'),
                    'transaccion' => 'Anulación de venta (Reposición EFECTIVO)',
                    'importe' => $total,
                    'idventa' => $id
                ]);
            } elseif ($tipoReposicion === 'qr') {
                $ultimaCaja->save();

                TransaccionesCaja::create([
                    'idcaja' => $ultimaCaja->id,
                    'idusuario' => $user->id,
                    'fecha' => now()->setTimezone('America/La_Paz'),
                    'transaccion' => 'Anulación de venta (Reposición QR)',
                    'importe' => $total,
                    'idventa' => $id
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => $mensaje
            ]);

        } else {
            // 🚫 Si la transacción no fue exitosa, no se toca la venta ni la caja
            $mensaje = $res->RespuestaServicioFacturacion->mensajesList->descripcion ?? 'ANULACION RECHAZADA';

            return response()->json([
                'success' => false,
                'mensaje' => $mensaje
            ]);
        }
    }

    public function emitirFactura(Request $request)
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $empresa = $sucursal->empresas;
        $codnit = $empresa->nit;
        $codSucursal = $sucursal->codigoSucursal;

        $datos = $request->input('factura');
        $id_cliente = $request->input('id_cliente');
        $idventa = $request->input('idventa');
        $correo = $request->input('correo');
        $cufd = $request->input('cufd');

        $valores = $datos['factura'][0]['cabecera'];
        $nitEmisor = str_pad($valores['nitEmisor'], 13, "0", STR_PAD_LEFT);

        $fechaEmision = $valores['fechaEmision'];
        $fecha_formato = str_replace("T", "", $fechaEmision);
        $fecha_formato = str_replace("-", "", $fecha_formato);
        $fecha_formato = str_replace(":", "", $fecha_formato);
        $fecha_formato = str_replace(".", "", $fecha_formato);
        $sucursal = str_pad($codSucursal, 4, "0", STR_PAD_LEFT);
        //dd($sucursal);
        $modalidad = 2;
        $tipoEmision = 1;
        $tipoFactura = 1;
        $tipoDocSector = str_pad(1, 2, "0", STR_PAD_LEFT);
        $numeroFactura = str_pad($valores['numeroFactura'], 10, "0", STR_PAD_LEFT);
        $puntoVentaCuf = str_pad($puntoVenta, 4, "0", STR_PAD_LEFT);
        $codigoControl = $_SESSION['scodigoControl'];
        $cadena = $nitEmisor . $fecha_formato . $sucursal . $modalidad . $tipoEmision . $tipoFactura . $tipoDocSector . $numeroFactura . $puntoVentaCuf;
        $numDig = 1;
        $limMult = 9;
        $x10 = false;
        $mod11 = CustomHelpers::calculaDigitoMod11($cadena, $numDig, $limMult, $x10);
        $cadena2 = $cadena . $mod11;

        $pString = $cadena2;
        $bas16 = CustomHelpers::base16($pString);

        $cuf = strtoupper($bas16) . $codigoControl;

        $datos['factura'][0]['cabecera']['cuf'] = $cuf;

        $temporal = $datos['factura'];
        //dd($temporal);
        $xml_temporal = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><facturaComputarizadaCompraVenta xsi:noNamespaceSchemaLocation=\"facturaComputarizadaCompraVenta.xsd\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"></facturaComputarizadaCompraVenta>");

        $this->formato_xml($temporal, $xml_temporal);
        $xml_temporal->asXML(public_path("docs/facturaxml.xml"));
        $gzdata = gzencode(file_get_contents(public_path("docs/facturaxml.xml")), 9);
        $fp = fopen(public_path("docs/facturaxml.xml.zip"), "w");
        fwrite($fp, $gzdata);
        fclose($fp);
        $archivo = $gzdata;
        $hashArchivo = hash("sha256", file_get_contents(public_path("docs/facturaxml.xml")));

        $numeroFactura = $valores['numeroFactura'];
        $codigoMetodoPago = $valores['codigoMetodoPago'];
        $montoTotal = $valores['montoTotal'];
        $montoTotalSujetoIva = $valores['montoTotalSujetoIva'];
        $descuentoAdicional = $valores['descuentoAdicional'];
        $productos = file_get_contents(public_path("docs/facturaxml.xml"));

        require "SiatController.php";
        $siat = new SiatController();
        $resFactura = $siat->recepcionFactura($archivo, $fechaEmision, $hashArchivo, $puntoVenta, $codSucursal, $codnit);
        //dd($resFactura);

        $idFactura = null;

        if ($resFactura->RespuestaServicioFacturacion->codigoDescripcion === "VALIDADA") {
            $mensaje = $resFactura->RespuestaServicioFacturacion->codigoDescripcion;
            $facturaResponse = $this->insertarFactura(
                $request,
                $id_cliente,
                $idventa,
                $numeroFactura,
                $cuf,
                $cufd,
                $codigoControl,
                $correo,
                $fechaEmision,
                $codigoMetodoPago,
                $montoTotal,
                $montoTotalSujetoIva,
                $descuentoAdicional,
                $productos
            );

            // Verificar si la factura se insertó correctamente y obtener el ID
            if ($facturaResponse->getData()->success) {
                $idFactura = $facturaResponse->getData()->id;
            } else {
                $mensaje = "Error al insertar la factura";
            }
        } else if ($resFactura->RespuestaServicioFacturacion->codigoDescripcion === "RECHAZADA") {
            $mensaje = $resFactura->RespuestaServicioFacturacion->mensajesList->descripcion;
        }

        echo json_encode(['mensaje' => $mensaje, 'idFactura' => $idFactura], JSON_UNESCAPED_UNICODE);

    }

    public function insertarFactura(Request $request, $id_cliente, $idventa, $numeroFactura, $cuf, $cufd, $codigoControl, $correo, $fechaEmision, $codigoMetodoPago, $montoTotal, $montoTotalSujetoIva, $descuentoAdicional, $productos)
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Acceso no autorizado'], 401);
        }

        $factura = new Factura();
        $factura->idventa = $idventa;
        $factura->idcliente = $id_cliente;
        $factura->numeroFactura = $numeroFactura;
        $factura->cuf = $cuf;
        $factura->cufd = $cufd;
        $factura->codigoControl = $codigoControl;
        $factura->correo = $correo;
        $factura->fechaEmision = $fechaEmision;
        $factura->codigoMetodoPago = $codigoMetodoPago;
        $factura->montoTotal = $montoTotal;
        $factura->montoTotalSujetoIva = $montoTotalSujetoIva;
        $factura->descuentoAdicional = $descuentoAdicional;
        $factura->productos = $productos;
        $factura->estado = 1;

        $success = $factura->save();

        if ($success) {
            return response()->json(['success' => true, 'id' => $factura->id]);
        } else {
            return response()->json(['success' => false, 'error' => 'No se pudo guardar la factura']);
        }
    }

    public function enviarPaquete(Request $request)
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }
        //$puntoVenta = $user->idpuntoventa;
        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;
        // Ruta al directorio que deseas comprimir en el archivo TAR
        $carpetaFuente = public_path("docs/temporal");
        // Nombre del archivo TAR resultante
        $nombreArchivoTAR = 'docs/temporal.tar';

        try {
            // Obtener la lista de archivos en el directorio
            $archivosEnDirectorio = scandir($carpetaFuente);

            $archivos = array_diff($archivosEnDirectorio, array('.', '..'));

            // Obtener el número de archivos en la carpeta
            $numeroFacturas = count($archivos);

            // Verificar si la cantidad de archivos excede 500
            if ($numeroFacturas > 500) {
                // Si supera el límite, muestra un mensaje de error
                echo 'La cantidad de archivos excede el límite de 500.';
                return;
            }

            // Crear un objeto PharData para el archivo TAR
            $tar = new PharData($nombreArchivoTAR);

            // Agregar el contenido del directorio al archivo TAR
            $tar->buildFromDirectory($carpetaFuente);

            // Comprimir el archivo TAR utilizando Gzip
            $gzdata = gzencode(file_get_contents(public_path($nombreArchivoTAR)), 9);
            $fp = fopen(public_path("docs/temporal.tar.zip"), "w");
            fwrite($fp, $gzdata);
            fclose($fp);
            $archivo = $gzdata;
            $hashArchivo = hash("sha256", file_get_contents(public_path("docs/temporal.tar.zip")));
            $nombreArchivoZIP = public_path("docs/temporal.tar.zip");

            require "SiatController.php";
            $siat = new SiatController();
            $res = $siat->recepcionPaqueteFactura($archivo, $request->fechaEmision, $hashArchivo, $numeroFacturas, $puntoVenta, $codSucursal);
            // Verificar el valor de transacción y asignar el mensaje correspondiente
            if ($res->RespuestaServicioFacturacion->codigoDescripcion === "PENDIENTE") {
                $mensaje = $res->RespuestaServicioFacturacion->codigoDescripcion;
                $_SESSION['scodigorecepcion'] = $res->RespuestaServicioFacturacion->codigoRecepcion;

                // Eliminar el archivo TAR si existe
                if (file_exists($nombreArchivoTAR)) {
                    unlink($nombreArchivoTAR);
                }
                // Eliminar el archivo ZIP si existe
                if (file_exists($nombreArchivoZIP)) {
                    unlink($nombreArchivoZIP);
                }
                // Eliminar la carpeta temporal si existe y está vacía
                if (is_dir($carpetaFuente)) {
                    $this->eliminarDirectorio($carpetaFuente);
                }
            } else if ($res->RespuestaServicioFacturacion->codigoDescripcion === "RECHAZADA") {
                $mensajes = $res->RespuestaServicioFacturacion->mensajesList;

                if (is_array($mensajes)) {
                    $descripciones = array_map(function ($mensaje) {
                        return $mensaje->descripcion;
                    }, $mensajes);
                    $mensaje = $descripciones;
                }
            }
            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
            //var_dump($res);

        } catch (Exception $e) {
            echo "Error al crear el archivo TAR comprimido o al enviarlo al servicio: " . $e->getMessage();
        }
    }



    public function eliminarDirectorio($directorio)
    {
        if (!is_dir($directorio)) {
            return;
        }

        $archivos = glob($directorio . '/*');
        foreach ($archivos as $archivo) {
            is_dir($archivo) ? $this->eliminarDirectorio($archivo) : unlink($archivo);
        }

        rmdir($directorio);
    }





    public function formato_xml($temporal, $xml_temporal)
    {
        $ns_xsi = "http://www.w3.org/2001/XMLSchema-instance";
        foreach ($temporal as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnodo = $xml_temporal->addChild("$key");
                    $this->formato_xml($value, $subnodo);
                } else {
                    $this->formato_xml($value, $xml_temporal);
                }
            } else {
                if ($value == null && $value <> '0') {
                    $hijo = $xml_temporal->addChild("$key", "$value");
                    $hijo->addAttribute('xsi:nil', 'true', $ns_xsi);
                } else {
                    $xml_temporal->addChild("$key", "$value");
                }
            }
        }
    }


    public function registroEventoSignificativo(Request $request)
    {
        $user = Auth::user();
        $puntoVenta = $user->idpuntoventa;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;

        $descripcion = $request->descripcion;
        $cufdEvento = $request->cufdEvento;
        $codigoMotivoEvento = $request->codigoMotivoEvento;
        $inicioEvento = $request->inicioEvento;
        $finEvento = $request->finEvento;

        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->registroEventoSignificativo($descripcion, $cufdEvento, $codigoMotivoEvento, $inicioEvento, $finEvento, $puntoVenta, $codSucursal);
        // Verificar el valor de transacción y asignar el mensaje correspondiente
        if ($res->RespuestaListaEventos->transaccion === true) {
            $mensaje = $res->RespuestaListaEventos->codigoRecepcionEventoSignificativo;
            $_SESSION['scodigoevento'] = $res->RespuestaListaEventos->codigoRecepcionEventoSignificativo;
        } else {
            $mensaje = $res->RespuestaListaEventos->mensajesList->descripcion;
        }

        // Imprimir o retornar el mensaje, o realizar otras acciones según tu necesidad
        echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
        //var_dump($res);
    }

    public function registroPuntoVenta(Request $request)
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;

        $nombre = $request->nombre;
        $descripcion = $request->descripcion;
        $nit = $request->nit;
        $idtipopuntoventa = $request->idtipopuntoventa;
        $idsucursal = $request->idsucursal;

        require 'SiatController.php';
        $siat = new SiatController();
        $res = $siat->registroPuntoVenta($nombre, $descripcion, $nit, $idtipopuntoventa, $idsucursal, $puntoVenta, $codSucursal);
        // Verificar el valor de transacción y asignar el mensaje correspondiente
        if ($res->RespuestaRegistroPuntoVenta->transaccion === true) {
            $mensaje = $res->RespuestaRegistroPuntoVenta->codigoPuntoVenta;
        } else {
            $mensaje = $res->RespuestaRegistroPuntoVenta->mensajesList->descripcion;
        }

        // Imprimir o retornar el mensaje, o realizar otras acciones según tu necesidad
        echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
        // var_dump($res);
    }

    public function cierrePuntoVenta(Request $request)
    {
        $user = Auth::user();
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;
        $codigoPuntoVenta = $request->codigoPuntoVenta;
        $codigoSucursal = $request->codigoSucursal;
        $nit = $request->nit;

        require 'SiatController.php';
        $siat = new SiatController();
        $res = $siat->cierrePuntoVenta($codigoPuntoVenta, $nit, $codSucursal);
        // Verificar el valor de transacción y asignar el mensaje correspondiente
        if ($res->RespuestaCierrePuntoVenta->transaccion === true) {
            $mensaje = $res->RespuestaCierrePuntoVenta->codigoPuntoVenta;
        } else {
            $mensaje = $res->RespuestaCierrePuntoVenta->mensajesList->descripcion;
        }

        echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
        // var_dump($res);
    }



    private function crearVentaResivo($request)
    {

        if ((int)$request->idtipo_pago === 7 && empty($request->idbanco)) {
            throw new \Exception("Debe seleccionar un banco antes de registrar la venta.");
        }

        $ventaResivo = new Venta();
        $ventaResivo->fill($request->only([
            'idcliente',
            'idtipo_pago',
            'idtipo_venta',
            'tipo_comprobante',
            'serie_comprobante',
            'num_comprobante',
            'descuento_total',
            'impuesto',
            'total',
            'idbanco'
        ]));

        // Usuario logueado
        $usuario = \Auth::user();

        // Asignar campos adicionales
        $ventaResivo->idusuario = $usuario->id;
        $ventaResivo->idsucursal = $usuario->idsucursal; // ✅ nuevo: guardar la sucursal del usuario
        $ventaResivo->fecha_hora = now()->setTimezone('America/La_Paz');
        $ventaResivo->idalmacen = $request->idAlmacen;
        $ventaResivo->estado = $request->idtipo_venta == 2 ? 2 : 1;

        // Obtener la última caja abierta de la sucursal del usuario logueado
        $ultimaCajaAbierta = Caja::where('idsucursal', $usuario->idsucursal)
            ->where('estado', '1')
            ->latest()
            ->first();

        if (!$ultimaCajaAbierta) {
            throw new \Exception('No hay una caja abierta para la sucursal del usuario.');
        }

        $ventaResivo->idcaja = $ultimaCajaAbierta->id;
        if ($request->idtipo_venta == 1 && $request->idtipo_pago == 7) {
            // Pago contado por banco
            $ventaResivo->idbanco = $request->idbanco;  // Debe venir desde el frontend
        } else {
            // Cualquier otro caso NO lleva banco
            $ventaResivo->idbanco = null;
        }
        // Guardar la venta
        $ventaResivo->save();

        // 🔹 Si se usó saldo a favor del cliente, vaciarlo
        $saldoFavorUsado = $request->input('saldo_favor_usado', 0);
        if ($saldoFavorUsado > 0 && $request->idcliente) {
            $persona = \App\Persona::find($request->idcliente);
            if ($persona) {
                $persona->saldo_favor = 0;
                $persona->save();
            }
        }
        /*
        // Si es crédito, registrar crédito y cuotas
        if ($request->idtipo_venta == 2) {
            $creditoventa = $this->crearCreditoVenta($ventaResivo, $request);
            $this->registrarCuotasCredito($creditoventa, $request->cuotaspago);
        }*/
        
        return $ventaResivo;
    }



    private function crearVentaResivo2($request)
    {
        $ventaResivo = new Venta();
        $ventaResivo->fill($request->only([
            'idcliente',
            'idtipo_pago',
            'idtipo_venta',
            'tipo_comprobante',
            'serie_comprobante',
            'num_comprobante',
            'impuesto',
            'descuento_total',
            'total'
        ]));

        // Usuario logueado
        $usuario = \Auth::user();

        // Asignar manualmente campos adicionales
        $ventaResivo->idusuario = $usuario->id;
        $ventaResivo->idsucursal = $usuario->idsucursal; // ✅ Nuevo: guardar la sucursal del usuario logueado
        $ventaResivo->fecha_hora = now()->setTimezone('America/La_Paz');
        $ventaResivo->estado = 'Pendiente';

        // Asignar idalmacen desde el request (llega como idAlmacen)
        $ventaResivo->idalmacen = $request->idAlmacen;

        // Obtener la última caja abierta de la sucursal del usuario
        $ultimaCajaAbierta = Caja::where('idsucursal', $usuario->idsucursal)
            ->where('estado', '1')
            ->latest()
            ->first();

        if (!$ultimaCajaAbierta) {
            throw new \Exception('No hay una caja abierta para la sucursal del usuario.');
        }

        $ventaResivo->idcaja = $ultimaCajaAbierta->id;

        // Guardar la venta
        $ventaResivo->save();

        // Registrar crédito si es venta a crédito
        if ($request->idtipo_venta == 2) {
            $creditoventa = $this->crearCreditoVenta($ventaResivo, $request);
            $this->registrarCuotasCredito($creditoventa, $request->cuotaspago);
        }

        return $ventaResivo;
    }


    public function imprimirFacturaRollo($id)
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        //$puntoVenta = $user->idpuntoventa;
        $puntoVenta = $codigoPuntoVenta;

        $facturas = Factura::join('ventas', 'facturas.idventa', '=', 'ventas.id')
            ->join('personas', 'facturas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')->select(
                'facturas.*',
                'personas.nombre as razonSocial',
                'personas.num_documento as documentoid',
                'sucursales.nombre as nombreSucursal'
            )
            ->where('facturas.id', '=', $id)
            ->orderBy('facturas.id', 'desc')->paginate(3);

        Log::info('Resultado', [
            //'facturas' => $facturas,
            'idFactura' => $id,
        ]);

        //dd($facturas);

        $xml = $facturas[0]->productos;
        $archivoXML = new SimpleXMLElement($xml);
        $nitEmisor = $archivoXML->cabecera[0]->nitEmisor;
        $numeroFactura = str_pad($archivoXML->cabecera[0]->numeroFactura, 5, "0", STR_PAD_LEFT);
        $cuf = $archivoXML->cabecera[0]->cuf;
        $direccion = $archivoXML->cabecera[0]->direccion;
        $telefono = $archivoXML->cabecera[0]->telefono;
        $municipio = $archivoXML->cabecera[0]->municipio;
        $fechaEmision = $archivoXML->cabecera[0]->fechaEmision;
        $fechaFormateada = date("d/m/Y h:i A", strtotime($fechaEmision));
        $documentoid = $archivoXML->cabecera[0]->numeroDocumento;
        $razonSocial = $archivoXML->cabecera[0]->nombreRazonSocial;
        $codigoCliente = $archivoXML->cabecera[0]->codigoCliente;
        $montoTotal1 = $archivoXML->cabecera[0]->montoTotal;
        $montoGiftCard = $archivoXML->cabecera[0]->montoGiftCard;
        $descuentoAdicional = $archivoXML->cabecera[0]->descuentoAdicional;
        $leyenda = $archivoXML->cabecera[0]->leyenda;
        $complementoid = $archivoXML->cabecera[0]->complemento;

        $montoTotal = ($montoTotal1 - $montoGiftCard);
        $totalpagar = number_format(floatval($montoTotal), 2);
        $totalpagar = str_replace(',', '', $totalpagar);
        $totalpagar = str_replace('.', ',', $totalpagar);
        $cifrasEnLetras = new CifrasEnLetrasController();
        $letra = ($cifrasEnLetras->convertirBolivianosEnLetras($totalpagar));


        $url = 'https://siat.impuestos.gob.bo/consulta/QR?nit=' . $nitEmisor . '&cuf=' . $cuf . '&numero=' . $numeroFactura . '&t=2';
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => false,
            'scale' => 10,
        ]);
        $qrCode = new QRCode($options);
        $qrCode->render($url, public_path('qr/qrcode.png'));

        //$pdf = new FPDF('P', 'mm', array(80, 0));
        $pdf = new FPDF('P', 'mm', array(80, 250));
        //$pdf = new FPDF();
        $nombreSucursal = $facturas[0]->nombreSucursal ?? 'Casa Matriz';

        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetMargins(10, 10);
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->Cell(0, 3, 'FACTURA', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->Cell(0, 3, utf8_decode('CON DERECHO A CRÉDITO FISCAL'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(0, 3, utf8_decode('MARIBEL QUISPE CHOQUE'), 0, 1, 'C');
        $pdf->Cell(0, 4, utf8_decode($nombreSucursal), 0, 1, 'C');
        $pdf->Cell(0, 4, utf8_decode('No. Punto de Venta ' . $puntoVenta), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 7);
        $pdf->MultiCell(0, 4, utf8_decode($direccion), 0, 'C');

        $pdf->SetFont('Arial', '', 8);
        //$pdf->Cell(0, 4, utf8_decode('Tel. ' . $telefono), 0, 1, 'C');
        $pdf->Cell(0, 4, utf8_decode($municipio), 0, 1, 'C');

        $y = $pdf->GetY();
        $pdf->SetY($y + 2);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Cell(0, 4, '', 'T', 1, 'C');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 4, 'NIT', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        //$pdf->Cell(0, 3, utf8_decode($documentoid."-".$complementoid), 0, 1, 'C');
        $pdf->Cell(0, 4, utf8_decode($nitEmisor), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 4, utf8_decode('FACTURA N°'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, utf8_decode($numeroFactura), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 4, utf8_decode('CÓD. AUTORIZACIÓN'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->MultiCell(0, 4, utf8_decode($cuf), 0, 'C');

        $y = $pdf->GetY();
        $pdf->SetY($y + 2);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Cell(0, 4, '', 'T', 1, 'C');

        $spacing = 8;

        // Definir margen izquierdo
        $marginLeft = 10;
        $spacing = 33; // Espaciado entre el título y el dato

        // NOMBRE/RAZON SOCIAL
        $pdf->SetX($marginLeft);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell($spacing, 4, utf8_decode('NOMBRE/RAZON SOCIAL:'), 0, 0, 'L'); // Título
        $pdf->SetFont('Arial', '', 8);
        $pdf->MultiCell(0, 4, utf8_decode($razonSocial), 0, 'L'); // Dato (permite saltos si es largo)

        $spacing = 17; // Espaciado entre el título y el dato

        // NIT/CI/CEX
        $pdf->SetX($marginLeft);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($spacing, 4, utf8_decode('NIT/CI/CEX:'), 0, 0, 'L'); // Título
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 4, utf8_decode($documentoid), 0, 1, 'L'); // Dato en la misma línea

        $spacing = 22; // Espaciado entre el título y el dato

        // COD. CLIENTE
        $pdf->SetX($marginLeft);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($spacing, 4, utf8_decode('COD. CLIENTE:'), 0, 0, 'L'); // Título
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 4, utf8_decode($codigoCliente), 0, 1, 'L'); // Dato en la misma línea

        $spacing = 29; // Espaciado entre el título y el dato

        // FECHA DE EMISIÓN
        $pdf->SetX($marginLeft);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($spacing, 4, utf8_decode('FECHA DE EMISIÓN:'), 0, 0, 'L'); // Título
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 4, utf8_decode($fechaFormateada), 0, 1, 'L'); // Dato en la misma línea

        $y = $pdf->GetY();
        $pdf->SetY($y + 2);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Cell(0, 4, '', 'T', 1, 'C');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 5, 'DETALLE', 0, 1, 'C');

        $detalle = $archivoXML->detalle;
        $sumaSubTotales = 0.0;
        foreach ($detalle as $p) {
            $producto = utf8_decode($p->codigoProducto . " - " . $p->descripcion);

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->MultiCell(0, 4, $producto, 0, 'L');

            $medida = $p->unidadMedida;
            $nombreMedida = Medida::where('codigoClasificador', $medida)->value('descripcion_medida');

            $pdf->SetFont('Arial', '', 9);
            //$pdf->Cell(0, 4, "Unidad de Medida: " . $nombreMedida, 0, 1, 'L');
            $pdf->Cell(0, 4, number_format(floatval($p->cantidad), 2) . " X " . number_format(floatval($p->precioUnitario), 2) . " - " . number_format(floatval($p->montoDescuento), 2), 0, 0, 'L');
            $pdf->Cell(0, 4, number_format(floatval($p->subTotal), 2), 0, 1, 'R');

            $sumaSubTotales += floatval($p->subTotal);
        }

        $y = $pdf->GetY();
        $pdf->SetY($y + 2);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Cell(0, 4, '', 'T', 1, 'C');

        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, 'SUBTOTAL Bs', 0, 0, 'C');
        $pdf->Cell(0, 4, number_format(floatval($sumaSubTotales), 2), 0, 1, 'R');
        $pdf->Cell(0, 4, 'DESCUENTO Bs', 0, 0, 'C');
        $pdf->Cell(0, 4, number_format(floatval($descuentoAdicional), 2), 0, 1, 'R');
        $pdf->Cell(0, 4, 'TOTAL Bs', 0, 0, 'C');
        $pdf->Cell(0, 4, number_format(floatval($montoTotal), 2), 0, 1, 'R');
        $pdf->Cell(0, 4, 'MONTO GIFT CARD Bs', 0, 0, 'C');
        $pdf->Cell(0, 4, number_format(floatval($montoGiftCard), 2), 0, 1, 'R');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 4, 'MONTO A PAGAR Bs', 0, 0, 'C');
        $pdf->Cell(0, 4, number_format(floatval($montoTotal), 2), 0, 1, 'R');
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->Cell(0, 4, utf8_decode('IMPORTE BASE CRÉDITO FISCAL Bs'), 0, 0, 'C');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 4, number_format(floatval($montoTotal), 2), 0, 1, 'R');
        $pdf->Ln(6);
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell(0, 4, 'Son: ' . $letra, 0, 'L');

        $y = $pdf->GetY();
        $pdf->SetY($y + 2);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Cell(0, 4, '', 'T', 1, 'C');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell(0, 4, utf8_decode('ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS,'), 0, 1, 'C');
        $pdf->Cell(0, 4, utf8_decode('EL USO ILÍCITO SERÁ SANCIONADO PENALMENTE DE'), 0, 1, 'C');
        $pdf->Cell(0, 4, utf8_decode('ACUERDO A LA LEY'), 0, 1, 'C');
        $pdf->Ln(3);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->MultiCell(0, 4, utf8_decode($leyenda), 0, 'C');
        $pdf->Ln(3);
        $pdf->Cell(0, 4, utf8_decode('Este documento es la Representación Gráfica de un'), 0, 1, 'C');
        $pdf->Cell(0, 4, utf8_decode('Documento Fiscal Digital emitido en una modalidad de'), 0, 1, 'C');
        $pdf->Cell(0, 4, utf8_decode('facturación en línea'), 0, 1, 'C');
        $pdf->Ln(3);
        $textY = $pdf->GetY(); // Posición actual después del contenido previo

        $imageWidth = 25; // Ancho del QR
        $imageHeight = 25; // Altura del QR
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();

        // Calcula la posición centrada horizontalmente
        $imageX = ($pageWidth - $imageWidth) / 2;

        // Verifica si hay suficiente espacio en la página para el QR
        if (($textY + $imageHeight + 10) > $pageHeight) {
            $pdf->AddPage(); // Agrega una nueva página si no hay espacio
            $textY = 10; // Reinicia la posición en la nueva página
        }

        // Agrega el QR en la posición ajustada
        $pdf->Image(public_path('qr/qrcode.png'), $imageX, $textY + 5, $imageWidth, $imageHeight, 'PNG');

        $pdf->Output(public_path('docs/facturaRollo.pdf'), 'F');

        $pdfPath = public_path('docs/facturaRollo.pdf');
        $xmlPath = public_path("docs/facturaxml.xml");

        //\Mail::to($correo)->send(new \App\Mail\FacturaElectrónica($xmlPath, $pdfPath));

        return response()->json(['url' => url('docs/facturaRollo.pdf')]);
    }

    public function imprimirResivoRollo($id)
    {
        try {
            $venta = Venta::with('detalles.producto')->find($id);
            if (!$venta) {
                return response()->json(['error' => 'NO SE ENCONTRÓ LA VENTA'], 404);
            }

            $persona = Persona::find($venta->idcliente);
            if (!$persona) {
                return response()->json(['error' => 'NO SE ENCONTRÓ EL CLIENTE'], 404);
            }

            $empresa = Empresa::first();
            if (!$empresa) {
                return response()->json(['error' => 'NO SE ENCONTRÓ LA EMPRESA'], 404);
            }

            if ($venta->detalles->isNotEmpty()) {
                // Configuración para recibo de rollo
                $pdf = new FPDF('P', 'mm', array(80, 297)); // Ancho de 80mm, alto variable
                $pdf->SetAutoPageBreak(true, 10);
                $pdf->SetMargins(5, 10, 5);
                $pdf->AddPage();

               // LOGO FIJO DESDE PUBLIC/IMG/logoPrincipal.png
                $logoPath = public_path('img/logoPrincipal.png');

                if (file_exists($logoPath)) {
                    $logoWidth = 20; // ancho en mm
                    $xPosition = (80 - $logoWidth) / 2; // centrar en papel de 80mm
                    $pdf->Image($logoPath, $xPosition, 2, $logoWidth);
                    $pdf->Ln(10);
                }

                $pdf->SetFont('Courier', 'B', 10);
                $pdf->Cell(0, 5, utf8_decode(strtoupper('NOTA DE VENTA')), 0, 1, 'C');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Cell(0, 4, utf8_decode(strtoupper('N. ' . $venta->num_comprobante)), 0, 1, 'C');
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell(0, 4, utf8_decode(strtoupper($empresa->nombre)), 0, 1, 'C');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Cell(0, 4, utf8_decode(strtoupper('TELÉFONO: ' . $empresa->telefono)), 0, 1, 'C');
                $pdf->Ln(2);
                // FECHA Y HORA EN UNA SOLA LÍNEA CON ESTILOS DIFERENTES
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'FECHA: ');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Write(5, date('d/m/Y', strtotime($venta->created_at)));

                $pdf->Write(5, '   |   ');

                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'HORA: ');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Write(5, date('H:i:s', strtotime($venta->created_at)));

                $pdf->Ln(5); // salto de línea
                // CLIENTE en negrita + dato normal
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'CLIENTE: ');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Write(5, utf8_decode(strtoupper($persona->nombre)));
                $pdf->Ln(5);

                // DOC en negrita + dato normal
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'DOC: ');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Write(5, utf8_decode(strtoupper($persona->num_documento)));
                $pdf->Ln(5);

                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'TELEFONO: ');
                $pdf->SetFont('Arial', '', 7);
                $telefono = !empty($persona->telefono) ? $persona->telefono : '0';
                $pdf->Write(5, utf8_decode(strtoupper($telefono)));
                $pdf->Ln(5);

                $pdf->Cell(0, 2, '', 'T', 1);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(12, 5, 'Cant', 0, 0, 'L');
                $pdf->Cell(43, 5, 'Producto', 0, 0, 'L');
                $pdf->Cell(15, 5, 'Subtotal', 0, 1, 'R');
                $pdf->SetFont('Arial', '', 8);

               $total = 0;

                foreach ($venta->detalles as $detalle) {

                    if ($detalle->modo_venta === 'caja') {
                        $subtotal = $detalle->cantidad * $detalle->producto->unidad_envase * $detalle->precio;
                    } else {
                        $subtotal = $detalle->cantidad * $detalle->precio;
                    }

                    $total += $subtotal;

                    // ---------------------------
                    // LÍNEA 1: Cjs | Producto + Código
                    // ---------------------------

                    // Cantidad
                    $pdf->SetFont('Arial', 'B', 8);
                    // Cantidad con abreviación (CJS o UND)
                    $abreviacion = $detalle->modo_venta === 'caja' ? 'CJS' : 'UND';
                    $cantidadTexto = $detalle->cantidad . ' ' . $abreviacion;

                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->Cell(12, 5, $cantidadTexto, 0, 0, 'L');

                    // Preparar nombre y código
                    $nombre = strtoupper(substr($detalle->producto->nombre, 0, 30));
                    $codigo = strtoupper($detalle->producto->codigo);

                    // Guardar posición de inicio
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();

                    // Imprimir SOLO NOMBRE en la celda de 43
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell(43, 5, utf8_decode($nombre), 0, 0, 'L');

                    // Calcular posición exacta donde termina el nombre
                    $pdf->SetXY($x + $pdf->GetStringWidth($nombre . ' '), $y);

                    // Imprimir CÓDIGO en negrita justo al lado
                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->Write(5, utf8_decode($codigo));

                    // Saltar de línea
                    $pdf->Ln(5);

                    // ---------------------------
                    // LÍNEA 2: Cant x Precio | Subtotal
                    // ---------------------------
                    $pdf->SetFont('Arial', '', 8);

                    if ($detalle->modo_venta === 'caja') {
                        // ejemplo: "12 x 5.00"
                        $linea2 = $detalle->producto->unidad_envase . ' x ' . number_format($detalle->precio, 2);
                    } else {
                        // ejemplo: "1 x 5.00"
                        $linea2 =  $detalle->cantidad . ' x ' . number_format($detalle->precio, 2);
                    }
                    $pdf->Cell(50, 5, $linea2, 0, 0, 'L'); // izquierda
                    $pdf->Cell(20, 5, number_format($subtotal, 2), 0, 1, 'R'); // derecha
                }

                $pdf->Cell(0, 2, '', 'T', 1);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(50, 6, utf8_decode(strtoupper('TOTAL')), 0, 0);
                $pdf->Cell(20, 6, utf8_decode(number_format($total, 2)), 0, 1, 'R');

                $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
                $totalTexto = strtoupper($formatter->format($total)) . ' BOLIVIANOS';
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->MultiCell(0, 5, 'SON: ' . $totalTexto, 0, 'L');
                $pdf->Ln(3);
                $pdf->SetFont('Arial', 'I', 8);
                $pdf->Cell(0, 5, utf8_decode(strtoupper('¡GRACIAS POR SU COMPRA!')), 0, 1, 'C');

                // Enviar el archivo PDF al navegador
                return response($pdf->Output('S'), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="recibo_rollo.pdf"');
            } else {
                return response()->json(['error' => 'NO HAY DETALLES PARA ESTA VENTA'], 404);
            }
        } catch (\Exception $e) {
    \Log::error('Error al imprimir el recibo en rollo: ' . $e->getMessage());
    // Devuelve el error real en la respuesta
    return response()->json([
        'error' => 'OCURRIÓ UN ERROR AL IMPRIMIR EL RECIBO EN ROLLO',
        'detalle' => $e->getMessage(),
        'linea' => $e->getLine(),
        'archivo' => $e->getFile()
    ], 500);
}

    }

    public function imprimirResivoCarta($id)
    {
        try {
            $venta = Venta::with('detalles.producto')->find($id);
            if (!$venta) {
                return response()->json(['error' => 'NO SE ENCONTRÓ LA VENTA'], 500);
            }

            $persona = Persona::find($venta->idcliente);
            if (!$persona) {
                return response()->json(['error' => 'NO SE ENCONTRÓ EL CLIENTE'], 500);
            }

            $empresa = Empresa::first();
            if (!$empresa) {
                return response()->json(['error' => 'NO SE ENCONTRÓ LA EMPRESA'], 404);
            }

            if ($venta->detalles->isNotEmpty()) {
                $pdf = new FPDF('P', 'mm', [139.7, 215.9]); // MEDIA CARTA
                $pdf->SetMargins(8, 8, 8);
                $pdf->SetAutoPageBreak(true, 10);
                $pdf->AddPage();


// ================= MEDIDAS =================
$pageWidth = $pdf->GetPageWidth();
$margin = 10;
$usableWidth = $pageWidth - ($margin * 2);

// ================= LOGO (IZQUIERDA) =================
$logoPath = public_path('img/logoPrincipal.png');
$logoWidth = 20;

if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $margin, 10, $logoWidth);
}

// ================= TITULO + INFO (CENTRO) =================
$pdf->SetXY($margin, 12);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($usableWidth, 6, 'NOTA DE VENTA', 0, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$pdf->Cell($usableWidth, 4, utf8_decode('SUCURSAL: Casa Matriz'), 0, 1, 'C');
$pdf->Cell($usableWidth, 4, utf8_decode('TELÉFONO: ' . $empresa->telefono), 0, 1, 'C');
$pdf->Cell($usableWidth, 4, utf8_decode('DIRECION: ' . $empresa->direccion), 0, 1, 'C');

$pdf->Cell($usableWidth, 4, utf8_decode('N° COMPROBANTE: ' . $venta->num_comprobante), 0, 1, 'C');

// ================= FECHA / HORA (DERECHA) =================
$fecha = date('d/m/Y', strtotime($venta->created_at));
$hora  = date('H:i:s', strtotime($venta->created_at));

$rightX = $pageWidth - $margin - 32;

$pdf->SetXY($rightX, 12);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(18, 4, 'FECHA:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 4, $fecha, 0, 1);

$pdf->SetXY($rightX, 18);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(18, 4, 'HORA:', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 4, $hora, 0, 1);

// ================= ESPACIO DESPUÉS DEL ENCABEZADO =================
$pdf->Ln(15);

                // CLIENTE
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(17, 6, 'CLIENTE:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(60, 6, strtoupper($persona->nombre), 0, 0, 'L');

                // DOCUMENTO
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(22, 6, 'DOCUMENTO:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(35, 6, strtoupper($persona->num_documento), 0, 0, 'L');

                // TELEFONO
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(22, 6, 'TELEFONO:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 8);
                $telefono = (!empty($persona->telefono)) ? $persona->telefono : '0';
                $pdf->Cell(0, 6, strtoupper($telefono), 0, 1, 'L');

                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetFillColor(230, 230, 230);

                $pdf->Cell(12, 7, 'Cant', 1, 0, 'C', true);
                $pdf->Cell(55, 7, 'Producto', 1, 0, 'C', true);
                $pdf->Cell(15, 7, 'Caja', 1, 0, 'C', true);
                $pdf->Cell(18, 7, 'P.Unit', 1, 0, 'C', true);
                $pdf->Cell(20, 7, 'Subt.', 1, 1, 'C', true);


                $pdf->SetFont('Arial', '', 7);
                $total = 0;
                $pdf->SetFont('Arial', '', 8);

                foreach ($venta->detalles as $detalle) {

                    $subtotal = ($detalle->modo_venta === 'caja')
                        ? $detalle->cantidad * $detalle->producto->unidad_envase * $detalle->precio
                        : $detalle->cantidad * $detalle->precio;
                    $total += $subtotal;

                    $abreviacion = $detalle->modo_venta === 'caja' ? 'CJS' : 'UND';
                    $cantidadTexto = strtoupper($detalle->cantidad . ' ' . $abreviacion);

                    $productoTexto = strtoupper(
                    $detalle->producto->codigo . ' - ' . $detalle->producto->nombre
                );

                $productoTexto = $this->cortarTexto(
                    $pdf,
                    utf8_decode($productoTexto),
                    52
                );

                    $cantXCaja = ($detalle->modo_venta === 'caja')
                        ? $detalle->producto->unidad_envase
                        : '-';

                    $pdf->Cell(12, 6, $cantidadTexto, 1, 0, 'C');
                    $pdf->Cell(55, 6, utf8_decode($productoTexto), 1, 0);
                    $pdf->Cell(15, 6, $cantXCaja, 1, 0, 'C');
                    $pdf->Cell(18, 6, number_format($detalle->precio, 2), 1, 0, 'R');
                    $pdf->Cell(20, 6, number_format($subtotal, 2), 1, 1, 'R');
                }

                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(90, 7, utf8_decode('TOTAL'), 1, 0, 'R');
                $pdf->Cell(30, 7, utf8_decode(strtoupper(number_format($total, 2))), 1, 1, 'R');

                $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
                $totalTexto = strtoupper($formatter->format($total)) . ' BOLIVIANOS';
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(0, 5, 'SON: ' . $totalTexto, 0, 1);

                $pdf->Ln(5);
                $pdf->SetFont('Arial', 'B', 8);
        
                // Firma
                /*$pdf->SetFont('Arial', '', 10);
                $anchoFirma = $pdf->GetPageWidth() / 2 - 20;
                $pdf->Cell($anchoFirma, 6, '_________________________', 0, 0, 'C');
                $pdf->Cell($anchoFirma, 6, '_________________________', 0, 1, 'C');
                $pdf->Cell($anchoFirma, 6, 'FIRMA DEL CLIENTE', 0, 0, 'C');
                $pdf->Cell($anchoFirma, 6, 'FIRMA AUTORIZADA', 0, 1, 'C');
                $pdf->Ln(10);*/

                // Nota de agradecimiento
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->Cell(0, 7, utf8_decode('¡GRACIAS POR SU COMPRA!'), 0, 1, 'C');

                $nombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $persona->nombre);
                $pdfPath = public_path('docs/recibo_carta_' . $nombreLimpio . '_' . $id . '.pdf');
                $pdf->Output($pdfPath, 'F');

                return response()->download($pdfPath);
            } else {
                return response()->json(['error' => 'NO HAY DETALLES PARA ESTA VENTA'], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Error al imprimir el recibo en carta: ' . $e->getMessage());
            return response()->json(['error' => 'OCURRIÓ UN ERROR AL IMPRIMIR EL RECIBO EN CARTA'], 500);
        }
    }

    public function imprimirRemisionRollo($id)
    {
        try {
            $venta = Venta::with('detalles.producto')->find($id);
            if (!$venta) {
                return response()->json(['error' => 'NO SE ENCONTRÓ LA VENTA'], 404);
            }

            $persona = Persona::find($venta->idcliente);
            if (!$persona) {
                return response()->json(['error' => 'NO SE ENCONTRÓ EL CLIENTE'], 404);
            }

            $empresa = Empresa::first();
            if (!$empresa) {
                return response()->json(['error' => 'NO SE ENCONTRÓ LA EMPRESA'], 404);
            }

            if ($venta->detalles->isNotEmpty()) {
                // Configuración para recibo de rollo
                $pdf = new FPDF('P', 'mm', array(80, 297)); // Ancho de 80mm, alto variable
                $pdf->SetAutoPageBreak(true, 10);
                $pdf->SetMargins(5, 10, 5);
                $pdf->AddPage();

               // LOGO FIJO DESDE PUBLIC/IMG/logoPrincipal.png
                $logoPath = public_path('img/logoPrincipal.png');

                if (file_exists($logoPath)) {
                    $logoWidth = 20; // ancho en mm
                    $xPosition = (80 - $logoWidth) / 2; // centrar en papel de 80mm
                    $pdf->Image($logoPath, $xPosition, 2, $logoWidth);
                    $pdf->Ln(10);
                }

                $pdf->SetFont('Courier', 'B', 10);
                $pdf->Cell(0, 5, utf8_decode(strtoupper('NOTA DE REMISIÓN')), 0, 1, 'C');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Cell(0, 4, utf8_decode(strtoupper('N. ' . $venta->num_comprobante)), 0, 1, 'C');
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell(0, 4, utf8_decode(strtoupper($empresa->nombre)), 0, 1, 'C');
                $pdf->Ln(2);
                // FECHA Y HORA EN UNA SOLA LÍNEA CON ESTILOS DIFERENTES
                $pdf->Write(5, 'FECHA: ');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Write(5, date('d/m/Y', strtotime($venta->created_at)));

                $pdf->Write(5, '   |   ');

                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'HORA: ');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Write(5, date('H:i:s', strtotime($venta->created_at)));

                $pdf->Ln(5); // salto de línea
                // CLIENTE en negrita + dato normal
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'CLIENTE: ');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Write(5, utf8_decode(strtoupper($persona->nombre)));
                $pdf->Ln(5);

                // DOC en negrita + dato normal
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'DOC: ');
                $pdf->SetFont('Arial', '', 7);
                $pdf->Write(5, utf8_decode(strtoupper($persona->num_documento)));
                $pdf->Ln(5);

                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Write(5, 'TELEFONO: ');
                $pdf->SetFont('Arial', '', 7);
                $telefono = !empty($persona->telefono) ? $persona->telefono : '0';
                $pdf->Write(5, utf8_decode(strtoupper($telefono)));
                $pdf->Ln(5);
                $pdf->Cell(0, 2, '', 'T', 1);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(12, 5, 'Cant', 0, 0, 'L');
                $pdf->Cell(43, 5, 'Producto', 0, 1, 'L');
                $pdf->SetFont('Arial', '', 8);

                $total = 0;

                foreach ($venta->detalles as $detalle) {

                    // ---------------------------
                    // LÍNEA 1: Cjs | Producto + Código
                    // ---------------------------

                    // Cantidad
                    $pdf->SetFont('Arial', 'B', 8);
                    // Cantidad con abreviación
                    $abreviacion = $detalle->modo_venta === 'caja' ? 'CJS' : 'UND';
                    $cantidadTexto = $detalle->cantidad . ' ' . $abreviacion;

                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->Cell(12, 5, utf8_decode($cantidadTexto), 0, 0, 'L'); 
                    // Preparar nombre y código
                    $nombre = strtoupper(substr($detalle->producto->nombre, 0, 30));
                    $codigo = strtoupper($detalle->producto->codigo);

                    // Guardar posición de inicio
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();

                    // Imprimir nombre en celda de 43
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell(43, 5, utf8_decode($nombre), 0, 0, 'L');

                    // Posicionar para escribir el código al lado
                    $pdf->SetXY($x + $pdf->GetStringWidth($nombre . ' '), $y);

                    // Código en negrita
                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->Write(5, utf8_decode($codigo));

                    // Saltar línea
                    $pdf->Ln(5);
                }

                $pdf->Cell(0, 2, '', 'T', 1);
                $pdf->SetFont('Arial', 'I', 8);

                // Enviar el archivo PDF al navegador
                return response($pdf->Output('S'), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="recibo_rollo.pdf"');
            } else {
                return response()->json(['error' => 'NO HAY DETALLES PARA ESTA VENTA'], 404);
            }
        } catch (\Exception $e) {
            \Log::error('Error al imprimir el recibo en rollo: ' . $e->getMessage());
            return response()->json(['error' => 'OCURRIÓ UN ERROR AL IMPRIMIR EL RECIBO EN ROLLO'], 500);
        }
    }

    public function imprimirRemisionCarta($id)
    {
        try {
            $venta = Venta::with('detalles.producto')->find($id);
            if (!$venta) {
                return response()->json(['error' => 'NO SE ENCONTRÓ LA VENTA'], 500);
            }

            $persona = Persona::find($venta->idcliente);
            if (!$persona) {
                return response()->json(['error' => 'NO SE ENCONTRÓ EL CLIENTE'], 500);
            }

            $empresa = Empresa::first();
            if (!$empresa) {
                return response()->json(['error' => 'NO SE ENCONTRÓ LA EMPRESA'], 404);
            }

            if ($venta->detalles->isNotEmpty()) {
                $pdf = new FPDF('P', 'mm', [139.7, 215.9]); // MEDIA CARTA
                $pdf->SetMargins(8, 8, 8);
                $pdf->SetAutoPageBreak(true, 10);
                $pdf->AddPage();


                // ================= MEDIDAS =================
                $pageWidth = $pdf->GetPageWidth();
                $margin = 10;
                $usableWidth = $pageWidth - ($margin * 2);

                // ================= LOGO (IZQUIERDA) =================
                $logoPath = public_path('img/logoPrincipal.png');
                $logoWidth = 20;

                if (file_exists($logoPath)) {
                    $pdf->Image($logoPath, $margin, 10, $logoWidth);
                }

                // ================= TITULO + INFO (CENTRO) =================
                $pdf->SetXY($margin, 12);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell($usableWidth, 6, 'NOTA DE REMISION', 0, 1, 'C');

                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell($usableWidth, 4, utf8_decode('SUCURSAL: Casa Matriz'), 0, 1, 'C');
                $pdf->Cell($usableWidth, 4, utf8_decode('TELÉFONO: ' . $empresa->telefono), 0, 1, 'C');
                $pdf->Cell($usableWidth, 4, utf8_decode('DIRECION: ' . $empresa->direccion), 0, 1, 'C');

                $pdf->Cell($usableWidth, 4, utf8_decode('N° COMPROBANTE: ' . $venta->num_comprobante), 0, 1, 'C');

                // ================= FECHA / HORA (DERECHA) =================
                $fecha = date('d/m/Y', strtotime($venta->created_at));
                $hora  = date('H:i:s', strtotime($venta->created_at));

                $rightX = $pageWidth - $margin - 30;

                $pdf->SetXY($rightX, 12);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(18, 4, 'FECHA:', 0, 0);
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(30, 4, $fecha, 0, 1);

                $pdf->SetXY($rightX, 18);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(18, 4, 'HORA:', 0, 0);
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(30, 4, $hora, 0, 1);

                // ================= ESPACIO DESPUÉS DEL ENCABEZADO =================
                $pdf->Ln(15);
                // CLIENTE
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(17, 6, 'CLIENTE:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(60, 6, strtoupper($persona->nombre), 0, 0, 'L');

                // DOCUMENTO
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(22, 6, 'DOCUMENTO:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(35, 6, strtoupper($persona->num_documento), 0, 0, 'L');

                // TELEFONO
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(22, 6, 'TELEFONO:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 8);
                $telefono = (!empty($persona->telefono)) ? $persona->telefono : '0';
                $pdf->Cell(0, 6, strtoupper($telefono), 0, 1, 'L');

                // Tabla de productos
               // Tabla de productos
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetFillColor(230, 230, 230);

                $pdf->Cell(15, 7, 'Cant', 1, 0, 'C', true);
                $pdf->Cell(85, 7, 'Producto', 1, 0, 'C', true);
                $pdf->Cell(15, 7, 'Cnt X Caja', 1, 1, 'C', true);


                $pdf->SetFont('Arial', '', 9);
                $total = 0;
                $totalCajas = 0;
                $totalUnidades = 0;

                $pdf->SetFont('Arial', '', 7);

                foreach ($venta->detalles as $detalle) {

                    $abreviacion = $detalle->modo_venta === 'caja' ? 'CJS' : 'UND';
                    $cantidadTexto = strtoupper($detalle->cantidad . ' ' . $abreviacion);

                    $productoTexto = strtoupper(
                        $detalle->producto->codigo . ' - ' . $detalle->producto->nombre
                    );

                    if ($detalle->modo_venta === 'caja') {
                        $cantXCaja = $detalle->producto->unidad_envase;
                        $totalCajas += $detalle->cantidad;
                    } else {
                        $cantXCaja = '-';
                        $totalUnidades += $detalle->cantidad;
                    }
                    $pdf->Cell(15, 6, utf8_decode($cantidadTexto), 1, 0, 'C');
                    $nombre = strtoupper($detalle->producto->nombre);
                    $codigo = strtoupper($detalle->producto->codigo);
                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->Cell(20, 6, utf8_decode($codigo), 1, 0);  // Código en negrita
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell(65, 6, utf8_decode($nombre), 1, 0);  // Nombre
                    $pdf->Cell(15, 6, utf8_decode(strtoupper($cantXCaja)), 1, 1, 'C');
                    
                }


                // ------------------------
                // FILAS DE TOTALES
                // ------------------------
               $pdf->Ln(2);
                $pdf->SetFont('Arial', 'B', 8);

                // Mover el cursor a la derecha, alineado con la tabla
                $pdf->SetX(40); // mismo margen izquierdo de la tabla

                $pdf->Cell(82, 5, "TOTAL CJS: $totalCajas", 0, 1, 'R');
                $pdf->SetX(40);
                $pdf->Cell(82, 5, "TOTAL UNIDADES SUELTAS: $totalUnidades", 0, 1, 'R');


                $pdf->Ln(5);
                $pdf->SetFont('Arial', 'B', 8);

                $nombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $persona->nombre);
                $pdfPath = public_path('docs/recibo_carta_' . $nombreLimpio . '_' . $id . '.pdf');
                $pdf->Output($pdfPath, 'F');

                return response()->download($pdfPath);
            } else {
                return response()->json(['error' => 'NO HAY DETALLES PARA ESTA VENTA'], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Error al imprimir el recibo en carta: ' . $e->getMessage());
            return response()->json(['error' => 'OCURRIÓ UN ERROR AL IMPRIMIR EL RECIBO EN CARTA'], 500);
        }
    }

    public function selectRoles(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $roles = Rol::where('condicion', '=', '1')
            ->select('id', 'nombre')->orderBy('nombre', 'asc')->get();
        return ['roles' => $roles];
    }

    private function cortarTexto($pdf, $texto, $anchoMax)
{
    if ($pdf->GetStringWidth($texto) <= $anchoMax) {
        return $texto;
    }

    while ($pdf->GetStringWidth($texto . '...') > $anchoMax) {
        $texto = mb_substr($texto, 0, -1);
    }

    return $texto . '...';
}

    public function reporteVentasDiarias(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
        ]);

        $idUsuario = $request->input('idUsuario');

        $query = DetalleVenta::join('ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->select(
                'ventas.id',

                'ventas.num_comprobante',
                DB::raw('GROUP_CONCAT(DISTINCT articulos.nombre SEPARATOR ", ") as articulo'),
                DB::raw('SUM(detalle_ventas.cantidad) as cantidad'),
                DB::raw('SUM(detalle_ventas.precio * detalle_ventas.cantidad) as total'),
                DB::raw('MAX(detalle_ventas.precio) as precio')
            )
            ->whereDate('ventas.created_at', $request->input('fecha'))
            ->groupBy('ventas.id', 'ventas.num_comprobante');

        if ($request->has('idCategoria') && $request->input('idCategoria') !== 'all') {
            $query->where('articulos.idcategoria', $request->input('idCategoria'));
        }

        if ($idUsuario !== 'all') {
            $query->where('ventas.idusuario', $idUsuario);
        }

        $ventas = $query->get();

        if ($ventas->isEmpty()) {
            return response()->json(['mensaje' => 'Ninguna Venta Realizada en la Fecha Indicada']);
        }

        return response()->json(['ventas' => $ventas]);
    }
    public function selectUsuarios()
    {
        $usuarios = User::select('id', 'usuario')->get();
        return response()->json(['usuarios' => $usuarios]);
    }

    public function topVendedores(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $usuario = \Auth::user();

        $query = Venta::join('personas', 'ventas.idusuario', '=', 'personas.id')
            ->select(
                'ventas.idusuario',
                'personas.nombre as nombreUsuario',
                DB::raw('COUNT(*) as cantidadVentas'),
                DB::raw('SUM(ventas.total) as totalVentas')
            )
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);

        // ✅ Si el usuario no tiene rol 4, filtrar por su sucursal
        if ($usuario->idrol != 4) {
            $query->where('ventas.idsucursal', $usuario->idsucursal);
        }

        $topVendedores = $query
            ->groupBy('ventas.idusuario', 'personas.nombre')
            ->orderByDesc('cantidadVentas')
            ->limit(10)
            ->get();

        return response()->json(['topVendedores' => $topVendedores]);
    }

    public function topClientes(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $usuario = \Auth::user();

        $query = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->select(
                'ventas.idcliente',
                'personas.nombre as nombreCliente',
                DB::raw('COUNT(*) as cantidadCompras'),
                DB::raw('SUM(ventas.total) as totalGastado')
            )
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);

        // ✅ Si el usuario no es rol 4, filtrar por su sucursal
        if ($usuario->idrol != 4) {
            $query->where('ventas.idsucursal', $usuario->idsucursal);
        }

        $topClientes = $query
            ->groupBy('ventas.idcliente', 'personas.nombre')
            ->orderByDesc('cantidadCompras')
            ->limit(10)
            ->get();

        return response()->json(['topClientes' => $topClientes]);
    }


    public function topProductos(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $usuario = \Auth::user();

        $query = DetalleVenta::join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->join('ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->select(
                'detalle_ventas.idarticulo',
                'articulos.nombre as nombreArticulo',
                DB::raw('SUM(detalle_ventas.cantidad) as cantidadTotal'),
                DB::raw('COUNT(*) as vecesVendido')
            )
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);

        // ✅ Filtrar por sucursal si el usuario no es rol 4
        if ($usuario->idrol != 4) {
            $query->where('ventas.idsucursal', $usuario->idsucursal);
        }

        $topProductos = $query
            ->groupBy('detalle_ventas.idarticulo', 'articulos.nombre')
            ->orderByDesc('cantidadTotal')
            ->limit(10)
            ->get();

        return response()->json(['topProductos' => $topProductos]);
    }

    public function obtenerUltimoComprobante(Request $request)
    {
        $idsucursal = $request->idsucursal;

        $ultimoComprobanteRecibo = Venta::join('users', 'ventas.idusuario', '=', 'users.id')
            ->select('ventas.num_comprobante')
            ->where('users.idsucursal', $idsucursal)
            ->where('ventas.tipo_comprobante', 'RESIVO')
            ->orderBy('ventas.id', 'desc')
            ->limit(1)
            ->first();

        $lastComprobanteRecibo = $ultimoComprobanteRecibo ? $ultimoComprobanteRecibo->num_comprobante : 0;

        return response()->json(['last_comprobante' => $lastComprobanteRecibo]);
    }


    private function crearCreditoVenta($venta, $request)
    {
        $creditoventa = new CreditoVenta();
        $creditoventa->idventa = $venta->id;
        $creditoventa->idcliente = $request->idcliente;
        $creditoventa->numero_cuotas = $request->numero_cuotasCredito;
        $creditoventa->tiempo_dias_cuota = $request->tiempo_dias_cuotaCredito;
        $creditoventa->total = $request->totalCredito;
        $creditoventa->estado = $request->estadoCredito;

        $primerCuotaNoPagada = null;
        /*
        foreach ($request->cuotaspago as $cuota) {
            if ($cuota['estado'] !== 'Pagado') {
                $primerCuotaNoPagada = $cuota;
                break;
            }
        }
        $creditoventa->proximo_pago = $primerCuotaNoPagada['fecha_pago'];*/

        $creditoventa->save();

        return $creditoventa;
    }
    public function obtenerCuotas(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $id = $request->id;

        $cuotas = DB::table('cuotas_credito')
            ->where('idcredito', $id)
            ->orderBy('numero_cuota', 'asc')
            ->get();

        return ['cuotas' => $cuotas];
    }


    private function registrarCuotasCredito($creditoventa, $cuotas)
    {
        $numeroCuota = 1; // Inicializamos el número de cuota en 1

        foreach ($cuotas as $detalle) {
            $cuota = new CuotasCredito();
            $cuota->idcredito = $creditoventa->id;
            if ($detalle['estado'] == "Pagado") {
                $cuota->idcobrador = \Auth::user()->id;
                $cuota->fecha_cancelado = $detalle['fecha_cancelado']; // Podrías ajustar esto según tus necesidades

            } else {
                $cuota->idcobrador = null;
                $cuota->fecha_cancelado = null; // Podrías ajustar esto según tus necesidades


            }

            $cuota->numero_cuota = $numeroCuota++; // Asignamos el número de cuota y luego incrementamos
            $cuota->fecha_pago = $detalle['fecha_pago'];
            $cuota->precio_cuota = $detalle['precio_cuota'];
            $cuota->saldo_restante = $detalle['saldo_restante'];
            $cuota->estado = $detalle['estado'];
            $cuota->save();
        }
    }

    public function ventaSelecionada($id)
    {
        // Encuentra la venta
        $venta = Venta::find($id);

        if (!$venta) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        // 🔹 Obtener los detalles de esa venta
        $detalles = \App\DetalleVenta::where('idventa', $id)->get();

        // 🔸 Calcular el monto total de descuentos (en Bs)
        $totalDescuentoDetalles = 0;

        foreach ($detalles as $detalle) {
            $precioUnitario = (float) $detalle->precio;
            $cantidad = (float) $detalle->cantidad;
            $descuentoPorcentaje = (float) $detalle->descuento; // está en %

            // 💰 Convertir a monto real
            $montoDescuento = $precioUnitario * $cantidad * ($descuentoPorcentaje / 100);
            $totalDescuentoDetalles += $montoDescuento;
        }

        // 🔹 Descuento total real: el registrado menos lo calculado por detalle
        $descuentoFinal = (float) $venta->descuento_total - $totalDescuentoDetalles;
        if ($descuentoFinal < 0)
            $descuentoFinal = 0;

        // 🔹 Devolver la respuesta
        return response()->json([
            'id' => $venta->id,
            'num_comprobante' => $venta->num_comprobante,
            'total' => $venta->total,
            'descuento' => number_format($descuentoFinal, 2, '.', ''),
        ]);
    }


    /*public function cerrarVenta(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        try {
            DB::beginTransaction();

            $venta = Venta::findOrFail($request->id);
            $venta->idtipo_pago = $request->idtipo_pago;
            $venta->estado = $request->estado;

            // Buscar la caja asociada a la venta
            $ultimaCaja = Caja::find($venta->idcaja);

            if ($ultimaCaja) {
                // Actualizar las ventas y el saldo de la caja dependiendo del tipo de pago
                if ($venta->idtipo_pago == 1) {
                    $ultimaCaja->ventasContado += $venta->total;
                    $ultimaCaja->saldoCaja += $venta->total;
                    $ultimaCaja->saldototalventas += $venta->total;
                } elseif ($venta->idtipo_pago == 7) {
                    $ultimaCaja->ventasQR += $venta->total;
                    $ultimaCaja->saldototalventas += $venta->total;
                } elseif ($venta->idtipo_pago == 2) {
                    $ultimaCaja->ventasTarjeta += $venta->total;
                    $ultimaCaja->saldototalventas += $venta->total;
                }

                // Guardar la caja
                $ultimaCaja->save();
            } else {
                return response()->json([
                    'id' => -1,
                    'error' => 'No se encontró la caja asociada a esta venta'
                ]);
            }

            $venta->save();

            DB::commit();

            return response()->json(['id' => $venta->id]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'id' => -1,
                'error' => 'Ha ocurrido un error al cerrar la venta'
            ]);
        }
    }*/

    public function cerrarVenta(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        try {
            DB::beginTransaction();

            $venta = Venta::findOrFail($request->id);
            $estadoAnterior = $venta->estado;
            $venta->idtipo_pago = $request->idtipo_pago;

            // Si es venta a crédito (idtipo_venta = 2) dejar estado en 2
            if ($venta->idtipo_venta == 2) {
                $venta->estado = 2;
            } else {
                $venta->estado = $request->estado;
            }

            // Obtener tipo_comprobante de la venta
            $tipo_comprobante = $venta->tipo_comprobante;
            $idtipo_venta = $venta->idtipo_venta;

            $user = \Auth::user();
            $ultimaCaja = null;

            // ======================
            //   DETALLES DE VENTA
            // ======================
            $detallesVenta = DetalleVenta::where('idventa', $venta->id)->get();

            //$idsucursal = $venta->usuario->sucursal->id;
            /*$almacen = Almacen::where('sucursal', $idsucursal)
                ->where('id', '<>', 1) // excluir almacén id=1
                ->first();

            if (!$almacen) {
                return response()->json([
                    'id' => -1,
                    'error' => 'No existe un almacén válido para la sucursal asociada al usuario de la venta'
                ]);
            }

            $idalmacen = $almacen->id;*/

            $venta->save();

            // ======================
            //   AGRUPAR DETALLES
            // ======================
            $detallesAgrupados = $detallesVenta->groupBy('idarticulo')->map(function ($grupo) {
                $detalleEjemplo = $grupo->first();

                $articulo = Articulo::where('id', $detalleEjemplo->idarticulo)->first();

                if ($articulo) {
                    $actividadEconomica = Categoria::where('id', $articulo->idcategoria)->value('actividadEconomica');
                    $codigoProductoSin = Categoria::where('id', $articulo->idcategoria)->value('codigoProductoSin');
                    $codigo = $articulo->codigo;
                    $nombre = $articulo->nombre;
                    $precio_venta = $detalleEjemplo->precio;
                    $unidadMedida = Medida::where('id', $articulo->idmedida)->value('codigoClasificador');
                } else {
                    $actividadEconomica = null;
                    $codigoProductoSin = null;
                    $codigo = null;
                    $nombre = null;
                    $precio_venta = null;
                    $unidadMedida = null;
                }

                $cantidadTotal = $grupo->sum('cantidad');
                $descuentoTotal = $grupo->sum('descuento');

                return [
                    'actividadEconomica' => $actividadEconomica,
                    'codigoProductoSin' => $codigoProductoSin,
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'cantidad' => $cantidadTotal,
                    'precio_venta' => $precio_venta,
                    'montoDescuento' => $descuentoTotal,
                    'unidadMedida' => $unidadMedida
                ];
            });

            DB::commit();

            return response()->json([
                'id' => $venta->id,
                'tipo_comprobante' => $tipo_comprobante,
                'idtipo_venta' => $idtipo_venta,
                'detalles' => $detallesAgrupados->values()
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'id' => -1,
                'error' => 'Ha ocurrido un error al cerrar la venta: ' . $e->getMessage()
            ]);
        }
    }

    public function autorizarDescuento(Request $request)
    {

        $autorizacion = Autorizaciondescuento::updateOrCreate(
            ['idusuario' => $request->idusuario],
            ['puede_descontar' => $request->puede_descontar]
        );

        return response()->json([
            'message' => 'Autorización actualizada correctamente.',
            'autorizacion' => $autorizacion
        ]);
    }

    public function verificarDescuento()
    {
        $usuarioId = \Auth::user()->id;

        $autorizacion = Autorizaciondescuento::where('idusuario', $usuarioId)->first();

        return response()->json([
            'puedeDescontar' => $autorizacion ? (bool) $autorizacion->puede_descontar : false
        ]);
    }

    private function actualizarDescuentoUsuarioLogueado()
    {
        $usuarioId = \Auth::user()->id;
        $autorizacionDescuento = Autorizaciondescuento::where('idusuario', $usuarioId)->first(); // Buscar la autorización

        // Si el usuario tiene autorización para descontar, desactivarlo
        if ($autorizacionDescuento && $autorizacionDescuento->puede_descontar) {
            $autorizacionDescuento->puede_descontar = 0; // Cambiar el valor a 0
            $autorizacionDescuento->save(); // Guardar los cambios
        }
    }

    public function actualizarEstado(Request $request)
    {
        $venta = Venta::find($request->idventa);

        if ($venta) {
            // Si es venta a crédito (idtipo_venta = 2) → estado 6
            if ($venta->idtipo_venta == 2) {
                $venta->estado = 7;
            }
            // Si es contado (idtipo_venta = 1) → el estado que viene en el request
            else if ($venta->idtipo_venta == 1) {
                $venta->estado = $request->nuevoEstado;
            }

            $venta->save();
            return response()->json(['message' => 'Estado actualizado'], 200);
        }

        return response()->json(['message' => 'Venta no encontrada'], 404);
    }

    public function consultaPuntoVenta(Request $request)
    {
        $user = Auth::user();
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;
        $nombreSucursal = $sucursal->nombre;
        $nit = "8033811015";

        require "SiatController.php";
        $siat = new SiatController();
        $res = $siat->consultaPuntoVenta();
        //dd($res);
        if ($res->RespuestaConsultaPuntoVenta->transaccion === true) {
            $mensaje = $res;
        } else {
            $mensaje = $res->RespuestaCierrePuntoVenta->mensajesList->descripcion;
        }

        return response()->json([
            'mensaje' => $mensaje,
            'codSucursal' => $codSucursal,
            'nombreSucursal' => $nombreSucursal,
            'nit2' => $nit
        ], 200);
    }

    public function sincronizarListaProductosServicios()
    {
        $user = Auth::user();
        $codigoPuntoVenta = '';
        if (!empty($user->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($user->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        $puntoVenta = $codigoPuntoVenta;
        $sucursal = $user->sucursal;
        $codSucursal = $sucursal->codigoSucursal;

        require 'SiatController.php';
        $siat = new SiatController();
        $res = $siat->sincronizarListaProductosServicios($puntoVenta, $codSucursal);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function obtenerVenta($idventa)
    {
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;
        $idsucursal = $usuario->idsucursal;

        // Obtener código de punto de venta
        $codigoPuntoVenta = '';
        if (!empty($usuario->idpuntoventa)) {
            $puntoVenta = PuntoVenta::find($usuario->idpuntoventa);
            if ($puntoVenta) {
                $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            }
        }

        // Obtener código de sucursal
        $codigoSucursal = '';
        $sucursal = Sucursales::find($idsucursal);
        if ($sucursal) {
            $codigoSucursal = $sucursal->codigoSucursal;
        }

        try {
            $idventa = (int) $idventa;
            $venta = Venta::findOrFail($idventa);

            // Devolver la respuesta JSON con los datos de la venta y el código de sucursal
            return response()->json([
                'venta' => $venta,
                'codigoSucursal' => $codigoSucursal  // Incluir el código de sucursal
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Venta no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los datos'], 500);
        }
    }
    public function generarReporteCreditoPDF($clienteId)
{
    try {

        // Cliente
        $cliente = Persona::findOrFail($clienteId);

        // Empresa
        $empresa = Empresa::first();
        if (!$empresa) {
            return response()->json(['error' => 'NO SE ENCONTRÓ LA EMPRESA'], 404);
        }

        $movimientos = [];
        $inicio = request('inicio');
        $fin = request('fin');

        $filtrarFechas = $inicio && $fin;

        /* ------------------------------------------------------
        1. VENTAS AL CONTADO
        ------------------------------------------------------ */
        $ventasContado = Venta::where('idcliente', $clienteId)
            ->where('idtipo_venta', 1)
            ->where('estado', '!=', '0') // crédito
            ->when($filtrarFechas, function($q) use ($inicio, $fin) {
                $q->whereBetween('fecha_hora', [
                    Carbon::parse($inicio)->startOfDay(),
                    Carbon::parse($fin)->endOfDay()
                ]);
            })
            ->orderBy('fecha_hora', 'asc')
            ->get();

        foreach ($ventasContado as $venta) {
            $tipoPago = $venta->idtipo_pago == 1 ? "EFECTIVO" : "BANCO";

            $movimientos[] = [
                'fecha'       => Carbon::parse($venta->fecha_hora)->format('Y-m-d H:i:s'),
                'tipo'        => 'VENTAS',
                'descripcion' => "CLIENTES\n AL CONTADO - {$tipoPago}",
                'num_comprobante' => $venta->num_comprobante,
                'cuenta'      => '',
                'banco'       => '',
                'importe'     => $venta->total,
                'saldo'       => 0,
                'color'       => null
            ];
        }

        /* ------------------------------------------------------
        2. VENTAS A CRÉDITO
        ------------------------------------------------------ */
        $ventasCredito = Venta::where('idcliente', $clienteId)
            ->where('idtipo_venta', 2)
            ->where('estado', '!=', '0') // crédito
            ->when($filtrarFechas, function($q) use ($inicio, $fin) {
                $q->whereBetween('fecha_hora', [
                    Carbon::parse($inicio)->startOfDay(),
                    Carbon::parse($fin)->endOfDay()
                ]);
            })
            ->orderBy('fecha_hora', 'asc')
            ->get();
            

        foreach ($ventasCredito as $venta) {
            $fechaVenta = Carbon::parse($venta->fecha_hora)->subSecond();

            // 🔥 Buscar si esta venta tiene descuento de liquidación
            $cuotaLiquidacion = DB::table('cuotas_credito')
                ->where('idcredito', $venta->id)
                ->where('idtipo_pago', 5)
                ->where('descuento', '>', 0)
                ->first();
            
            $descuentoLiquidacion = $cuotaLiquidacion ? (float)$cuotaLiquidacion->descuento : 0;
            $montoOriginalVenta = (float)$venta->total + $descuentoLiquidacion;

            $movimientos[] = [
                'fecha'       => $fechaVenta->format('Y-m-d H:i:s'),
                'tipo'        => 'VENTAS',
                'descripcion' => "CLIENTES\nCRÉDITO",
                'num_comprobante' => $venta->num_comprobante,
                'cuenta'      => '',
                'banco'       => '',
                'importe'     => $montoOriginalVenta, 
                'saldo'       => $montoOriginalVenta,
                'color'       => null,
                // 🔥 NUEVO: Agregar info de liquidación si existe
                'descuento_liquidacion' => $descuentoLiquidacion,
                'monto_original_liquidacion' => $descuentoLiquidacion > 0 ? $montoOriginalVenta : null
            ];

            // Sus cuotas
            $cuotas = DB::table('cuotas_credito')
                ->where('idcredito', $venta->id)
                ->when($filtrarFechas, function($q) use ($inicio, $fin) {
                    $q->whereBetween('fecha_pago', [
                        Carbon::parse($inicio)->startOfDay(),
                        Carbon::parse($fin)->endOfDay()
                    ]);
                })
                ->orderBy('fecha_pago', 'asc')
                ->get();
            $numeroComprobanteVenta = $venta->num_comprobante;

            foreach ($cuotas as $cuota) {
                $tipoPago = '';
                $nombreCuenta = '';
                $nombreBanco  = '';
                $colorFila = null;

                if ($cuota->idtipo_pago == 5 && $cuota->descuento > 0) {
                    $movimientos[] = [
                        'fecha'       => $cuota->fecha_pago,
                        'tipo'        => 'COBRAR',
                        'descripcion' => "CLIENTES\nLIQUIDACIÓN",
                        'num_comprobante' => $numeroComprobanteVenta,
                        'cuenta'      => '',
                        'banco'       => '',
                        'importe'     => $cuota->descuento,
                        'saldo'       => $cuota->saldo_restante,
                        'color'       => 'yellow'
                    ];
                    continue;
                }
                if ($cuota->idtipo_pago == 4) {
                    $transaccion = DB::table('transacciones_cajas')
                        ->where('transaccion', 'like', '%'.$numeroComprobanteVenta.'%')
                        ->orderByDesc('id')
                        ->first();

                    $movimientos[] = [
                        'fecha'       => $cuota->fecha_pago,
                        'tipo'        => 'PAGO', // 👈 IMPORTANTE
                        'descripcion' => $transaccion
                            ? $transaccion->transaccion
                            : 'PAGO DE GASTO',
                        'num_comprobante' => $numeroComprobanteVenta,
                        'cuenta'      => '',
                        'banco'       => '',
                        'importe'     => abs($cuota->precio_cuota), // 👈 SIEMPRE POSITIVO
                        'saldo'       => $cuota->saldo_restante,
                        'color'       => null // 👈 NEGRO
                    ];

                    continue;
                }
                if ($cuota->idtipo_pago == 1) {
                    $tipoPago = 'EFECTIVO';
                    $cobrador = DB::table('users')->where('id', $cuota->idcobrador)->first();
                    $nombreCuenta = $cobrador ? $cobrador->usuario : 'Desconocido';
                } elseif ($cuota->idtipo_pago == 7) {
                    $tipoPago = 'BANCO';
                    $banco = DB::table('bancos')->where('id', $cuota->idbanco)->first();

                    if ($banco) {
                        $bancoArray = (array)$banco;

                        $nombreCuenta = trim(
                            ($bancoArray['nombre_cuenta'] ?? '') . '-' .
                            ($bancoArray['numero_cuenta'] ?? '')
                        );

                        if ($nombreCuenta == '-') $nombreCuenta = 'Cuenta no registrada';
                        $nombreBanco = $bancoArray['nombre_banco'] ?? $bancoArray['nombre'] ?? 'Banco desconocido';
                    }
                }

                $movimientos[] = [
                    'fecha'       => $cuota->fecha_pago,
                    'tipo'        => 'COBRAR',
                    'descripcion' => "CLIENTES\n{$tipoPago}",
                    'num_comprobante' => $numeroComprobanteVenta,
                    'cuenta'      => $nombreCuenta,
                    'banco'       => $nombreBanco,
                    'importe'     => $cuota->precio_cuota,
                    'saldo'       => $cuota->saldo_restante,
                    'color'       => null
                ];
            }
        }
// 🔹 Saldos a favor registrados en cuotas_credito (numero_cuota = 0)
$cuotasSaldoFavor = DB::table('cuotas_credito')
    ->where('idcredito', null)          // No tienen venta asociada
    ->where('numero_cuota', 0)         // Indica que es saldo a favor
    ->where('idcobrador', '!=', null)  // Opcional: filtrar si quieres solo pagos válidos
    ->where('idtipo_pago', '!=', 5)    // No incluir descuentos si quieres solo saldo a favor
    ->where('idcliente', $clienteId)   // Solo para este cliente
    ->get();
        foreach ($cuotasSaldoFavor as $cuota) {
    $movimientos[] = [
        'fecha'       => $cuota->fecha_pago,
        'tipo'        => 'SALDO_FAVOR',
        'descripcion' => 'SALDO A FAVOR REGISTRADO',
        'num_comprobante' => '',
        'cuenta'      => '',
        'banco'       => '',
        'importe'     => $cuota->precio_cuota,
        'saldo'       => $cuota->saldo_restante,
        'color'       => 'green',
        'numero_cuota'=> 0,
        'idcredito'   => null,
    ];
}

        /* ------------------------------------------------------
        ORDENAR TODOS LOS MOVIMIENTOS POR FECHA
        ------------------------------------------------------ */
        usort($movimientos, fn($a, $b) => strtotime($a['fecha']) <=> strtotime($b['fecha']));

        /* ------------------------------------------------------
        TOTAL ACUMULADO POR MOVIMIENTO (ESTADO DE CUENTA REAL)
        ------------------------------------------------------ */
        $totalAcumulado = 0;

        foreach ($movimientos as &$mov) {

            $importe = (float) $mov['importe'];

            // 🔵 VENTAS SUMAN
            if ($mov['tipo'] === 'VENTAS') {
                $totalAcumulado += $importe;
            }

            // 🔴 COBROS RESTAN
            if ($mov['tipo'] === 'COBRAR') {
                $totalAcumulado -= abs($importe);
            }

            // 🟠 PAGOS SUMAN (gastos / cargos)
            if ($mov['tipo'] === 'PAGO') {
                $totalAcumulado += abs($importe);
            }

            // 🔥 TOTAL ACUMULADO REAL
            $mov['total_acumulado'] = round($totalAcumulado, 2);
        }
        unset($mov);

        /* ------------------------------------------------------
        AGRUPACIÓN POR MES
        ------------------------------------------------------ */
        $agrupado = collect($movimientos)
            ->groupBy(fn($item) => Carbon::parse($item['fecha'])->format('F Y'));

        $mesesEs = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre',
        ];

        $movimientos_por_mes = [];
        $saldoAcumulado = 0;
        $saldoAFavor = 0; // 🔥 NUEVO: Track saldo a favor por separado

        foreach ($agrupado as $mesIngles => $items) {
            [$mesEng, $anio] = explode(' ', $mesIngles);
            $mesEsp = $mesesEs[$mesEng] . ' ' . $anio;

            $saldoAnterior = $saldoAcumulado;
            $saldoAFavorAnterior = $saldoAFavor;
            $totalVenta = 0;
            $totalCobros = 0;
            $itemsModificados = [];
            $inicioMes = Carbon::createFromFormat('F Y', $mesIngles)->startOfMonth();
            $finMes    = Carbon::createFromFormat('F Y', $mesIngles)->endOfMonth();
            // 🔥 TOTAL VENTAS = SUMA REAL DE LA TABLA ventas.total
            $totalVenta = Venta::where('idcliente', $clienteId)
                        ->where('estado', '!=', '0') // crédito
                ->whereBetween('fecha_hora', [$inicioMes, $finMes])
                ->sum('total');

            // 🔥 TOTAL COBROS (opcional, si lo sigues usando)
            $totalCobros = DB::table('ventas as v')
            ->leftJoin(DB::raw("
                (
                    SELECT cc1.idcredito, cc1.saldo_restante
                    FROM cuotas_credito cc1
                    INNER JOIN (
                        SELECT idcredito, MAX(numero_cuota) AS max_cuota
                        FROM cuotas_credito
                        GROUP BY idcredito
                    ) cc2
                    ON cc2.idcredito = cc1.idcredito AND cc2.max_cuota = cc1.numero_cuota
                ) ult
            "), 'ult.idcredito', '=', 'v.id')
            ->where('v.idcliente', $clienteId)
            ->where('v.idtipo_venta', 2)
                        ->where('v.estado', '!=', '0') // crédito
            ->whereBetween('v.fecha_hora', [$inicioMes, $finMes])
            ->selectRaw("
                SUM(
                    CASE
                        WHEN ult.idcredito IS NULL THEN v.total
                        WHEN ult.saldo_restante > 0 THEN ult.saldo_restante
                        ELSE 0
                    END
                ) AS total
            ")
            ->value('total');


            foreach ($items as $i) {
                $itemMod = $i; // Copia para modificar
                
                // 🔥 LIQUIDACIÓN: El descuento se suma a la venta (porque ya fue descontado del total)
                if (!empty($i['color']) && $i['color'] === 'yellow') {
                    
                    $itemsModificados[] = $itemMod;
                    continue;
                }
                if ($i['tipo'] === 'VENTAS' && str_contains($i['descripcion'], 'AL CONTADO')) {
                    $itemsModificados[] = $itemMod;
                    continue;
                }

                if ($i['tipo'] === 'VENTAS') {
                    $importeVenta = (float)$i['importe'];
                    
                    // 🔥 Si hay saldo a favor, el importe ya viene con el descuento aplicado
                    // Entonces: monto_original = importe + saldo_favor
                    if ($saldoAFavor > 0) {
                        $descuentoAplicado = min($saldoAFavor, $importeVenta + $saldoAFavor);
                        // El monto original es el importe actual + el saldo a favor que se aplicó
                        $montoOriginal = $importeVenta + $descuentoAplicado;
                        $itemMod['saldo_favor_aplicado'] = $descuentoAplicado;
                        $itemMod['saldo_original'] = $montoOriginal; // 🔥 Monto original = importe + saldo aplicado
                        $saldoAFavor -= $descuentoAplicado;
                        // El importeVenta ya viene con el descuento, no lo modificamos
                    }
                }

                if ($i['tipo'] === 'COBRAR') {
                    $cobro = (float)$i['importe'];
                    $saldoRestante = (float)$i['saldo'];
                    

                    
                    // 🔥 Si el saldo restante es negativo, es saldo a favor
                    if ($saldoRestante < 0) {
                        $saldoAFavor = abs($saldoRestante);
                        $itemMod['es_saldo_favor'] = true;
                    }
                }
                
                $itemsModificados[] = $itemMod;
            }

            $saldoMes = $totalVenta - $totalCobros;

            $saldoAcumulado = max(0, $saldoAnterior + $saldoMes);

            $movimientos_por_mes[$mesEsp] = [
                'items'          => $itemsModificados,
                'totalVenta'     => $totalVenta,   // 👈 SUM(ventas.total)
                'totalCobros'    => $totalCobros,  // 👈 saldo pendiente real
                'saldoMes'       => $totalVenta - $totalCobros,
                'saldoAnterior'  => max(0, $saldoAnterior),
                'saldoAcumulado' => $saldoAcumulado,
                'saldoAFavor'    => $saldoAFavor,
            ];

        }

        /* ------------------------------------------------------
        GENERAR PDF
        ------------------------------------------------------ */
        
        // 🔥 Calcular totales como en Cobros.vue
        $totalVentasSuma = 0;
        $saldoRestanteSuma = 0;
        
        // Sumar totales de ventas a crédito
        foreach ($ventasCredito as $venta) {
            // Total de venta + descuento_total (liquidación ya descontada del total)
            $totalVentasSuma += (float)$venta->total + (float)($venta->descuento_total ?? 0);
            
            // Buscar si tiene liquidación y sumar ese importe
            $cuotaLiquidacion = DB::table('cuotas_credito')
                ->where('idcredito', $venta->id)
                ->where('idtipo_pago', 5)
                ->where('descuento', '>', 0)
                ->first();
            if ($cuotaLiquidacion) {
                $totalVentasSuma += (float)$cuotaLiquidacion->descuento;
            }
            
            $saldoRestante = (float)$venta->saldo_restante;
            if ($saldoRestante > 0) {
                $saldoRestanteSuma += $saldoRestante;
            }
        }
        
        // Sumar totales de ventas al contado
        foreach ($ventasContado as $venta) {
            $totalVentasSuma += (float)$venta->total;
        }
        
        $data = [
            'empresa'         => $empresa,       // <-- Aquí pasamos la empresa
            'cliente'         => $cliente,
            'movimientos'     => $movimientos_por_mes,
            'fecha_generacion'=> Carbon::now()->format('d/m/Y H:i'),
            'saldoFavorCliente' => (float) ($cliente->saldo_favor ?? 0), // 🔥 Saldo a favor desde tabla personas
            'totalVentasSuma' => $totalVentasSuma, // 🔥 Total de ventas como en Cobros.vue
            'saldoRestanteSuma' => $saldoRestanteSuma, // 🔥 Saldo restante (solo positivos de crédito)
        ];

        $pdf = PDF::loadView('pdf.reporte-credito', $data)->setPaper('A4', 'portrait');

        return $pdf->stream("estado_cuenta_{$cliente->id}.pdf");

    } catch (\Exception $e) {
        return "Error generando PDF: " . $e->getMessage();
    }
}

    public function obtenerCuotasid($idcredito)
{
    try {
        $cuotas = CuotasCredito::select(
                'cuotas_credito.*',
                'tipos_pago.nombre as tipo_pago_nombre',
                'bancos.nombre_cuenta as nombre_cuenta'
            )
            ->leftJoin('tipos_pago', 'tipos_pago.id', '=', 'cuotas_credito.idtipo_pago')
            ->leftJoin('bancos', 'bancos.id', '=', 'cuotas_credito.idbanco')
            ->where('cuotas_credito.idcredito', $idcredito)
            ->orderBy('cuotas_credito.numero_cuota', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'cuotas' => $cuotas
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener cuotas.',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function obtenerVentaCompleta($idventa)
{
    try {
        // 🔹 Buscar venta principal
        $venta = Venta::findOrFail($idventa);

        // -----------------------------------------------------------
        // 🔹 Obtener datos del cliente
        // -----------------------------------------------------------
        $cliente = null;

        if (!empty($venta->idcliente)) {
            $cliente = Persona::where('id', $venta->idcliente)
                ->select(
                    'id',
                    'nombre',
                    'num_documento',
                    'telefono',
                    'tipo_documento'
                )
                ->first();
        }

        // -----------------------------------------------------------
        // 🔹 Recuperar detalles con stock actual
        // -----------------------------------------------------------
        $detalles = DetalleVenta::join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->leftJoin('inventarios', function ($join) use ($venta) {
                $join->on('inventarios.idarticulo', '=', 'detalle_ventas.idarticulo')
                     ->where('inventarios.idalmacen', '=', $venta->idalmacen);
            })
            ->select(
                'detalle_ventas.id',
                'detalle_ventas.idarticulo',
                'articulos.nombre as articulo',
                'articulos.unidad_envase',
                'articulos.descripcion_fabrica',
                'detalle_ventas.cantidad',
                'detalle_ventas.precio',
                'detalle_ventas.descuento',
                'detalle_ventas.modo_venta',
                'articulos.codigo',
                'articulos.precio_uno',
                'articulos.precio_dos',

                // 🔸 Stock actual
                DB::raw("IFNULL(SUM(inventarios.saldo_stock), 0) as saldo_stock"),
                DB::raw("ROUND(IFNULL(SUM(inventarios.saldo_stock) / NULLIF(articulos.unidad_envase, 0), 0), 2) as saldo_stock_cajas"),

                // 🔸 Precio seleccionado en la venta
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN detalle_ventas.modo_venta = 'caja' 
                                THEN detalle_ventas.precio * articulos.unidad_envase
                            ELSE 
                                detalle_ventas.precio
                        END
                    , 2) as precioseleccionado
                "),

                // 🔸 Subtotal sin descuento
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN detalle_ventas.modo_venta = 'caja' 
                                THEN detalle_ventas.precio * articulos.unidad_envase * detalle_ventas.cantidad
                            ELSE 
                                detalle_ventas.precio * detalle_ventas.cantidad
                        END
                    , 2) as subtotal_sin_descuento
                "),

                // 🔸 Descuento aplicado
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN detalle_ventas.modo_venta = 'caja' 
                                THEN (detalle_ventas.precio * articulos.unidad_envase * detalle_ventas.cantidad) * (detalle_ventas.descuento / 100)
                            ELSE 
                                (detalle_ventas.precio * detalle_ventas.cantidad) * (detalle_ventas.descuento / 100)
                        END
                    , 2) as descuento_monto
                "),

                // 🔸 Subtotal final
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN detalle_ventas.modo_venta = 'caja' 
                                THEN (detalle_ventas.precio * articulos.unidad_envase * detalle_ventas.cantidad) 
                                    - ((detalle_ventas.precio * articulos.unidad_envase * detalle_ventas.cantidad) * (detalle_ventas.descuento / 100))
                            ELSE 
                                (detalle_ventas.precio * detalle_ventas.cantidad) 
                                    - ((detalle_ventas.precio * detalle_ventas.cantidad) * (detalle_ventas.descuento / 100))
                        END
                    , 2) as subtotal
                ")
            )
            ->where('detalle_ventas.idventa', $idventa)
            ->groupBy(
                'detalle_ventas.id',
                'detalle_ventas.idarticulo',
                'articulos.nombre',
                'articulos.unidad_envase',
                'articulos.descripcion_fabrica',
                'detalle_ventas.cantidad',
                'detalle_ventas.precio',
                'detalle_ventas.descuento',
                'detalle_ventas.modo_venta',
                'articulos.codigo',
                'articulos.precio_uno',
                'articulos.precio_dos'
            )
            ->orderBy('detalle_ventas.id', 'asc')
            ->get();

        // 🔹 Respuesta final
        return response()->json([
            'venta' => $venta,
            'cliente' => $cliente,
            'detalles' => $detalles
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Venta no encontrada'], 404);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al obtener la venta completa'], 500);
    }
}

public function actualizarVenta(Request $request)
{
    try {
        DB::beginTransaction();

        $venta = Venta::findOrFail($request->idventa);

        // ----------------------------------------------------------
        // 🟦 GUARDAR DETALLES ORIGINALES ANTES DE MODIFICAR NADA
        // ----------------------------------------------------------
        $detallesOriginales = DetalleVenta::where('idventa', $venta->id)->get();

        // ----------------------------------------------------------
        // 🟦 ACTUALIZAR CLIENTE
        // ----------------------------------------------------------
        if ($venta->idcliente) {
            $cliente = Persona::find($venta->idcliente);
            if ($cliente) {
                $cliente->num_documento = $request->cliente['num_documento'];
                $cliente->nombre = $request->cliente['nombre'];
                $cliente->telefono = $request->cliente['telefono'];
                $cliente->tipo_documento = $request->cliente['tipo_documento'];
                $cliente->save();
            }
        }

        // ----------------------------------------------------------
        // 🟦 ACTUALIZAR DETALLES (BORRAR LOS QUE YA NO EXISTEN)
        // ----------------------------------------------------------
        $idsEnviados = collect($request->detalles)->pluck('iddetalle')->filter();

        DetalleVenta::where('idventa', $venta->id)
            ->whereNotIn('id', $idsEnviados)
            ->delete();

        foreach ($request->detalles as $item) {

            $detalle = null;

            if (!empty($item['iddetalle'])) {
                $detalle = DetalleVenta::find($item['iddetalle']);
            }

            // 👉 Si NO existe en BD, se crea nuevo
            if (!$detalle) {
                $detalle = new DetalleVenta();
                $detalle->idventa = $venta->id;
            }

            $detalle->idarticulo = $item['idarticulo'];
            $detalle->cantidad = $item['cantidad'];
            $detalle->precio = $item['precio'];
            $detalle->descuento = $item['descuento'];
            $detalle->modo_venta = $item['modo_venta'];
            $detalle->save();
        }

        // ----------------------------------------------------------
        // 🟦 AJUSTAR INVENTARIO (REVERTIR → APLICAR NUEVO)
        // ----------------------------------------------------------

        // 🔄 Revertir inventario original
        foreach ($detallesOriginales as $det) {

            $articulo = Articulo::find($det->idarticulo);
            $unidadEnvase = $articulo->unidad_envase ?? 1;

            $unidades = $det->modo_venta === 'caja'
                ? $det->cantidad * $unidadEnvase
                : $det->cantidad;

            DB::table('inventarios')
                ->where('idalmacen', $venta->idalmacen)
                ->where('idarticulo', $det->idarticulo)
                ->increment('saldo_stock', $unidades);
        }

        // 🔄 Aplicar inventario nuevo
        foreach ($request->detalles as $item) {

            $articulo = Articulo::find($item['idarticulo']);
            $unidadEnvase = $articulo->unidad_envase ?? 1;

            $unidades = $item['modo_venta'] === 'caja'
                ? $item['cantidad'] * $unidadEnvase
                : $item['cantidad'];

            DB::table('inventarios')
                ->where('idalmacen', $venta->idalmacen)
                ->where('idarticulo', $item['idarticulo'])
                ->decrement('saldo_stock', $unidades);
        }

        // ----------------------------------------------------------
        // 🟦 ACTUALIZAR TOTAL DE LA VENTA
        // ----------------------------------------------------------
        $totalAnterior = (float)$venta->total;

        $venta->total = $request->total;

        // ----------------------------------------------------------
        // 🟦 ASIGNAR CAJA A LA VENTA (si no tiene o si deseas actualizarla)
        // ----------------------------------------------------------
        $caja = Caja::where('idusuario', $venta->idusuario)
                    ->where('estado', '!=', 'CERRADO')
                    ->orderBy('id', 'desc')
                    ->first();

        if ($caja) {
            $venta->idcaja = $caja->id; // 🔥 se actualiza idcaja
        }

        $venta->save();

        // ----------------------------------------------------------
        // 🟦 ACTUALIZAR CUOTAS DE CRÉDITO
        // ----------------------------------------------------------
        $cuotas = CuotasCredito::where('idcredito', $venta->id)
            ->orderBy('numero_cuota', 'asc')
            ->get();

        if ($cuotas->count() > 0) {

            $totalNuevo = (float)$request->total;
            $diferencia = $totalNuevo - $totalAnterior;

            foreach ($cuotas as $cuota) {

                $nuevoSaldo = (float)$cuota->saldo_restante + $diferencia;

                if ($nuevoSaldo < 0) $nuevoSaldo = 0;

                $cuota->saldo_restante = $nuevoSaldo;
                $cuota->save();
            }
        }

        // ----------------------------------------------------------
        // 🟦 ACTUALIZAR CAJA (SOLO SI VENTA AL CONTADO)
        // ----------------------------------------------------------
        if ($venta->idtipo_venta == 1 && $caja) {

            $diferencia = (float)$request->total - (float)$totalAnterior;

            // ventas globales
            $caja->ventas += $diferencia;

            // efectivo
            if ($venta->idtipo_pago == 1) {
                $caja->ventasContado += $diferencia;
                $caja->saldoCaja += $diferencia; // entra/sale efectivo físico
            }

            // QR
            if ($venta->idtipo_pago == 7) {
                $caja->ventasQR += $diferencia;
                // QR no afecta saldo físico de caja
            }

            // total de ventas
            $caja->saldototalventas += $diferencia;

            $caja->save();
        }

        // ----------------------------------------------------------
        // 🟦 FIN — GUARDADO EXITOSO
        // ----------------------------------------------------------
        DB::commit();
        return response()->json(['message' => 'Venta actualizada correctamente']);

    } catch (\Exception $e) {

        DB::rollBack();

        \Log::error("Error al actualizar venta", [
            'exception' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'request' => $request->all()
        ]);

        return response()->json([
            'error' => 'Error al actualizar venta',
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
public function registrarGastoCredito(Request $request)
{
    DB::beginTransaction();

    try {
        $request->validate([
            'idventa'     => 'required|integer|exists:ventas,id',
            'monto'       => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:255',
            'idbanco'     => 'nullable|integer|exists:bancos,id'
        ]);

        $venta = Venta::findOrFail($request->idventa);
        $usuario = Auth::user();

        // Caja actual
        $caja = Caja::where('idsucursal', $usuario->idsucursal)
            ->latest()
            ->first();

        if (!$caja) {
            throw new \Exception('No existe caja abierta.');
        }

        // Última cuota
        $ultimaCuota = CuotasCredito::where('idcredito', $venta->id)
            ->orderByDesc('numero_cuota')
            ->first();

        $saldoAnterior = $ultimaCuota
            ? floatval($ultimaCuota->saldo_restante)
            : floatval($venta->total);

        // 🔥 El gasto AUMENTA la deuda
        $nuevoSaldo = $saldoAnterior + floatval($request->monto);

        $numeroCuota = $ultimaCuota
            ? $ultimaCuota->numero_cuota + 1
            : 1;

      // =========================
        // REGISTRAR GASTO COMO CUOTA
        // =========================
        $cuota = CuotasCredito::create([
            'idcredito'       => $venta->id,
            'idcaja'          => $caja->id,
            'idcobrador'      => $usuario->id,
            'numero_cuota'    => $numeroCuota,
            'fecha_pago'      => now(),
            'fecha_cancelado' => now(),
            'precio_cuota'    => floatval($request->monto),
            'descuento'       => 0,
            'saldo_restante'  => $nuevoSaldo,
            'estado'          => 'Pendiente',
            'idtipo_pago'     => 4, // GASTO
            'idbanco'         => $request->filled('idbanco') ? $request->idbanco : null,
        ]);

        // =========================
        // REGISTRAR TRANSACCIÓN CAJA
        // =========================
        $venta = Venta::with('cliente')->findOrFail($request->idventa);

        $clienteNombre = DB::table('personas')
            ->where('id', $venta->idcliente)
            ->value('nombre');

        $descripcionTransaccion =
            'Gasto | NºComprobante: ' . ($venta->num_comprobante ?? 'S/N') .
            ' | Cliente: ' . ($clienteNombre ?? 'N/A') .
            ' | ' . trim($request->descripcion);
        $tipoPagoCaja = $request->filled('idbanco') ? 7 : 1;

        TransaccionesCaja::create([
            'idcaja'           => $caja->id,
            'idusuario'        => $usuario->id,
            'fecha'            => now(),
            'transaccion'      => $descripcionTransaccion,
            'importe'          => floatval($request->monto),
            'tipo_pago'        => $tipoPagoCaja,
            'idbanco'          => $request->filled('idbanco') ? $request->idbanco : null,
            'idcuota_credito'  => $cuota->id, // <-- aquí va el ID de la cuota creada
        ]);
        // =========================
        // AFECTAR CAJA (SALE DINERO)
        // =========================
        $caja->salidas += floatval($request->monto);
        $caja->saldoCaja -= floatval($request->monto);
        $caja->saldototalventas -= floatval($request->monto);
        $caja->save();

        // Venta vuelve a crédito activo
        $venta->estado = 2;
        $venta->save();

        DB::commit();

        return [
            'success'      => true,
            'message'      => 'Gasto registrado correctamente.',
            'nuevo_saldo'  => $nuevoSaldo
        ];

    } catch (\Exception $e) {
        DB::rollBack();
        return [
            'success' => false,
            'error'   => $e->getMessage()
        ];
    }
}
public function obtenerCuotasPorCliente($clienteId)
{
    try {

        // 🔎 Ventas a crédito del cliente
        $ventas = Venta::where('idcliente', $clienteId)
            ->where('idtipo_venta', 2)
            ->orderBy('fecha_hora', 'asc')
            ->get();

        // ❗ Si no hay ventas
        if ($ventas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente no tiene ventas a crédito registradas.',
                'data' => []
            ]);
        }

        $resultado = [];
        $totalSaldoPendiente = 0;
        $totalSaldoFavor = 0;

        foreach ($ventas as $venta) {

            // 🔎 Cuotas de la venta
            $cuotas = DB::table('cuotas_credito')
                ->where('idcredito', $venta->id)
                ->orderBy('numero_cuota', 'asc')
                ->get();

            // 🔥 Calcular saldo restante real de la venta
            $ultimaCuota = $cuotas->last();
            $saldoRestanteVenta = $ultimaCuota ? (float) $ultimaCuota->saldo_restante : (float) $venta->total;

            if ($saldoRestanteVenta > 0) {
                $totalSaldoPendiente += $saldoRestanteVenta;
            } elseif ($saldoRestanteVenta < 0) {
                $totalSaldoFavor += abs($saldoRestanteVenta);
            }

            $resultado[] = [
                'idventa'           => $venta->id,
                'num_comprobante'   => $venta->num_comprobante,
                'fecha'             => $venta->fecha_hora,
                'total'             => (float) $venta->total,
                'saldo_restante'    => $saldoRestanteVenta,
                'cuotas'            => $cuotas
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Cuotas del cliente obtenidas correctamente.',
            'resumen' => [
                'total_ventas_credito' => count($resultado),
                'saldo_pendiente'      => $totalSaldoPendiente,
                'saldo_favor'          => $totalSaldoFavor
            ],
            'data' => $resultado
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Error al obtener las cuotas del cliente.',
            'error'   => $e->getMessage()
        ], 500);
    }
}
}
