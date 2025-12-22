<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Actualizacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActualizacionController extends Controller
{
    public function registrar(Request $request)
    {
        $request->validate([
            'inicio' => 'required|numeric',
            'estado' => 'required|in:A,C,E',
            'usuario_codusuario' => 'required|integer'
        ]);

        $enProceso = Actualizacion::where('usuario_codusuario', $request->usuario_codusuario)
            ->where('estado', 'A')
            ->whereNull('fin')
            ->first();

        if ($enProceso && $request->estado == 'A') {
            return response()->json([
                'success' => false,
                'codigo' => 2,
                'error' => 'El usuario ya tiene un proceso de actualización activo'
            ], 409);
        }


        $inicioDate = Carbon::now('America/Lima');

        $actualizacion = Actualizacion::create([
            'inicio' => $inicioDate,
            'estado' => $request->estado,
            'usuario_codusuario' => $request->usuario_codusuario
        ]);

        return response()->json([
            'success' => true,
            'codigo' => 1,
            'mensaje' => 'Registro creado correctamente',
            'data' => $actualizacion
        ], 201);
    }



    public function actualizarPorId(Request $request)
    {
        $request->validate([
            'codactualizacion' => 'required|integer',
            'estado' => 'required|in:A,C,E',
            'usuario_codusuario' => 'required|integer'
        ]);

        $actualizacion = Actualizacion::where('codactualizacion', $request->codactualizacion)
            ->where('usuario_codusuario', $request->usuario_codusuario)
            ->first();

        if (!$actualizacion) {
            return response()->json([
                'success' => false,
                'codigo' => 3,
                'error' => 'Registro no encontrado'
            ], 404);
        }

        if ($actualizacion->estado != 'A' && $request->estado == 'A') {
            return response()->json([
                'success' => false,
                'codigo' => 4,
                'error' => 'La actualización ya está finalizada'
            ], 400);
        }

        $finDate = Carbon::now('America/Lima');

        $actualizacion->update([
            'fin' => $finDate,
            'estado' => $request->estado
        ]);

        return response()->json([
            'success' => true,
            'codigo' => 1,
            'mensaje' => 'Registro actualizado correctamente',
            'data' => $actualizacion
        ]);
    }


    public function finalizarActualizacionPorUsuario(  $usuarioId)
    {
        // Validar que el usuario sea un entero positivo
        if ($usuarioId <= 0) {
            return response()->json([
                'success' => false,
                'codigo' => 2,
                'error' => 'ID de usuario inválido'
            ], 400);
        }

        // Buscar la actualización activa del usuario
        $actualizacion = Actualizacion::where('usuario_codusuario', $usuarioId)
            ->where('estado', 'A') // solo activas
            ->first();

        if (!$actualizacion) {
            return response()->json([
                'success' => false,
                'codigo' => 3,
                'error' => 'No se encontró una actualización activa para el usuario'
            ], 404);
        }

        // Marcar la actualización como finalizada con ERROR
        $finDate = Carbon::now('America/Lima');

        $actualizacion->update([
            'fin' => $finDate,
            'estado' => 'E'
        ]);

        return response()->json([
            'success' => true,
            'codigo' => 1,
            'mensaje' => 'Actualización finalizada con ERROR correctamente',
            'data' => $actualizacion
        ]);
    }
}
