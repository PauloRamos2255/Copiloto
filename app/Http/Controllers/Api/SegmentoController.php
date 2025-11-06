<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Segmento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SegmentoController extends Controller
{

    public function index()
    {
        return response()->json([
            'success' => true,
            'segmentos' => Segmento::all()
        ]);
    }

   public function sincronizar(Request $request)
{
    $segmentos = $request->input('segmentos', []);
    $batch = [];

    foreach ($segmentos as $seg) {
        $id = $seg['id'] ?? null;
        if (!$id) continue;

        $batch[] = [
            'codsegmento' => $id,
            'nombre'      => $seg['nombre'] ?? 'Sin nombre',
            'color'       => $seg['colorHex'] ?? $seg['color'] ?? '#FFFFFF', // ✅ usa colorHex si existe
            'cordenadas'  => json_encode($seg['cordenadas'] ?? []),
            'bounds'      => json_encode($seg['bounds'] ?? []),
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    if (!empty($batch)) {
        Segmento::upsert(
            $batch,
            ['codsegmento'], // clave única
            ['nombre', 'color', 'cordenadas', 'bounds', 'updated_at']
        );
    }

    return response()->json([
        'success' => true,
        'mensaje' => 'Segmentos sincronizados correctamente'
    ]);
}





    public function guardar(Request $request)
    {
        $zonas = $request->input('zonas', []);


        if (!is_array($zonas) || empty($zonas)) {
            return response()->json([
                'success' => false,
                'error' => 'No se recibieron zonas válidas'
            ], 400);
        }

        $zonasData = [];
        foreach ($zonas as $z) {
            if (!isset($z['id'])) continue;

            $zonasData[] = [
                'codsegmento' => $z['id'],
                'nombre'      => $z['nombre'] ?? $z['n'] ?? '',
                'color'       => $z['color'] ?? '#FFFFFF',
                'cordenadas'  => !empty($z['cordenadas']) ? json_encode($z['cordenadas'], JSON_UNESCAPED_UNICODE) : null,
                'bounds'      => !empty($z['bounds']) ? json_encode($z['bounds'], JSON_UNESCAPED_UNICODE) : null,
                'updated_at'  => now(),
                'created_at'  => now(),
            ];
        }

        try {
            Segmento::upsert(
                $zonasData,
                ['codsegmento'],
                ['nombre', 'color', 'cordenadas', 'bounds', 'updated_at']
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


    public function destroy($id)
    {
        $segmento = Segmento::find($id);
        if (!$segmento) {
            return response()->json(['success' => false, 'mensaje' => 'Segmento no encontrado'], 404);
        }

        $segmento->delete();

        return response()->json(['success' => true, 'mensaje' => 'Segmento eliminado correctamente']);
    }
}
