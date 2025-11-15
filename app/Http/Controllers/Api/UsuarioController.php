<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
   
     public function index()
    {
        $usuarios = Usuario::all(); // Trae todos los usuarios
        return response()->json($usuarios);
    }

    // GET /api/usuarios/{id}
    public function show($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($usuario);
    }

    // POST /api/usuarios
    public function store(Request $request)
{
    $request->validate([
        'empresa_codempresa' => 'required|integer',
        'nombre'             => 'required|string|max:100',
        'clave'              => 'required|string|max:500',
        'tipo'               => 'required|string|max:1',
        'identificador'      => 'required|string|max:30'
    ]);

    $usuario = DB::transaction(function () use ($request) {
        return Usuario::create([
            'empresa_codempresa' => $request->empresa_codempresa,
            'nombre'             => $request->nombre,
            'clave'              => bcrypt($request->clave),
            'tipo'               => $request->tipo,
            'identificador'      => $request->identificador
        ]);
    });

    return response()->json([
        'ok' => true,
        'msg' => 'Usuario creado correctamente',
        'usuario' => [
            'id'              => $usuario->codusuario,
            'empresa'         => $usuario->empresa_codempresa,
            'nombre'          => $usuario->nombre,
            'identificador'   => $usuario->identificador,
            'tipo'            => $usuario->tipo
        ]
    ]);
}


    // PUT /api/usuarios/{id}
    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $request->validate([
            'empresa_codempresa' => 'sometimes|required|integer',
            'nombre' => 'sometimes|required|string|max:100',
            'clave' => 'sometimes|nullable|string|min:6',
            'tipo' => ['sometimes','required', Rule::in(['A','U','C'])],
            'identificador' => 'nullable|string|max:30',
        ]);

        if ($request->has('clave') && $request->clave) {
            $usuario->clave = Hash::make($request->clave);
        }

        $usuario->empresa_codempresa = $request->empresa_codempresa ?? $usuario->empresa_codempresa;
        $usuario->nombre = $request->nombre ?? $usuario->nombre;
        $usuario->tipo = $request->tipo ?? $usuario->tipo;
        $usuario->identificador = $request->identificador ?? $usuario->identificador;
        $usuario->ultimoIngreso = $request->ultimoIngreso ?? $usuario->ultimoIngreso;

        $usuario->save();

        return response()->json($usuario);
    }

    // DELETE /api/usuarios/{id}
    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }



    public function login(Request $request)
    {
        $inicio = microtime(true);

        $request->validate([
            'nombre' => 'required|string',
            'clave'  => 'required|string'
        ]);

        $usuario = Usuario::where('nombre', $request->nombre)->first();

        if (!$usuario) {
            $fin = microtime(true);
            Log::info('Login fallido: usuario no encontrado. Tiempo: ' . ($fin - $inicio));
            return redirect()->route('login')->with('error', 'Usuario no encontrado');
        }

        if (!Hash::check($request->clave, $usuario->clave)) {
            $fin = microtime(true);
            Log::info('Login fallido: clave incorrecta. Tiempo: ' . ($fin - $inicio));
            return redirect()->route('login')->with('error', 'Clave incorrecta');
        }

        // Validación del tipo REAL desde la BD
        if (!in_array($usuario->tipo, ['A', 'U'])) {
            $fin = microtime(true);
            Log::info('Login fallido: tipo no permitido. Tiempo: ' . ($fin - $inicio));
            return redirect()->route('login')->with('error', 'Tipo de usuario inválido');
        }

        // Guardar último ingreso
        $usuario->ultimoIngreso = now('America/Lima');
        $usuario->save();

        Auth::login($usuario);

        $fin = microtime(true);
        Log::info('Login exitoso. Tiempo: ' . ($fin - $inicio));

        return Inertia::location('/rutas');
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
