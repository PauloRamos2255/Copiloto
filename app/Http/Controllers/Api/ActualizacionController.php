<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Actualizacion;
use Illuminate\Support\Facades\Log;

class ActualizacionController extends Controller
{
    public function registrar(Request $request)
    {
        $request->validate([
            'inicio' => 'required|numeric',
            'estado' => 'required|in:A,R',
            'usuario_codusuario' => 'required|integer'
        ]);

        // Revisar si ya hay una actualización activa
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

        // Crear registro usando los mutators del modelo
        $actualizacion = Actualizacion::create([
            'inicio' => $request->inicio,
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
            'id' => 'required|integer',
            'estado' => 'required|in:A,R',
            'fecha_fin' => 'required|numeric',
            'usuario_codusuario' => 'required|integer'
        ]);

        // Buscar el registro por ID y usuario
        $actualizacion = Actualizacion::where('codactualizacion', $request->id)
            ->where('usuario_codusuario', $request->usuario_codusuario)
            ->first();

        if (!$actualizacion) {
            return response()->json([
                'success' => false,
                'codigo' => 3,
                'error' => 'Registro no encontrado'
            ], 404);
        }

        // Actualizar fin y estado usando mutators
        $actualizacion->update([
            'fin' => $request->fecha_fin,
            'estado' => $request->estado
        ]);

        return response()->json([
            'success' => true,
            'codigo' => 1,
            'mensaje' => 'Registro actualizado correctamente',
            'data' => $actualizacion
        ]);
    }
}
