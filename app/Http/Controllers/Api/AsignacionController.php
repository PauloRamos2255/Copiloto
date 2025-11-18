<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Ruta;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AsignacionController extends Controller
{
    public function index()
    {
        $usuarios = DB::table('usuario AS u')
            ->leftJoin('asignacion AS a', 'a.usuario_codusuario', '=', 'u.codusuario')
            ->leftJoin('historicoViaje AS h', 'h.asignacion_codAsignacion', '=', 'a.codasignacion')
            ->select(
                'u.codusuario',
                'u.nombre',
                'u.identificador',
                DB::raw('COUNT(DISTINCT a.codasignacion) AS total_rutas'),
                DB::raw('COUNT(DISTINCT h.codhistorioViaje) AS total_viajes')
            )
            ->where('u.tipo', 'C')
            ->groupBy('u.codusuario', 'u.nombre', 'u.identificador')
            ->get();

        return response()->json($usuarios);
    }




    public function obtenerTodasLasRutas()
    {
        $rows = DB::table('ruta as r')
            ->join('detalleRuta as d', 'd.ruta_codruta', '=', 'r.codruta')
            ->join('segmento as s', 's.codsegmento', '=', 'd.segmento_codsegmento')
            ->select(
                'r.codruta as idRuta',
                'r.nombre as nombreRuta',
                's.nombre',
                's.color',
                's.codsegmento as idSegmento',
                's.cordenadas',
                's.bounds'
            )
            ->orderBy('r.codruta')
            ->get();

        // Agrupar rutas manualmente (rápido)
        $rutas = [];

        foreach ($rows as $row) {
            if (!isset($rutas[$row->idRuta])) {
                $rutas[$row->idRuta] = [
                    'id' => $row->idRuta,
                    'nombre' => $row->nombreRuta,
                    'segmentos' => []
                ];
            }

            $rutas[$row->idRuta]['segmentos'][] = [
                'id' => $row->idSegmento,
                'nombre' => $row->nombre,
                'color' => $row->color,
                'cordenadas' => $row->cordenadas, // JSON crudo
                'bounds' => $row->bounds // JSON crudo
            ];
        }

        return response()->json([
            'status' => true,
            'rutas' => array_values($rutas)
        ]);
    }


    public function obtenerRutasPorUsuario($idUsuario)
    {
        $rows = DB::table('ruta as r')
            ->join('detalleRuta as d', 'd.ruta_codruta', '=', 'r.codruta')
            ->join('segmento as s', 's.codsegmento', '=', 'd.segmento_codsegmento')
            ->join('asignacion as a', 'a.ruta_codruta', '=', 'r.codruta')
            ->where('a.usuario_codusuario', $idUsuario)
            ->select(
                'r.codruta as idRuta',
                'r.nombre as nombreRuta',
                's.nombre',
                's.color',
                's.codsegmento as idSegmento',
                's.cordenadas',
                's.bounds'
            )
            ->orderBy('r.codruta')
            ->get();

        $rutas = [];

        foreach ($rows as $row) {
            if (!isset($rutas[$row->idRuta])) {
                $rutas[$row->idRuta] = [
                    'id' => $row->idRuta,
                    'nombre' => $row->nombreRuta,
                    'segmentos' => []
                ];
            }

            $rutas[$row->idRuta]['segmentos'][] = [
                'id' => $row->idSegmento,
                'nombre' => $row->nombre,
                'color' => $row->color,
                'cordenadas' => $row->cordenadas,
                'bounds' => $row->bounds
            ];
        }

        return response()->json([
            'status' => true,
            'rutas' => array_values($rutas)
        ]);
    }

   public function obtenerRutasPorConductor($idUsuario)
{
    $rutas = DB::table('asignacion AS a')
        ->join('usuario AS u', 'u.codusuario', '=', 'a.usuario_codusuario')
        ->join('ruta AS r', 'r.codruta', '=', 'a.ruta_codruta')
        ->leftJoin('detalleRuta AS d', 'd.ruta_codruta', '=', 'r.codruta')
        ->where('u.codusuario', $idUsuario)
        ->where('u.tipo', 'C') // Ajusta según tu columna
        ->select(
            'r.codruta',
            'r.nombre',
            'r.limiteGeneral',
            'r.tipo',
            'r.icono',
            DB::raw('COUNT(d.iddetalleRuta) AS cantidadSegmentos')
        )
        ->groupBy('r.codruta','r.nombre','r.limiteGeneral','r.tipo','r.icono')
        ->get();

    return response()->json($rutas);
}





    public function guardarAsignaciones(Request $request)
    {
        $usuarioId = $request->input('usuario');
        $rutas = $request->input('rutas', []);

        if (!$usuarioId || !is_array($rutas) || empty($rutas)) {
            return response()->json(['error' => 'Datos inválidos'], 422);
        }

        foreach ($rutas as $rutaId) {
            // Evitar duplicados
            Asignacion::updateOrCreate(
                [
                    'usuario_codusuario' => $usuarioId,
                    'ruta_codruta' => $rutaId
                ],
                [
                    'ultimaActualizacion' => now()
                ]
            );
        }

        return response()->json([
            'message' => 'Asignaciones guardadas correctamente'
        ]);
    }


    public function editarAsignaciones(Request $request)
    {
        $usuarioId = $request->input('usuario');
        $nuevasRutas = $request->input('rutas', []);

        if (!$usuarioId || !is_array($nuevasRutas)) {
            return response()->json(['error' => 'Datos de entrada inválidos.'], 422);
        }

        try {
            Asignacion::where('usuario_codusuario', $usuarioId)->delete();

            $asignacionesAInsertar = [];
            $fechaActual = now();

            foreach ($nuevasRutas as $rutaId) {
                $asignacionesAInsertar[] = [
                    'usuario_codusuario' => $usuarioId,
                    'ruta_codruta' => $rutaId,
                    'ultimaActualizacion' => $fechaActual,
                    'created_at' => $fechaActual,
                    'updated_at' => $fechaActual,
                ];
            }

            if (!empty($asignacionesAInsertar)) {
                Asignacion::insert($asignacionesAInsertar);
            }

            return response()->json([
                'message' => 'Asignaciones editadas y reemplazadas correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al procesar las asignaciones: ' . $e->getMessage()], 500);
        }
    }
}
