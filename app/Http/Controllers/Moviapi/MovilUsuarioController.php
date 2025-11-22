<?php

namespace App\Http\Controllers\Moviapi;

use App\Http\Controllers\Controller;
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
        // 1. Obtener rutas asignadas (incluyendo los datos de asignación)
        $asignaciones = DB::table('asignacion as a')
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
            ->get();

        $respuesta = [];

        foreach ($asignaciones as $asignacion) {

            $codRuta = $asignacion->codruta;

            // 2. Obtener los detalles de la ruta
            $detalles = DB::table('detalleRuta')
                ->where('ruta_codruta', $codRuta)
                ->get();

            // 3. Obtener IDs únicos de segmentos
            $segmentosIds = $detalles->pluck('segmento_codsegmento')->unique();

            // 4. Obtener segmentos completos
            $segmentos = DB::table('segmento')
                ->whereIn('codsegmento', $segmentosIds)
                ->get();

            // 5. Armar estructura final
            $respuesta[] = [
                "asignacion" => [
                    "codasignacion"       => $asignacion->codasignacion,
                    "ruta_codruta"        => $asignacion->ruta_codruta,
                    "usuario_codusuario"  => $asignacion->usuario_codusuario,
                    "ultimaActualizacion" => $asignacion->ultimaActualizacion
                ],
                "ruta" => [
                    "codruta"       => $asignacion->codruta,
                    "nombre"        => $asignacion->nombre,
                    "limiteGeneral" => $asignacion->limiteGeneral,
                    "fechaCreacion" => $asignacion->fechaCreacion,
                    "icono"         => $asignacion->icono,
                    "tipo"          => $asignacion->tipo
                ],
                "detalles" => $detalles,
                "segmentos" => $segmentos
            ];
        }

        return response()->json([
            "success" => true,
            "rutas" => $respuesta
        ]);
    }
}
