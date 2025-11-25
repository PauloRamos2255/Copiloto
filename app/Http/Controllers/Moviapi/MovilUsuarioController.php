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
    $actualizacionActiva = Actualizacion::where('estado', 'A')
        ->where('usuario_codusuario', $usuario->codusuario)
        ->first();

    if ($actualizacionActiva) {
        return response()->json([
            'success' => false,
            'message' => 'No se puede iniciar sesión: existe una actualización activa para este usuario'
        ], 403);
    }

    // Registrar inicio de actualización con estado A
    Actualizacion::create([
        'usuario_codusuario' => $usuario->codusuario,
        'estado' => 'A',
        'inicio' => now()
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
                        "coddetalle" => (int) ($d->coddetalle ?? 0),
                        "ruta_codruta" => (int) ($d->ruta_codruta ?? 0),
                        "segmento_codsegmento" => (int) ($d->segmento_codsegmento ?? 0),
                        "velocidadPermitida" => (int) ($d->velocidadPermitida ?? 0),
                        "mensaje" => $d->mensaje ?? ""
                    ];
                })
                ->toArray();

            $segmentosIds = collect($detalles)
                ->pluck('segmento_codsegmento')
                ->filter()
                ->unique()
                ->values();

            $segmentos = DB::table('segmento')
                ->whereIn('codsegmento', $segmentosIds)
                ->get()
                ->map(function ($segmento) {

                    $cord = $segmento->cordenadas ?? "";

                    $cord = preg_replace('/[^\x20-\x7E]/', '', $cord);

                    $cord = str_replace('\\', '\\\\', $cord);

                    return [
                        "codsegmento" => (int) $segmento->codsegmento,
                        "nombre" => $segmento->nombre ?? "",
                        "color" => $segmento->color ?? "",
                        "cordenadas" => $cord,
                        "bounds" => $segmento->bounds ?? ""
                    ];
                })
                ->toArray();

            $formatearFechaIso = function ($fecha) {
                return \Carbon\Carbon::parse($fecha)->toISOString(); // ISO 8601 válido
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
                "detalles" => $detalles,
                "segmentos" => $segmentos
            ];
        }

        // ==========================
        // 5) RESPUESTA FINAL
        // ==========================
        return response()->json([
            'success' => true,
            'rutas' => $respuesta,
            'current_page' => $asignacionesQuery->currentPage(),
            'last_page' => $asignacionesQuery->lastPage()
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
