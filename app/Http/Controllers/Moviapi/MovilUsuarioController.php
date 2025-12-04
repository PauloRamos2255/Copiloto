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

        // Buscar usuario tipo conductor con su empresa
        $usuario = Usuario::with('empresa')
            ->where('nombre', $request->nombre)
            ->where('tipo', 'C')
            ->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Conductor no encontrado'
            ], 404);
        }

        // Verificar contraseña
        if (!Hash::check($request->clave, $usuario->clave)) {
            return response()->json([
                'success' => false,
                'message' => 'Clave incorrecta'
            ], 401);
        }

        // Verificar sesión activa
        $actualizacionActiva = Actualizacion::where('estado', 'I')
            ->where('usuario_codusuario', $usuario->codusuario)
            ->first();

        if ($actualizacionActiva) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede iniciar sesión: existe una actualización activa para este usuario'
            ], 403);
        }

        // Crear actualización
        Actualizacion::create([
            'usuario_codusuario' => $usuario->codusuario,
            'estado' => 'I',
            'inicio' => now('America/Lima')
        ]);

        // Actualizar último ingreso
        $usuario->ultimoIngreso = now();
        $usuario->save();

        // Empresa separada
        $empresa = $usuario->empresa;

        return response()->json([
            'success' => true,
            'message' => 'Conductor autenticado correctamente',

            "usuario" => [
                'codusuario' => $usuario->codusuario,
                'nombre' => $usuario->nombre,
                'tipo' => $usuario->tipo,
                'ultimoIngreso' => $usuario->ultimoIngreso,
                'identificador' => $usuario->identificador,
                'empresa_codempresa' => $usuario->empresa_codempresa
            ],

            "empresa" => [
                'codempresa' => $empresa->codempresa,
                'nombre' => $empresa->nombre,
                'empresacol' => $empresa->empresacol ?? null,
                'observacion' => $empresa->observacion ?? null
            ]
        ]);
    }


    public function obtenerRutasConductor($codusuario)
    {
        $rutas = DB::table('asignacion as a')
            ->join('ruta as r', 'a.ruta_codruta', '=', 'r.codruta')
            ->leftJoin('detalleRuta as d', 'r.codruta', '=', 'd.ruta_codruta')
            ->leftJoin('segmento as s', 'd.segmento_codsegmento', '=', 's.codsegmento')
            ->where('a.usuario_codusuario', $codusuario)
            ->select(
                'a.codasignacion',
                'a.ruta_codruta as rutaId',
                'a.usuario_codusuario',
                'a.ultimaActualizacion',
                'r.codruta',
                'r.nombre as rutaNombre',
                'r.limiteGeneral',
                'r.fechaCreacion',
                'r.icono',
                'r.tipo',
                'd.iddetalleRuta',
                'd.segmento_codsegmento',
                'd.velocidadPermitida',
                'd.mensaje as detalleMensaje',
                's.codsegmento as segmentoId',
                's.nombre as segmentoNombre',
                's.color',
                's.cordenadas',
                's.bounds'
            )
            ->get();

        $resultado = [];

        foreach ($rutas as $row) {
            $rutaKey = $row->codruta;

            // Crear estructura base por ruta
            if (!isset($resultado[$rutaKey])) {
                $resultado[$rutaKey] = [
                    'asignacion' => [
                        'codasignacion' => (int)$row->codasignacion,
                        'ruta_codruta' => (int)$row->rutaId,
                        'usuario_codusuario' => (int)$row->usuario_codusuario,
                        'ultimaActualizacion' => \Carbon\Carbon::parse($row->ultimaActualizacion)->toISOString()
                    ],
                    'ruta' => [
                        'codruta' => (int)$row->codruta,
                        'nombre' => $row->rutaNombre,
                        'limiteGeneral' => (int)$row->limiteGeneral,
                        'fechaCreacion' => \Carbon\Carbon::parse($row->fechaCreacion)->toISOString(),
                        'icono' => $row->icono,
                        'tipo' => $row->tipo
                    ],
                    'detalles' => [],
                    'segmentos' => [] // ← nuevos segmentos únicos
                ];
            }

            // Procesar detalle
            if ($row->iddetalleRuta) {

                // → Limpiar coordenadas y bounds
                $cordenadas = $this->limpiarJsonArray($row->cordenadas);
                $bounds = $this->limpiarJsonArray($row->bounds);

                $detalle = [
                    'coddetalle' => (int)$row->iddetalleRuta,
                    'ruta_codruta' => (int)$row->rutaId,
                    'segmento_codsegmento' => (int)$row->segmento_codsegmento,
                    'velocidadPermitida' => (int)$row->velocidadPermitida,
                    'mensaje' => $row->detalleMensaje ?? '',
                    'segmento' => $row->segmentoId ? [
                        'codsegmento' => (int)$row->segmentoId,
                        'nombre' => $row->segmentoNombre,
                        'color' => $row->color,
                        'cordenadas' => $cordenadas,
                        'bounds' => $bounds
                    ] : null
                ];

                $resultado[$rutaKey]['detalles'][] = $detalle;

                // → Guardar segmentos sin repetidos
                if ($row->segmentoId && !isset($resultado[$rutaKey]['segmentos'][$row->segmentoId])) {
                    $resultado[$rutaKey]['segmentos'][$row->segmentoId] = [
                        'codsegmento' => (int)$row->segmentoId,
                        'nombre' => $row->segmentoNombre,
                        'color' => $row->color,
                        'cordenadas' => $cordenadas,
                        'bounds' => $bounds
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'rutas' => array_values($resultado)
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function limpiarJsonArray($valor)
    {
        if (!$valor || $valor === "null" || trim($valor) === "") {
            return [];
        }

        $json = json_decode($valor, true);

        return is_array($json) ? $json : [];
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
        $actualizacion->estado = 'F';
        $actualizacion->fin = now('America/Lima');
        $actualizacion->save();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }


    public function obtenerSegmentos($idRuta)
    {
        $ruta = Ruta::with('detallesRuta.segmento')->find($idRuta);

        if (!$ruta) {
            return response()->json(['error' => 'La ruta no existe'], 404);
        }

        $segmentosTotales = [];

        foreach ($ruta->detallesRuta as $detalle) {
            if ($detalle->segmento) { // evitar null
                $segmentosTotales[] = $detalle->segmento;
            }
        }

        return response()->json($segmentosTotales, 200);
    }
}
