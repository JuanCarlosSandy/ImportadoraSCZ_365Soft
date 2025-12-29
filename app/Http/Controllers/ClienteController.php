<?php

namespace App\Http\Controllers;

use App\CreditoVenta;

use Illuminate\Http\Request;
use App\Persona;
use App\Exports\ClientExport;
use App\Imports\ClienteImport;
use App\User;
use App\Venta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Exception;


class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->buscar;

        $idsExcluidos = DB::table('users')->select('id')
            ->union(DB::table('proveedores')->select('id'));

        $usuarios = Persona::whereNotIn('id', $idsExcluidos)
            ->when(!empty($buscar), function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('num_documento', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        return [
            'total' => $usuarios->count(),
            'usuarios' => $usuarios
        ];
    }
    public function indexClientesConSaldoCredito(Request $request)
    {
        $buscar = $request->buscar;
        $ubicacion = $request->ubicacion;

        // 🔴 Excluir users y proveedores
        $idsExcluidos = DB::table('users')->select('id')
            ->union(DB::table('proveedores')->select('id'));

        // 🔹 Obtener clientes (personas)
        $usuarios = Persona::whereNotIn('id', $idsExcluidos)

            // 👉 filtro por ubicación (si se envía)
            ->when(!empty($ubicacion), function ($q) use ($ubicacion) {
                $q->where('direccion', $ubicacion);
            })

            // 👉 filtro de búsqueda
            ->when(!empty($buscar), function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('num_documento', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%")
                        ->orWhere('direccion', 'like', "%{$buscar}%");
                });
            })

            ->orderBy('id', 'desc')
            ->get();

        // 🟢 CALCULAR SALDO DE CRÉDITO POR CLIENTE
        foreach ($usuarios as $usuario) {

            // 🔍 ventas a crédito del cliente
            $ventasCredito = DB::table('ventas')
                ->where('idcliente', $usuario->id)
                ->where('idtipo_venta', 2) // crédito
                ->where('estado', '!=', '0') // crédito
                ->get(['id', 'total']);

            if ($ventasCredito->isEmpty()) {
                $usuario->saldo_credito_total = 0;
                continue;
            }

            $saldoTotal = 0;

            foreach ($ventasCredito as $venta) {

                // 🔍 última cuota
                $ultimaCuota = DB::table('cuotas_credito')
                    ->where('idcredito', $venta->id)
                    ->orderByDesc('numero_cuota')
                    ->first();

                if ($ultimaCuota) {
                    // 👉 tiene cuotas
                    $saldoTotal += floatval($ultimaCuota->saldo_restante);
                } else {
                    // 👉 NO tiene cuotas → usar total de la venta
                    $saldoTotal += floatval($venta->total);
                }
            }

            $usuario->saldo_credito_total = $saldoTotal;
        }

        return [
            'total' => $usuarios->count(),
            'usuarios' => $usuarios
        ];
    }

    public function indexUbicacion(Request $request)
    {
        $buscar = $request->buscar;
        $ubicacion = $request->ubicacion;

        // IDs que NO son clientes (users y proveedores)
        $idsExcluidos = DB::table('users')->select('id')
            ->union(DB::table('proveedores')->select('id'));

        $usuarios = Persona::whereNotIn('id', $idsExcluidos)

            // 👉 FILTRO POR UBICACIÓN SOLO SI EL USUARIO SELECCIONA UNA
            ->when(!empty($ubicacion), function ($q) use ($ubicacion) {
                $q->where('direccion', $ubicacion);
            })

            // 👉 FILTRO GENERAL DE BÚSQUEDA
            ->when(!empty($buscar), function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('num_documento', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%")
                        ->orWhere('direccion', 'like', "%{$buscar}%");
                });
            })

            ->orderBy('id', 'desc')
            ->get();

        return [
            'total' => $usuarios->count(),
            'usuarios' => $usuarios
        ];
    }

    public function indexConSaldoCredito(Request $request)
    {
        $buscar = $request->buscar;

        // excluir usuarios y proveedores igual que tu método original
        $idsExcluidos = DB::table('users')->select('id')
            ->union(DB::table('proveedores')->select('id'));

        // obtenemos clientes (personas)
        $usuarios = Persona::whereNotIn('id', $idsExcluidos)
            ->when(!empty($buscar), function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('num_documento', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        // AGREGAR SALDO DE CRÉDITO A CADA CLIENTE
        foreach ($usuarios as $usuario) {

            // 🔍 Obtener todas las ventas a crédito que tenga el cliente
            $ventasCredito = DB::table('ventas')
                ->where('idcliente', $usuario->id)
                ->where('idtipo_venta', 2)  // 2 = Crédito
                ->pluck('id');              // solo ids de ventas

            if ($ventasCredito->isEmpty()) {
                // no tiene créditos
                $usuario->saldo_credito_total = 0;
                continue;
            }

            $saldoTotal = 0;

            foreach ($ventasCredito as $idVentaCredito) {

                // 🔍 Obtener la última cuota del crédito (mayor numero_cuota)
                $ultimaCuota = DB::table('cuotas_credito')
                    ->where('idcredito', $idVentaCredito)
                    ->orderBy('numero_cuota', 'desc')
                    ->first();

                if ($ultimaCuota) {
                    $saldoTotal += floatval($ultimaCuota->saldo_restante);
                }
            }

            // asignar saldo total al cliente
            $usuario->saldo_credito_total = $saldoTotal;
        }

        return [
            'total' => $usuarios->count(),
            'usuarios' => $usuarios
        ];
    }

    public function selectCliente(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->filtro;
        $clientes = Persona::where('nombre', 'like', '%' . $filtro . '%')
            ->whereNull('direccion')
            ->where('usuario', '>', 0)
            ->select('id', 'nombre', 'tipo_documento', 'num_documento', 'complemento_id', 'email', 'telefono')
            ->orderBy('nombre', 'asc')
            ->take(5)
            ->get();

        $clientesConCreditos = $clientes->map(function ($cliente) {
            $cantidadCreditos = CreditoVenta::where('idcliente', $cliente->id)
                ->where('estado', '!=', 'Completado')
                ->count();
            $cliente->cantidad_creditos = $cantidadCreditos;
            return $cliente;
        });

        return ['clientes' => $clientesConCreditos];
    }


    public function seleccionarClientePorNumero(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->numero;
        $clientes = Persona::where('num_documento', 'like', '%' . $filtro . '%')
            ->whereNull('direccion')
            ->where('usuario', '>', 0)
            ->select('id', 'nombre', 'tipo_documento', 'num_documento', 'email', 'telefono')
            ->orderBy('tipo_documento', 'desc')
            ->take(5)
            ->get();
        $clientesConCreditos = $clientes->map(function ($cliente) {
            $cantidadCreditos = CreditoVenta::where('idcliente', $cliente->id)
                ->where('estado', '!=', 'Completado')
                ->count();

            $cliente->cantidad_creditos = $cantidadCreditos;
            return $cliente;
        });

        return ['clientes' => $clientesConCreditos];

        //return ['clientes' => $clientes];
    }

    public function store(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        // Verificar si ya existe un cliente con este número de documento
        $clienteExistente = Persona::where('num_documento', $request->num_documento)->first();

        if ($clienteExistente) {
            // Si ya existe, devolver ese ID
            return ['id' => $clienteExistente->id];
        }

        // Si no existe, crear uno nuevo
        $persona = new Persona();
        $persona->nombre = $request->nombre;
        $persona->usuario = Auth::user()->iduse;
        $persona->tipo_documento = $request->tipo_documento ?? null;
        $persona->num_documento = $request->num_documento ?? null;
        $persona->complemento_id = $request->complemento ?? null;
        $persona->telefono = $request->telefono ?? null;
        $persona->direccion = $request->direccion ?? null;
        $persona->save();

        return ['id' => $persona->id];
    }

    public function update(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $persona = Persona::findOrFail($request->id);
        $persona->nombre = $request->nombre;
        $persona->usuario = $request->usuariodos_id;
        $persona->tipo_documento = $request->tipo_documento;
        $persona->num_documento = $request->num_documento;
        $persona->complemento_id = $request->complemento;
        $persona->telefono = $request->telefono;
        $persona->direccion = $request->direccion;

        $persona->save();
        Log::info('DAtOS ACTU8ALIZAR!!:', [
            'DATOS' => $persona,
        ]);
    }

    public function listarReporteClienteExcel()
    {
        return Excel::download(new ClientExport, 'clientes.xlsx');
    }

    //---seleccionar usuario vendedor--
    public function selectUsuarioVendedor(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->filtro;
        $clientes = User::join('personas', 'users.id', '=', 'personas.id')
            ->select(
                'personas.id as ID',
                'personas.nombre',
                'users.idrol',
                'users.iduse as ID_use'
            )->where('users.idrol', '=', 2)
            ->orWhere('personas.nombre', 'like', '%' . $filtro . '%')
            ->orderBy('personas.nombre', 'asc')
            //->toSql();
            ->get();

        return ['clientes' => $clientes];
    }
    //---listado por id lo que se pidio de Personana--
    public function indexUsuario(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $idusuario = $request->idusuario;
        $usuario = User::join('personas', 'users.id', '=', 'personas.id')
            //->join('roles', 'users.idrol', '=', 'roles.id')
            ->select(
                'personas.id as ID',
                'personas.nombre',
                'personas.usuario',
                //'users.iduse as ID_use'
            )
            //->where('personas.usuario', '=', $idusuario)->get();
            ->where('users.iduse', '=', $idusuario)->get();
        return ['usuario' => $usuario];
    }
    //---listado por id lo que se pidio de Personana--
    public function indexUsuarioFiltro(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        //$idusuario = $request->idusuario;
        $filtro = $request->filtro;
        $usuariodos = User::join('personas', 'users.id', '=', 'personas.id')
            //->join('roles', 'users.idrol', '=', 'roles.id')
            ->select(
                'personas.nombre',
                'personas.usuario',
                'users.iduse'
            )
            //->where('personas.usuario', '=', $idusuario)->get();
            ->where('users.idrol', '=', 2)
            ->orWhere('personas.nombre', 'like', '%' . $filtro . '%')
            ->orderBy('personas.nombre', 'asc')
            //->toSql();
            ->get();

        return ['usuariodos' => $usuariodos];
    }

    public function getUserInfo()
    {
        $user = Auth::user();
        return response()->json(['user' => $user]);
    }
    // public function selectUsuarioVendedor(Request $request){
    //     if (!$request->ajax()) return redirect('/');

    //     $buscar = $request->buscar;
    //     $criterio = $request->criterio;

    //     if ($buscar==''){
    //         $personas = User::join('personas', 'users.id', '=', 'personas.id')
    //             ->join('roles', 'users.idrol', '=', 'roles.id')
    //             ->select(
    //                 'personas.id as ID', 'personas.nombre',
    //                 'roles.id',
    //                 )  ->where('roles.id', '=', '2')->paginate(6);
    //         Persona::orderBy('id', 'desc')->paginate(6);
    //     }
    //     else{
    //         $personas = User::join('personas', 'users.id', '=', 'personas.id')
    //         ->join('roles', 'users.idrol', '=', 'roles.id')
    //         ->select(
    //             'personas.id', 'personas.nombre',
    //             'roles.id as ID',
    //             )  ->where('roles.id', '=', '2')->paginate(6);        }


    //     return [
    //         'pagination' => [
    //             'total'        => $personas->total(),
    //             'current_page' => $personas->currentPage(),
    //             'per_page'     => $personas->perPage(),
    //             'last_page'    => $personas->lastPage(),
    //             'from'         => $personas->firstItem(),
    //             'to'           => $personas->lastItem(),
    //         ],
    //         'personas' => $personas
    //     ];
    // }

    public function importarCliente(Request $request)
    {
        $path = $request->file('select_users_file')->getRealPath();
        Excel::import(new ClienteImport, $path);
    }

    public function verificarExistencia(Request $request)
    {
        //$venta = Venta::findOrFail($request->documento);
        //$documento = $venta->cliente->num_documento;
        $documento = $request->documento;
        $cliente = Persona::where('num_documento', $documento)->first();

        if ($cliente) {
            return response()->json(['existe' => true, 'cliente' => $cliente]);
        } else {
            return response()->json(['existe' => false]);
        }
    }

    public function verificarExistencia2(Request $request)
    {
        $venta = Venta::findOrFail($request->documento);
        $documento = $venta->cliente->num_documento;
        $cliente = Persona::where('num_documento', $documento)->first();

        if ($cliente) {
            return response()->json(['existe' => true, 'cliente' => $cliente]);
        } else {
            return response()->json(['existe' => false]);
        }
    }

    public function buscarPorDocumento(Request $request)
    {
        $busqueda = $request->query('documento');

        if (!$busqueda || trim($busqueda) === '') {
            return response()->json([], 400);
        }

        // IDs a excluir (usuarios y proveedores)
        $idsExcluidos = DB::table('users')->select('id')
            ->union(DB::table('proveedores')->select('id'));

        $palabras = preg_split('/\s+/', trim($busqueda));

        $clientes = Persona::whereNotIn('id', $idsExcluidos)
            ->where(function ($query) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $query->where(function ($subquery) use ($palabra) {
                        $subquery->where('nombre', 'LIKE', "%{$palabra}%")
                            ->orWhere('num_documento', 'LIKE', "%{$palabra}%")
                            ->orWhere('telefono', 'LIKE', "%{$palabra}%")
                            ->orWhere('email', 'LIKE', "%{$palabra}%");
                    });
                }
            })
            ->limit(10)
            ->get();

        if ($clientes->isEmpty()) {
            return response()->json([], 404);
        }

        // 🔹 Obtener el monto actual de bonificación
        $montoBonificacion = DB::table('montobonificacion')
            ->orderBy('fecha_actualizacion', 'desc')
            ->first();

        $montoMinimo = $montoBonificacion ? (float) $montoBonificacion->monto : 0;
        $esAcumulativo = $montoBonificacion ? (bool) $montoBonificacion->es_acumulativo : false;
        $fechaInicio = $montoBonificacion ? $montoBonificacion->fecha_inicio : null;
        $periodoMeses = $montoBonificacion ? (int) $montoBonificacion->periodo_meses : 1;

        $clientesConMontos = $clientes->map(function ($cliente) use ($montoMinimo, $esAcumulativo, $fechaInicio, $periodoMeses) {

            if (!$esAcumulativo || !$fechaInicio) {
                // ❌ No acumulativo: todas las ventas activas
                $totalCompras = DB::table('ventas')
                    ->where('idcliente', $cliente->id)
                    ->where('estado', 1)
                    ->sum('total');
            } else {
                // ✅ Acumulativo: calcular solo el periodo actual
                $fechaActual = now();
                $inicio = \Carbon\Carbon::parse($fechaInicio);

                // Calcular cuántos periodos de meses han pasado desde fecha_inicio
                $diffMeses = $inicio->diffInMonths($fechaActual);
                $periodoActual = intdiv($diffMeses, $periodoMeses); // número de periodos completos
                $periodoInicio = $inicio->copy()->addMonths($periodoActual * $periodoMeses);
                $periodoFin = $periodoInicio->copy()->addMonths($periodoMeses)->subDay(); // último día del periodo

                // Sumar solo ventas dentro del periodo actual
                $totalCompras = DB::table('ventas')
                    ->where('idcliente', $cliente->id)
                    ->where('estado', 1)
                    ->whereBetween('fecha_hora', [$periodoInicio->format('Y-m-d'), $periodoFin->format('Y-m-d')])
                    ->sum('total');
            }

            $cliente->total_compras = $totalCompras;
            $cliente->bonificacion_habilitada = $totalCompras >= $montoMinimo;
            $cliente->monto_requerido = $montoMinimo;
            $cliente->es_acumulativo = $esAcumulativo;
            $cliente->saldo_favor = $cliente->saldo_favor ?? 0;

            return $cliente;
        });

        return response()->json($clientesConMontos);
    }

    public function importar(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|mimes:xlsx,xls',
            ]);

            $archivo = $request->file('archivo');

            $import = new ClienteImport();
            Excel::import($import, $archivo);

            $errors = $import->getErrors();

            if (!empty($errors)) {
                return response()->json(['errors' => $errors], 422);
            } else {
                return response()->json(['mensaje' => 'Importación exitosa'], 200);
            }
        } catch (Exception $e) {
            Log::error('Error en la importación: ' . $e->getMessage());

            return response()->json(['error' => 'Error en la importación', 'mensaje' => $e->getMessage()], 500);
        }
    }
    public function buscarDireccion(Request $request)
    {
        $filtro = $request->filtro;

        if (!$filtro) {
            return ['direcciones' => []];
        }

        // Buscar direcciones únicas que contengan el texto ingresado
        $direcciones = Persona::where('direccion', 'like', "%$filtro%")
            ->pluck('direccion')
            ->unique()
            ->values();

        return ['direcciones' => $direcciones];
    }
    public function listarUbicaciones()
    {
        $ubicaciones = Persona::select('direccion')
            ->whereNotNull('direccion')
            ->where('direccion', '!=', '')
            ->groupBy('direccion')
            ->orderBy('direccion')
            ->get()
            ->pluck('direccion');

        return response()->json(['ubicaciones' => $ubicaciones]);
    }



}
