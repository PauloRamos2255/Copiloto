<?php

namespace App\Http\Controllers\Moviapi;

use App\Http\Controllers\Controller;
use App\Models\Actualizacion;
use App\Models\Evento;
use App\Models\HistoricoViaje;
use App\Models\Ruta;
use App\Models\Usuario;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Log;
use Carbon\Carbon;

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
        // Primero obtenemos las rutas normalmente
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
            ->get()
            ->groupBy('codruta');

        // Ahora obtenemos UN SOLO histórico para TODAS las asignaciones
        $ultimoHistoricoGeneral = DB::table('historicoViaje as hv')
            ->join('asignacion as a', 'hv.asignacion_codAsignacion', '=', 'a.codasignacion')
            ->where('a.usuario_codusuario', $codusuario)
            ->where('hv.estado', '!=', 'F')
            ->orderBy('hv.inicio', 'desc')
            ->select(
                'hv.codhistorioViaje',
                'hv.inicio as historicoInicio',
                'hv.fin as historicoFin',
                'hv.estado as historicoEstado',
                'a.codasignacion'
            )
            ->first();

        // Y UN SOLO evento para TODAS las asignaciones
        $ultimoEventoGeneral = DB::table('evento as e')
            ->join('historicoViaje as hv', 'e.historicoViaje_codhistorioViaje', '=', 'hv.codhistorioViaje')
            ->join('asignacion as a', 'hv.asignacion_codAsignacion', '=', 'a.codasignacion')
            ->where('a.usuario_codusuario', $codusuario)
            ->whereNull('e.fin')
            ->orderBy('e.inicio', 'desc')
            ->select(
                'e.codevento',
                'e.nombre as eventoNombre',
                'e.inicio as eventoInicio',
                'e.fin as eventoFin',
                'e.tipo as eventoTipo',
                'hv.codhistorioViaje'
            )
            ->first();

        $resultado = [];

        foreach ($rutas as $codruta => $rows) {
            $firstRow = $rows->first();

            $resultado[$codruta] = [
                'asignacion' => [
                    'codasignacion' => (int) $firstRow->codasignacion,
                    'ruta_codruta' => (int) $firstRow->rutaId,
                    'usuario_codusuario' => (int) $firstRow->usuario_codusuario,
                    'ultimaActualizacion' => \Carbon\Carbon::parse($firstRow->ultimaActualizacion)->toISOString()
                ],
                'ruta' => [
                    'codruta' => (int) $firstRow->codruta,
                    'nombre' => $firstRow->rutaNombre,
                    'limiteGeneral' => (int) $firstRow->limiteGeneral,
                    'fechaCreacion' => \Carbon\Carbon::parse($firstRow->fechaCreacion)->toISOString(),
                    'icono' => $firstRow->icono,
                    'tipo' => $firstRow->tipo
                ],
                'ultimoHistoricoViaje' => null,
                'ultimoEvento' => null,
                'detalles' => [],
            ];

            // Asignar el mismo histórico general a todas las rutas
            if ($ultimoHistoricoGeneral) {
                $resultado[$codruta]['ultimoHistoricoViaje'] = [
                    'codhistorioViaje' => (int) $ultimoHistoricoGeneral->codhistorioViaje,
                    'inicio' => $ultimoHistoricoGeneral->historicoInicio ? \Carbon\Carbon::parse($ultimoHistoricoGeneral->historicoInicio)->toISOString() : null,
                    'fin' => $ultimoHistoricoGeneral->historicoFin ? \Carbon\Carbon::parse($ultimoHistoricoGeneral->historicoFin)->toISOString() : null,
                    'estado' => $ultimoHistoricoGeneral->historicoEstado
                ];
            }

            // Asignar el mismo evento general a todas las rutas
            if ($ultimoEventoGeneral) {
                $resultado[$codruta]['ultimoEvento'] = [
                    'codevento' => (int) $ultimoEventoGeneral->codevento,
                    'nombre' => $ultimoEventoGeneral->eventoNombre,
                    'inicio' => $ultimoEventoGeneral->eventoInicio ? \Carbon\Carbon::parse($ultimoEventoGeneral->eventoInicio)->toISOString() : null,
                    'fin' => $ultimoEventoGeneral->eventoFin ? \Carbon\Carbon::parse($ultimoEventoGeneral->eventoFin)->toISOString() : null,
                    'tipo' => $ultimoEventoGeneral->eventoTipo
                ];
            }

            // Procesar detalles únicos
            $detallesUnicos = $rows->where('iddetalleRuta', '!=', null)
                ->unique('iddetalleRuta');

            foreach ($detallesUnicos as $detalleRow) {
                $cordenadas = $this->limpiarJsonArray($detalleRow->cordenadas);
                $bounds = $this->limpiarJsonArray($detalleRow->bounds);

                $detalle = [
                    'coddetalle' => (int) $detalleRow->iddetalleRuta,
                    'ruta_codruta' => (int) $detalleRow->rutaId,
                    'segmento_codsegmento' => (int) $detalleRow->segmento_codsegmento,
                    'velocidadPermitida' => (int) $detalleRow->velocidadPermitida,
                    'mensaje' => $detalleRow->detalleMensaje ?? '',
                    'segmento' => $detalleRow->segmentoId ? [
                        'codsegmento' => (int) $detalleRow->segmentoId,
                        'nombre' => $detalleRow->segmentoNombre,
                        'color' => $detalleRow->color,
                        'cordenadas' => $cordenadas,
                        'bounds' => $bounds
                    ] : null
                ];

                $resultado[$codruta]['detalles'][] = $detalle;
            }
        }

        return response()->json([
            'success' => true,
            'rutas' => array_values($resultado),
            'historicoGeneral' => $ultimoHistoricoGeneral ? [
                'codhistorioViaje' => (int) $ultimoHistoricoGeneral->codhistorioViaje,
                'asignacion_codAsignacion' => (int) $ultimoHistoricoGeneral->codasignacion
            ] : null,
            'eventoGeneral' => $ultimoEventoGeneral ? [
                'codevento' => (int) $ultimoEventoGeneral->codevento,
                'historicoViaje_codhistorioViaje' => (int) $ultimoEventoGeneral->codhistorioViaje
            ] : null
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

    public function validarSesionActiva($usuarioId)
    {
        // Buscar si existe alguna actualización activa para este usuario
        $existe = Actualizacion::where('usuario_codusuario', $usuarioId)
            ->where('estado', 'I')
            ->exists();

        if ($existe) {
            return response()->json([
                'activo' => true,
                'mensaje' => 'El usuario tiene una sesión o actualización activa.'
            ]);
        } else {
            return response()->json([
                'activo' => false,
                'mensaje' => 'El usuario no tiene sesiones activas.'
            ]);
        }
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

    public function insertarViaje(Request $request)
    {
        try {
            // Validación estricta
            $request->validate([
                'asignacion_codAsignacion' => 'required|integer',
                'inicio' => 'required|date',
                'estado' => 'required|string|max:1',
                'fin' => 'nullable|date', // Fecha fin opcional
            ]);

            // Crear registro usando create()
            $historico = HistoricoViaje::create([
                'asignacion_codAsignacion' => $request->asignacion_codAsignacion,
                'inicio' => $request->inicio,
                'estado' => $request->estado,
                'fin' => $request->fin ?? null, // Si no viene, se deja en null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registro creado correctamente',
                'data' => $historico
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function actualizarViajePorId(Request $request)
    {
        try {
            // Validar datos
            $request->validate([
                'codhistorioViaje' => 'required|integer',
                'fin' => 'required|date',
                'estado' => 'required|string'
            ]);

            // Buscar histórico por id
            $historico = HistoricoViaje::find($request->codhistorioViaje);

            if (!$historico) {
                return response()->json([
                    'success' => false,
                    'message' => 'Histórico no encontrado para el id: ' . $request->codhistorioViaje
                ], 404);
            }

            // Actualizar fecha fin y estado
            $historico->update([
                'fin' => $request->fin,
                'estado' => $request->estado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Histórico actualizado correctamente',
                'data' => $historico
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }




    public function actualizarEstadoPorViaje(Request $request)
    {
        try {
            // Validar datos
            $request->validate([
                'codhistorioViaje' => 'required|integer',
                'estado' => 'required|string',
                'fin' => 'nullable|date'
            ]);

            // Buscar histórico por cod de viaje
            $historico = HistoricoViaje::find($request->codhistorioViaje);

            if (!$historico) {
                return response()->json([
                    'success' => false,
                    'message' => 'Histórico no encontrado para el viaje: ' . $request->codhistorioViaje
                ], 404);
            }

            // Actualizar estado
            $historico->estado = $request->estado;

            // Actualizar fin solo si se envió en la request
            if ($request->has('fin') && !empty($request->fin)) {
                $historico->fin = $request->fin; // Cambiado a 'fin'
            }

            $historico->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => $historico
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }






    public function validarUsuario(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'usuario_id' => 'required|integer',
            'ultimoIngreso_cliente' => 'required|date',
        ]);

        $usuario = Usuario::where('codusuario', $request->usuario_id)
            ->where('nombre', $request->nombre)
            ->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Usuario no encontrado o datos no coinciden',
                'usuario_id' => null
            ]);
        }

        // Convertir fechas a la misma zona horaria
        $ultimoCliente = Carbon::parse($request->ultimoIngreso_cliente)->setTimezone('America/Lima');
        $ultimoServidor = Carbon::parse($usuario->ultimoIngreso)->setTimezone('America/Lima');
        $fechasCoinciden = $ultimoCliente->diffInSeconds($ultimoServidor) <= 1;

        // Validar las 3 condiciones
        $valido = $usuario->codusuario == $request->usuario_id &&
            $usuario->nombre === $request->nombre &&
            $fechasCoinciden;

        return response()->json([
            'success' => $valido,
            'mensaje' => $valido ? 'Datos del usuario correctos' : 'Datos no válidos o desactualizados',
            'usuario_id' => $valido ? $usuario->codusuario : null
        ]);
    }





    public function insertarEvento(Request $request)
    {
        try {
            // Validación estricta
            $request->validate([
                'nombre' => 'required|string|max:30',
                'inicio' => 'required|date',
                'tipo' => 'required|string|max:1',
                'historicoViaje_codhistorioViaje' => 'required|integer',
                'fin' => 'nullable|date',
                'detalle' => 'nullable|string|max:500', // AGREGAR ESTO
            ]);

            // Crear registro usando create()
            $evento = Evento::create([
                'nombre' => $request->nombre,
                'inicio' => $request->inicio,
                'tipo' => $request->tipo,
                'historicoViaje_codhistorioViaje' => $request->historicoViaje_codhistorioViaje,
                'fin' => $request->fin ?? null,
                'detalle' => $request->detalle ?? null, // AGREGAR ESTO
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evento creado correctamente',
                'data' => $evento
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }





    public function EventoUpdateFin(Request $request)
    {
        $request->validate([
            'codevento' => 'required|integer|exists:evento,codevento',
            'fin' => 'required|date'
        ]);

        $evento = Evento::findOrFail($request->codevento);

        $evento->update([
            'fin' => $request->fin
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fecha fin actualizada correctamente',
            'data' => $evento
        ]);
    }
}
