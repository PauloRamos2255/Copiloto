<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
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
        $usuarios = Usuario::with('empresa')->get();

        return $usuarios->map(function ($u) {
            return [
                'codusuario' => $u->codusuario,
                'nombre' => $u->nombre,
                'tipo' => $u->tipo,
                'empresa_codempresa' => $u->empresa_codempresa,
                'empresa_nombre' => $u->empresa->nombre ?? 'Sin empresa',
            ];
        });
    }


    public function show($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($usuario);
    }

    public function listarTipoC()
    {
        $usuarios = Usuario::where('tipo', 'C')->get();
        return response()->json($usuarios);
    }

    public function verificarUsuario($id)
    {
        $existe = Asignacion::where('usuario_codusuario', $id)->exists();

        if ($existe) {
            return response()->json([
                'success' => true,
                'mensaje' => 'El usuario está asignado y no puede eliminarse'
            ]);
        }

        return response()->json([
            'success' => false,
            'mensaje' => 'El usuario se puede eliminar'
        ]);
    }



    public function store(Request $request)
    {
        try {
            // Log completo para debug
            Log::info('Request completo:', [
                'all' => $request->all(),
                'input' => $request->input(),
                'json' => $request->json()->all(),
                'content' => $request->getContent(),
                'headers' => $request->headers->all()
            ]);


            $validated = $request->validate([
                'empresa_codempresa' => 'required|integer',
                'nombre'             => 'required|string|max:100',
                'clave'              => 'required|string|min:6',
                'tipo'               => 'required|string|in:A,U,C',
                'identificador'      => 'nullable|string|max:30'
            ]);

            Log::info('Datos validados:', $validated);

            // Generar identificador si no se proporciona
            $identificador = $validated['identificador'] ?? substr(bin2hex(random_bytes(15)), 0, 30);

            $usuario = Usuario::create([
                'empresa_codempresa' => $validated['empresa_codempresa'],
                'nombre'             => $validated['nombre'],
                'clave'              => Hash::make($validated['clave']),
                'tipo'               => $validated['tipo'],
                'identificador'      => $identificador
            ]);

            Log::info('Usuario creado exitosamente:', ['id' => $usuario->codusuario]);

            return response()->json([
                'ok' => true,
                'msg' => 'Usuario creado correctamente',
                'usuario' => [
                    'id'            => $usuario->codusuario,
                    'empresa'       => $usuario->empresa_codempresa,
                    'nombre'        => $usuario->nombre,
                    'identificador' => $usuario->identificador,
                    'tipo'          => $usuario->tipo
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación:', $e->errors());
            return response()->json([
                'ok' => false,
                'msg' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear usuario:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'ok' => false,
                'msg' => 'Error al crear usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $request->validate([
            'empresa_codempresa' => 'sometimes|required|integer',

            // ✓ Validación correcta del nombre (tabla usuario, columna nombre)
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('usuario', 'nombre')->ignore($id, 'codusuario')
            ],

            'clave' => 'sometimes|nullable|string|min:6',

            'tipo' => [
                'sometimes',
                'required',
                Rule::in(['A', 'U', 'C'])
            ],

            // ✓ Validación correcta del identificador
            'identificador' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('usuario', 'identificador')->ignore($id, 'codusuario')
            ],
        ]);

        // Actualizar solo los campos proporcionados
        if ($request->has('empresa_codempresa')) {
            $usuario->empresa_codempresa = $request->empresa_codempresa;
        }

        if ($request->has('nombre')) {
            $usuario->nombre = $request->nombre;
        }

        if ($request->has('clave') && !empty($request->clave)) {
            $usuario->clave = Hash::make($request->clave);
        }

        if ($request->has('tipo')) {
            $usuario->tipo = $request->tipo;
        }

        if ($request->has('identificador')) {
            $usuario->identificador = $request->identificador;
        }

        if ($request->has('ultimoIngreso')) {
            $usuario->ultimoIngreso = $request->ultimoIngreso;
        }

        $usuario->save();

        return response()->json([
            'ok' => true,
            'msg' => 'Usuario actualizado correctamente',
            'usuario' => $usuario
        ]);
    }


    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
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
            return redirect()->route('login')->withErrors(['error' => 'Usuario no encontrado']);
        }

        if (!Hash::check($request->clave, $usuario->clave)) {
            $fin = microtime(true);
            Log::info('Login fallido: clave incorrecta. Tiempo: ' . ($fin - $inicio));
            return redirect()->route('login')->withErrors(['error' => 'Clave incorrecta']);
        }

        // Validación del tipo de usuario
        if (!in_array($usuario->tipo, ['A', 'U'])) {
            $fin = microtime(true);
            Log::info('Login fallido: tipo no permitido. Tiempo: ' . ($fin - $inicio));
            return redirect()->route('login')->withErrors(['error' => 'Tipo de usuario inválido']);
        }

        // Guardar último ingreso
        $usuario->ultimoIngreso = now('America/Lima');
        $usuario->save();

        Auth::login($usuario);
        $request->session()->regenerate(); // Regenera la sesión

        Log::info('Después de Auth::login():', [
            'user_id' => Auth::id(),
            'authenticated' => Auth::check(),
            'user' => Auth::user(),
        ]);

        $fin = microtime(true);
        Log::info('Login exitoso para usuario: ' . $usuario->nombre . '. Tiempo: ' . ($fin - $inicio));

        return redirect()->intended('/rutas'); // Cambia esto
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
