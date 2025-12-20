<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\HistoricoViaje;
use App\Models\Ruta;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AsignacionController extends Controller
{
    public function index()
    {
        $usuarios = DB::table('usuario AS u')
            ->join('asignacion AS a', 'a.usuario_codusuario', '=', 'u.codusuario')
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


    public function conductoresSinRutas()
    {
        $usuarios = DB::table('usuario AS u')
            ->where('u.tipo', 'C')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('asignacion AS a')
                    ->whereColumn('a.usuario_codusuario', 'u.codusuario');
            })
            ->select(
                'u.codusuario',
                'u.nombre',
                'u.identificador'
            )
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
            ->join('usuario as u', 'u.codusuario', '=', 'a.usuario_codusuario') // 🔥 AGREGADO
            ->where('a.usuario_codusuario', $idUsuario)
            ->select(
                'r.codruta as idRuta',
                'r.nombre as nombreRuta',
                's.nombre',
                's.color',
                's.codsegmento as idSegmento',
                's.cordenadas',
                's.bounds',
                'u.nombre as nombreConductor' // 🔥 AGREGADO
            )
            ->orderBy('r.codruta')
            ->get();

        $rutas = [];
        $nombreConductor = null;

        foreach ($rows as $row) {

            // Guardamos el nombre del conductor 1 sola vez
            if ($nombreConductor === null) {
                $nombreConductor = $row->nombreConductor;
            }

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
            'conductor' => $nombreConductor,
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
            ->groupBy('r.codruta', 'r.nombre', 'r.limiteGeneral', 'r.tipo', 'r.icono')
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

        DB::beginTransaction();

        try {
            $fechaActual = now();

            // Rutas ya asignadas al usuario
            $rutasActuales = Asignacion::where('usuario_codusuario', $usuarioId)
                ->pluck('ruta_codruta')
                ->toArray();

            foreach ($nuevasRutas as $rutaId) {

                if (in_array($rutaId, $rutasActuales)) {
                    // SOLO actualizar fecha
                    Asignacion::where('usuario_codusuario', $usuarioId)
                        ->where('ruta_codruta', $rutaId)
                        ->update([
                            'ultimaActualizacion' => $fechaActual
                        ]);
                } else {
                    // Insertar nueva asignación
                    Asignacion::create([
                        'usuario_codusuario' => $usuarioId,
                        'ruta_codruta' => $rutaId,
                        'ultimaActualizacion' => $fechaActual,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Asignaciones actualizadas correctamente'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al actualizar asignaciones: ' . $e->getMessage()
            ], 500);
        }
    }



    public function eliminarAsignacionesUsuario($usuarioId)
    {
        try {
            $eliminadas = Asignacion::where('usuario_codusuario', $usuarioId)->delete();
            return response()->json([
                'message' => "Se eliminaron $eliminadas asignaciones del usuario $usuarioId."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => "Ocurrió un error al eliminar asignaciones: " . $e->getMessage()
            ], 500);
        }
    }


    public function validarAsignacionActivaPorUsuario($usuarioId)
    {
        $existe = Historicoviaje::whereHas('asignacion', function ($query) use ($usuarioId) {
            $query->where('usuario_codusuario', $usuarioId);
        })
            ->where('estado', 'I')
            ->exists();

        if ($existe) {
            return [
                'activo' => true,
                'mensaje' => 'El usuario tiene asignaciones activas en un viaje.'
            ];
        } else {
            return [
                'activo' => false,
                'mensaje' => 'El usuario no tiene viajes activos.'
            ];
        }
    }
}
