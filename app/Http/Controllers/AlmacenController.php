<?php

namespace App\Http\Controllers;

use App\Almacen;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\User;

class AlmacenController extends Controller
{
    public function listarAlmacen(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $usuario = \Auth::user(); // Obtener el usuario logueado
        $idrol = $usuario->idrol;

        $query = Almacen::where('condicion', '=', '1');

        if ($idrol != 4) {
            // Si el usuario no es de rol 4, filtrar por su sucursal
            $query->where('sucursal', '=', $usuario->idsucursal);
        }

        $almacenes = $query->select('id', 'nombre_almacen')
            ->orderBy('nombre_almacen', 'asc')
            ->get();

        return ['almacenes' => $almacenes];
    }

    public function listarAlmacenes(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $usuario = \Auth::user(); // Usuario logueado
        $idrol = $usuario->idrol;

        $query = Almacen::where('condicion', '=', '1');

        // ✅ Si no es rol 4, filtrar por la sucursal del usuario
        if ($idrol != 4) {
            $query->where('sucursal', '=', $usuario->idsucursal);
        }

        $almacenes = $query->select('id', 'nombre_almacen')
            ->orderBy('nombre_almacen', 'asc')
            ->get();

        return ['almacenes' => $almacenes];
    }

    //listas almacen
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = $request->buscar;
        $usuario = \Auth::user();
        $idrol = $usuario->idrol;
        $idsucursal = $usuario->idsucursal;

        $almacenes = Almacen::join('sucursales', 'almacens.sucursal', '=', 'sucursales.id')
            ->select('almacens.*', 'sucursales.nombre as nombre_sucursal')
            /*->when($idrol == 1, function ($query) use ($idsucursal) {
                // Si es administrador (idrol 1), filtrar por su sucursal
                return $query->where('almacens.sucursal', $idsucursal);
            })*/
            ->when($buscar, function ($query) use ($buscar) {
                return $query->where(function ($q) use ($buscar) {
                    $q->where('almacens.nombre_almacen', 'like', '%' . $buscar . '%')
                        ->orWhere('sucursales.nombre', 'like', '%' . $buscar . '%')
                        ->orWhere('almacens.ubicacion', 'like', '%' . $buscar . '%')
                        ->orWhere('almacens.telefono', 'like', '%' . $buscar . '%'); // si querés filtrar por ID de encargado
                });
            })
            ->orderBy('almacens.id', 'desc')
            ->paginate(10);

        // Agregar nombres de encargados
        foreach ($almacenes as $almacen) {
            $encargadosIds = explode(',', $almacen->encargado);
            $encargadosNombres = User::whereIn('id', $encargadosIds)->pluck('usuario')->implode(', ');
            $almacen->encargados_nombres = $encargadosNombres;
        }

        return [
            'pagination' => [
                'total' => $almacenes->total(),
                'current_page' => $almacenes->currentPage(),
                'per_page' => $almacenes->perPage(),
                'last_page' => $almacenes->lastPage(),
                'from' => $almacenes->firstItem(),
                'to' => $almacenes->lastItem(),
            ],
            'almacenes' => $almacenes
        ];
    }


    public function buscadorAlmacen(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $nombre = $request->input('buscar');
        $campos = [
            'id',
            'nombre_almacen',
            'condicion'
        ];

        $almacenes = Almacen::where('almacens.nombre_almacen', 'like', '%' . $nombre . '%')
            ->where('condicion', 1)
            ->select($campos)
            ->paginate(3);

        return [
            'pagination' => [
                'total' => $almacenes->total(),
                'current_page' => $almacenes->currentPage(),
                'per_page' => $almacenes->perPage(),
                'last_page' => $almacenes->lastPage(),
                'from' => $almacenes->firstItem(),
                'to' => $almacenes->lastItem(),
            ],
            'almacenes' => $almacenes
        ];
    }
    public function index2(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        $almacenes = Almacen::join('sucursales', 'almacens.sucursal', '=', 'sucursales.id')
            ->select('almacens.*', 'sucursales.nombre as nombre_sucursal')
            ->when($buscar, function ($query) use ($buscar, $criterio) {
                return $query->where('almacens.nombre_almacen', 'like', '%' . $buscar . '%')
                    ->orWhere('sucursales.nombre', 'like', '%' . $buscar . '%');
            })
            ->orderBy('almacens.id', 'desc');
        $almacenes = $almacenes->get();
        foreach ($almacenes as $almacen) {
            $encargadosIds = explode(',', $almacen->encargado);
            // Cambia 'name' a 'usuario' en la siguiente línea
            $encargadosNombres = User::whereIn('id', $encargadosIds)->pluck('usuario')->implode(', ');
            $almacen->encargados_nombres = $encargadosNombres;
        }

        return [
            'almacenes' => $almacenes
        ];
    }

    public function store(Request $request)
    {

        if (!$request->ajax())
            return redirect('/');
        $almacenes = new Almacen();
        $almacenes->nombre_almacen = $request->nombre_almacen;
        $almacenes->ubicacion = $request->ubicacion;
        $almacenes->encargado = $request->encargado;
        $almacenes->telefono = $request->telefono;
        $almacenes->sucursal = $request->sucursal;
        $almacenes->observacion = $request->observaciones;
        Log::info('DATOS REGISTRO ALMACEN:', [
            'nombre_almacen' => $request->nombre_almacen,
            'ubicacion' => $request->ubicacion,
            'encargado' => $request->encargado,
            'telefono' => $request->telefono,
            'sucursal' => $request->sucursal,
            'observacion' => $request->observacion,
        ]);
        $almacenes->save();
        $encargados = explode(',', $request->encargado);
        $almacenes->encargado = json_encode($encargados);
    }
    public function update(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $almacenes = Almacen::findOrFail($request->id);

        $almacenes->nombre_almacen = $request->nombre_almacen;
        $almacenes->ubicacion = $request->ubicacion;
        $almacenes->encargado = $request->encargado;
        $almacenes->telefono = $request->telefono;
        $almacenes->sucursal = $request->sucursal;
        $almacenes->observacion = $request->observaciones;
        Log::info('ACTUALIZAR ALMACEN:', [
            'nombre_almacen' => $request->nombre_almacen,
            'ubicacion' => $request->ubicacion,
            'encargado' => $request->encargado,
            'telefono' => $request->telefono,
            'sucursal' => $request->sucursal,
            'observacion' => $request->observacion,
        ]);
        $almacenes->save();
        $encargados = explode(',', $request->encargado);
        $almacenes->encargado = json_encode($encargados);
    }
  public function selectAlmacen(Request $request)
{
    if (!$request->ajax()) {
        return redirect('/');
    }

    $usuario = \Auth::user(); // Usuario logueado
    $idsucursal = $usuario->idsucursal;
    $idrol = $usuario->idrol; // Asegúrate de que el modelo User tenga esta relación o campo

    // Si el usuario tiene rol 1 o 2, solo mostrar los almacenes de su sucursal
    if (in_array($idrol, [1, 2])) {
        $almacenes = Almacen::where('condicion', '=', '1')
            ->where('sucursal', '=', $idsucursal)
            ->select('id', 'nombre_almacen', 'sucursal')
            ->orderBy('nombre_almacen', 'asc')
            ->get();
    } else {
        // Caso contrario, listar todos los almacenes priorizando la sucursal del usuario
        $almacenes = Almacen::where('condicion', '=', '1')
            ->select('id', 'nombre_almacen', 'sucursal')
            ->orderByRaw("CASE WHEN sucursal = ? THEN 0 ELSE 1 END", [$idsucursal])
            ->orderBy('nombre_almacen', 'asc')
            ->get();
    }

    return ['almacenes' => $almacenes];
}


    public function AlmacenesLista(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $usuario = \Auth::user(); // Usuario logueado
        $idrol = $usuario->idrol;

        $query = Almacen::where('condicion', '=', '1');

        // Si no es rol 4, filtrar por sucursal del usuario
        if ($idrol != 4) {
            $query->where('sucursal', '=', $usuario->idsucursal);
        }

        $almacenes = $query->select('id', 'nombre_almacen')->orderBy('nombre_almacen', 'asc')->get();

        return ['almacenes' => $almacenes, 'idrol' => $idrol]; // incluye el rol también
    }

    public function selectAlmacenDestino(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $almacenes = Almacen::where('condicion', '=', '1')
            ->select('id', 'nombre_almacen')->orderBy('nombre_almacen', 'asc')->get();
        return ['almacenes' => $almacenes];
    }
}