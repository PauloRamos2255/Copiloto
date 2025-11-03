<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Segmento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SegmentoController extends Controller
{
    // 🔹 Listar todos los segmentos
    public function index()
    {
        return response()->json([
            'success' => true,
            'segmentos' => Segmento::all()
        ]);
    }

    // 🔹 Guardar o actualizar múltiples segmentos
    public function guardar(Request $request)
    {
        $zonas = $request->input('zonas', []);

        if (!is_array($zonas) || empty($zonas)) {
            return response()->json([
                'success' => false,
                'error' => 'No se recibieron zonas válidas'
            ], 400);
        }

        // Construir array de datos para upsert
        $zonasData = [];
        foreach ($zonas as $z) {
            if (!isset($z['id'])) continue;

            $zonasData[] = [
                'codsegmento' => $z['id'],
                'nombre'      => $z['n'] ?? $z['nombre'] ?? '',
                'color'       => $z['c'] ?? '#FFFFFF',
                'cordenadas'  => isset($z['p']) && is_array($z['p']) ? json_encode($z['p'], JSON_UNESCAPED_UNICODE) : null,
                'bounds'      => isset($z['b']) && is_array($z['b']) ? json_encode($z['b'], JSON_UNESCAPED_UNICODE) : null,
                'updated_at'  => now(),
                'created_at'  => now(),
            ];
        }

        try {
            // upsert: evita bucles de updateOrCreate
            Segmento::upsert(
                $zonasData,
                ['codsegmento'], // columna para detectar duplicados
                ['nombre', 'color', 'cordenadas', 'bounds', 'updated_at'] // columnas a actualizar
            );

            return response()->json([
                'success' => true,
                'mensaje' => 'Segmentos guardados/actualizados correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar segmentos', [
                'error' => $e->getMessage(),
                'zonas' => $zonas
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al guardar segmentos: ' . $e->getMessage()
            ], 500);
        }
    }
}
