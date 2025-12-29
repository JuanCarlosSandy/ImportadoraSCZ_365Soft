<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;

class LoginController extends Controller
{
    public function showLoginForm(){
        return view('auth.login');
    }

      public function login(Request $request)
    {
        $this->validateLogin($request);        

        // Buscar si el usuario existe
        $user = User::where('usuario', $request->usuario)->first();

        if (!$user) {
            return back()
                ->withErrors(['usuario' => 'Usuario no encontrado.'])
                ->withInput($request->only('usuario'));
        }

        // Verificar si el usuario está activo
        if ($user->condicion != 1) {
            return back()
                ->withErrors(['usuario' => 'Usuario deshabilitado.'])
                ->withInput($request->only('usuario'));
        }

        // Verificar si la contraseña es correcta
        if (!Auth::attempt(['usuario' => $request->usuario, 'password' => $request->password, 'condicion' => 1])) {
            return back()
                ->withErrors(['password' => 'Contraseña incorrecta.'])
                ->withInput($request->only('usuario'));
        }

        // Usuario autenticado correctamente
        $persona = $user->persona()->get();
        $request->session()->put('fotografia', ($persona[0]->fotografia != '') ? $persona[0]->fotografia : 'defecto.jpg');
        $request->session()->put('id', ($persona[0]->id));

        return redirect()->route('main');
    }

    protected function validateLogin(Request $request){
        $this->validate($request,[
            'usuario' => 'required|string',
            'password' => 'required|string'
        ]);

    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        return redirect('/');
    }
}
