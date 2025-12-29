<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\User;
use App\Persona;
use App\Sucursales;
use App\Exports\UserExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;

        $usuario = \Auth::user();
        $idrol = $usuario->idrol;
        $idsucursal = $usuario->idsucursal;

        $query = User::join('personas', 'users.id', '=', 'personas.id')
            ->join('roles', 'users.idrol', '=', 'roles.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
                        ->join('punto_ventas', 'users.idpuntoventa', '=', 'punto_ventas.id')
            ->select(
                'personas.id',
                'personas.nombre',
                'personas.tipo_documento',
                'personas.num_documento',
                'personas.direccion',
                'personas.telefono',
                'personas.email',
                'personas.fotografia',
                'users.usuario',
                'users.password',
                'users.condicion',
                'users.idrol',
                'roles.nombre as rol',
                'users.idsucursal',
                'users.idpuntoventa',
                'sucursales.nombre as sucursal',
                                'punto_ventas.nombre as puntoventa'
            );

        // Si el usuario es rol 1 (administrador de sucursal), filtra por sucursal y excluye rol 4
        /*if ($idrol == 1) {
            $query->where('users.idsucursal', $idsucursal)
                ->where('users.idrol', '<>', 4);
        }*/

        // Búsqueda global en varias columnas
        if (!empty($buscar)) {
            $query->where(function($q) use ($buscar) {
                $q->where('personas.nombre', 'like', "%$buscar%")
                  ->orWhere('personas.tipo_documento', 'like', "%$buscar%")
                  ->orWhere('personas.num_documento', 'like', "%$buscar%")
                  ->orWhere('personas.direccion', 'like', "%$buscar%")
                  ->orWhere('personas.telefono', 'like', "%$buscar%")
                  ->orWhere('personas.email', 'like', "%$buscar%")
                  ->orWhere('users.usuario', 'like', "%$buscar%")
                  ->orWhere('roles.nombre', 'like', "%$buscar%")
                  ->orWhere('sucursales.nombre', 'like', "%$buscar%")
                ;
            });
        }

        $personas = $query->orderBy('personas.id', 'desc')->get();

        return [
            'personas' => $personas
        ];
    }


    public function store(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        try {
            DB::beginTransaction();

            $persona = new Persona();
            $persona->nombre = $request->nombre;
            $persona->usuario = Auth::user()->id;
            $persona->tipo_documento = $request->tipo_documento;
            $persona->num_documento = $request->num_documento;
            $persona->direccion = $request->direccion;
            $persona->telefono = $request->telefono;
            $persona->email = $request->email;

            if ($request->hasFile('fotografia')) {
                if ($request->hasFile('fotografia')) {
                    $imagen = $request->file("fotografia");
                    $nombreimagen = Str::slug($request->nombre) . "." . $imagen->guessExtension();
                    $ruta = public_path("img/usuarios/");

                    // Crear el directorio si no existe
                    if (!File::isDirectory($ruta)) {
                        File::makeDirectory($ruta, 0755, true);
                    }

                    // Copiar la imagen al directorio
                    copy($imagen->getRealPath(), $ruta . $nombreimagen);

                    $persona->fotografia = $nombreimagen;
                }
            }

            $persona->save();

            $user = new User();
            $user->id = $persona->id;
            $user->idrol = $request->idrol;
            $user->idsucursal = $request->idsucursal;
            $user->usuario = $request->usuario;
            $user->password = bcrypt($request->password);
            $user->condicion = '1';
            $user->idpuntoventa = $request->idpuntoventa;
            $user->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }
    }

    public function update(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->id);
            $persona = Persona::findOrFail($user->id);
            $persona->nombre = $request->nombre;
            $persona->tipo_documento = $request->tipo_documento;
            $persona->num_documento = $request->num_documento;
            $persona->direccion = $request->direccion;
            $persona->telefono = $request->telefono;
            $persona->email = $request->email;


            $nombreimagen = $persona->fotografia;
            if ($request->hasFile('fotografia')) {
                // Eliminar imagen anterior si existe
                if ($persona->fotografia != '' && File::exists(public_path('img/usuarios/' . $persona->fotografia))) {
                    File::delete(public_path('img/usuarios/' . $persona->fotografia));
                }

                $imagen = $request->file("fotografia");
                $nombreimagen = Str::slug($request->nombre) . "." . $imagen->guessExtension();
                $ruta = public_path("img/usuarios/");

                // Crear el directorio si no existe
                if (!File::isDirectory($ruta)) {
                    File::makeDirectory($ruta, 0755, true);
                }

                // Copiar la imagen al directorio
                copy($imagen->getRealPath(), $ruta . $nombreimagen);

                $persona->fotografia = $nombreimagen;
            }

            Log::info('Datos actualizados', [
                'nombre' => $request->nombre,
                'tipo_documento' => $request->tipo_documento,
                'num_documento' => $request->num_documento,
                'direccion' => $request->direccion,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'fotografia' => $nombreimagen,
                'usuario' => $request->usuario,
                'password' => $request->password,
                'idrol' => $request->idrol,
                'id' => $request->id

            ]);


            $persona->save();

            $user->usuario = $request->usuario;
            $user->password = bcrypt($request->password);
            $user->condicion = '1';

            if ($request->idrol != '') {
                $user->idrol = $request->idrol;
            }

            if ($request->idsucursal != '') {
                $user->idsucursal = $request->idsucursal;
            }

            if ($request->idpuntoventa != '') {
                $user->idpuntoventa = $request->idpuntoventa;
            }

            $user->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }
    }

    public function desactivar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $user = User::findOrFail($request->id);
        $user->condicion = '0';
        $user->save();
    }

    public function activar(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $user = User::findOrFail($request->id);
        $user->condicion = '1';
        $user->save();
    }

    public function listarReporteUsuariosExcel()
    {
        return Excel::download(new UserExport, 'usuarios.xlsx');
    }

    public function editarPersona(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $persona = User::join('personas', 'users.id', '=', 'personas.id')
            ->join('roles', 'users.idrol', '=', 'roles.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->select('personas.id', 'personas.nombre', 'personas.tipo_documento', 'personas.num_documento', 'personas.direccion', 'personas.telefono', 'personas.email', 'personas.fotografia', 'users.usuario', 'users.password', 'users.condicion', 'users.idrol', 'roles.nombre as rol', 'users.idsucursal', 'sucursales.nombre as sucursal')
            ->where('personas.id', $request->id);

        return ['persona' => $persona->first()];
    }
    public function selectUsuarios(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->filtro;

        $usuarios = User::join('personas', 'users.id', '=', 'personas.id')
            ->where('personas.nombre', 'like', '%' . $filtro . '%')
            ->select('users.id', 'personas.nombre as nombre')
            ->orderBy('personas.nombre', 'asc')
            ->get();

        return ['usuarios' => $usuarios];
    }

    public function selectUsuariosPorRol(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->filtro;

        $usuarios = User::join('personas', 'users.id', '=', 'personas.id')
            ->where('users.idrol', '=', $filtro)
            ->select('personas.nombre as nombre', 'personas.id', 'users.condicion')
            ->orderBy('personas.nombre', 'asc')
            ->get();

        return ['usuarios' => $usuarios];
    }
    public function getUsuarioAutenticado(Request $request)
    {
        $usuario = Auth::user();
        $sucursal = Sucursales::find($usuario->idsucursal); // Cambia Sucursal por Sucursales
        if (!$sucursal) {
            return response()->json(['error' => 'Sucursal no encontrada'], 404);
        }
        return response()->json([
            'idsucursal' => $sucursal->id,
            'sucursal_nombre' => $sucursal->nombre
        ]);
    }
}
