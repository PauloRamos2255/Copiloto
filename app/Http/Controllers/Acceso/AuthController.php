<?php

namespace App\Http\Controllers\Acceso;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function acceso(Request $request)
{
    $request->validate([
        'nombre' => 'required|string',
        'clave'  => 'required|string',
    ]);

    $user = Usuario::where('nombre', $request->nombre)->first();

    if (!$user || !Hash::check($request->clave, $user->clave)) {
        return back()->withErrors(['nombre' => 'Credenciales incorrectas']);
    }

    Auth::login($user);

    if (isset($user->ultimoIngreso)) {
        $user->ultimoIngreso = now();
        $user->save();
    }

    return redirect()->route('mapa');
}

public function logout(Request $request)
    {
        Auth::logout();

        // Invalidar sesión y regenerar token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirigir al login
        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente ✅');
    }

}


