<?php

namespace App\Http\Controllers\Moviapi;

use App\Http\Controllers\Controller;
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

        $usuario = Usuario::where('nombre', $request->nombre)
            ->where('tipo', 'C')
            ->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Conductor no encontrado'
            ], 404);
        }

        if (!Hash::check($request->clave, $usuario->clave)) {
            return response()->json([
                'success' => false,
                'message' => 'Clave incorrecta'
            ], 401);
        }

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

            // Obtener detalles
            $detalles = DB::table('detalleRuta')
                ->where('ruta_codruta', $codRuta)
                ->get()
                ->map(function ($d) {
                    return [
                        "coddetalle" => $d->coddetalle ?? null,
                        "ruta_codruta" => $d->ruta_codruta ?? null,
                        "segmento_codsegmento" => $d->segmento_codsegmento ?? null,
                    ];
                })
                ->toArray();

            // Evitar segmentos repetidos
            $segmentosIds = collect($detalles)
                ->pluck('segmento_codsegmento')
                ->filter() // eliminar nulos
                ->unique();

            $segmentos = DB::table('segmento')
                ->whereIn('codsegmento', $segmentosIds)
                ->get()
                ->map(function ($segmento) {
                    $cordenadasLimpias = isset($segmento->cordenadas)
                        ? str_replace('\\', '\\\\', preg_replace('/[^\x20-\x7E]/', '', $segmento->cordenadas))
                        : "";
                    return [
                        "codsegmento" => $segmento->codsegmento ?? null,
                        "nombre" => $segmento->nombre ?? "",
                        "cordenadas" => $cordenadasLimpias
                    ];
                })
                ->toArray();

            // Formatear fechas ISO 8601
            $formatearFechaIso = function ($fecha) {
                return \Carbon\Carbon::parse($fecha)->format('Y-m-d\TH:i:s.u\Z');
            };

            $respuesta[] = [
                "asignacion" => [
                    "codasignacion" => $asignacion->codasignacion,
                    "ruta_codruta" => $asignacion->ruta_codruta,
                    "usuario_codusuario" => $asignacion->usuario_codusuario,
                    "ultimaActualizacion" => $formatearFechaIso($asignacion->ultimaActualizacion)
                ],
                "ruta" => [
                    "codruta" => $asignacion->codruta,
                    "nombre" => $asignacion->nombre,
                    "limiteGeneral" => $asignacion->limiteGeneral,
                    "fechaCreacion" => $formatearFechaIso($asignacion->fechaCreacion),
                    "icono" => $asignacion->icono,
                    "tipo" => $asignacion->tipo
                ],
                "detalles" => $detalles,
                "segmentos" => $segmentos
            ];
        }

        return response()->json([
            'success' => true,
            'rutas' => $respuesta,
            'current_page' => $asignacionesQuery->currentPage(),
            'last_page' => $asignacionesQuery->lastPage()
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
