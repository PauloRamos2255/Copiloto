<?php

namespace App\Http\Controllers\Moviapi;

use App\Http\Controllers\Controller;
use App\Models\Actualizacion;
use App\Models\Ruta;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MovilUsuarioController extends Controller
{
    public function loginConductor(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'clave' => 'required'
        ]);

        // Buscar usuario tipo conductor
        $usuario = Usuario::where('nombre', $request->nombre)
            ->where('tipo', 'C')
            ->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Conductor no encontrado'
            ], 404);
        }

        // Verificar contraseña primero
        if (!Hash::check($request->clave, $usuario->clave)) {
            return response()->json([
                'success' => false,
                'message' => 'Clave incorrecta'
            ], 401);
        }

        // Verificar si existe una actualización activa para este usuario
        $actualizacionActiva = Actualizacion::where('estado', 'I')
            ->where('usuario_codusuario', $usuario->codusuario)
            ->first();

        if ($actualizacionActiva) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede iniciar sesión: existe una actualización activa para este usuario'
            ], 403);
        }

        Actualizacion::create([
            'usuario_codusuario' => $usuario->codusuario,
            'estado' => 'I',
            'inicio' =>  now('America/Lima')
        ]);

        // Actualizar último ingreso
        $usuario->ultimoIngreso = now();
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Conductor autenticado correctamente',
            'usuario' => [
                'codusuario' => $usuario->codusuario,
                'nombre' => $usuario->nombre,
                'tipo' => $usuario->tipo,
                'ultimoIngreso' => $usuario->ultimoIngreso,
                'identificador' => $usuario->identificador,
                'empresa_codempresa' => $usuario->empresa_codempresa
            ]
        ]);
    }

    public function obtenerRutasConductor($codusuario)
    {
        $asignacionesQuery = DB::table('asignacion as a')
            ->join('ruta as r', 'a.ruta_codruta', '=', 'r.codruta')
            ->where('a.usuario_codusuario', $codusuario)
            ->select(
                'a.codasignacion',
                'a.ruta_codruta',
                'a.usuario_codusuario',
                'a.ultimaActualizacion',
                'r.codruta',
                'r.nombre',
                'r.limiteGeneral',
                'r.fechaCreacion',
                'r.icono',
                'r.tipo'
            )
            ->paginate(50);

        $respuesta = [];

        foreach ($asignacionesQuery->items() as $asignacion) {
            $codRuta = $asignacion->codruta;

            $detalles = DB::table('detalleRuta')
                ->where('ruta_codruta', $codRuta)
                ->get()
                ->map(function ($d) {
                    return [
                        "coddetalle" => (int) ($d->iddetalleRuta ?? 0),
                        "ruta_codruta" => (int) ($d->ruta_codruta ?? 0),
                        "segmento_codsegmento" => (int) ($d->segmento_codsegmento ?? 0),
                        "velocidadPermitida" => (int) ($d->velocidadPermitida ?? 0),
                        "mensaje" => $d->mensaje ?? ""
                    ];
                })
                ->toArray();

            $formatearFechaIso = function ($fecha) {
                return \Carbon\Carbon::parse($fecha)->toISOString();
            };

            $respuesta[] = [
                "asignacion" => [
                    "codasignacion" => (int) $asignacion->codasignacion,
                    "ruta_codruta" => (int) $asignacion->ruta_codruta,
                    "usuario_codusuario" => (int) $asignacion->usuario_codusuario,
                    "ultimaActualizacion" => $formatearFechaIso($asignacion->ultimaActualizacion)
                ],
                "ruta" => [
                    "codruta" => (int) $asignacion->codruta,
                    "nombre" => $asignacion->nombre,
                    "limiteGeneral" => (int) $asignacion->limiteGeneral,
                    "fechaCreacion" => $formatearFechaIso($asignacion->fechaCreacion),
                    "icono" => $asignacion->icono,
                    "tipo" => $asignacion->tipo
                ],
                "detalles" => $detalles
            ];
        }

        return response()->json([
            'success' => true,
            'rutas' => $respuesta,
            'current_page' => $asignacionesQuery->currentPage(),
            'last_page' => $asignacionesQuery->lastPage()
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    public function logoutConductor($codusuario)
    {
        // Validar que sea un número entero
        if (!is_numeric($codusuario)) {
            return response()->json([
                'success' => false,
                'message' => 'El ID debe ser un número válido'
            ], 400);
        }

        // Buscar usuario
        $usuario = Usuario::find($codusuario);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Buscar actualización activa (estado I = iniciado)
        $actualizacion = Actualizacion::where('usuario_codusuario', $codusuario)
            ->where('estado', 'I')
            ->first();

        if (!$actualizacion) {
            return response()->json([
                'success' => false,
                'message' => 'No existe una sesión activa para este usuario'
            ], 400);
        }

        // Cerrar sesión
        $actualizacion->estado = 'C';
        $actualizacion->fin = now('America/Lima');
        $actualizacion->save();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}
