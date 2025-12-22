<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Segmento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SegmentoController extends Controller
{

    public function show($id)
    {
        $segmento = Segmento::find($id);
        if (!$segmento) {
            return response()->json(['success' => false, 'mensaje' => 'Segmento no encontrado'], 404);
        }

        return response()->json($segmento);
    }


    public function index()
    {
        return response()->json([
            'success' => true,
            'segmentos' => Segmento::all()
        ]);
    }

    /**
     * GUARDAR/ACTUALIZAR un segmento
     * PUT /api/segmentos/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            // Buscar por codsegmento (que es el ID)
            $segmento = Segmento::where('codsegmento', $id)->firstOrFail();

            // Validar entrada
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'color' => 'nullable|string',
                'cordenadas' => 'required|array',
                'bounds' => 'nullable|array',
            ]);

            // Actualizar
            $segmento->update([
                'nombre' => $validated['nombre'],
                'color' => $validated['color'] ?? $segmento->color,
                'cordenadas' => json_encode($validated['cordenadas']),
                'bounds' => json_encode($validated['bounds'] ?? []),
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Segmento actualizado correctamente',
                'segmento' => $segmento
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Segmento no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ELIMINAR un segmento
     * DELETE /api/segmentos/{id}
     */
    public function destroy($id)
    {
        try {
            // Verificar si tiene rutas
            $tieneRutas = DB::table('detalleRuta')
                ->where('segmento_codsegmento', $id)
                ->exists();

            if ($tieneRutas) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede eliminar. Este segmento está asignado a rutas'
                ], 409);
            }

            // Buscar por codsegmento
            $segmento = Segmento::where('codsegmento', $id)->firstOrFail();
            $nombre = $segmento->nombre;

            $segmento->delete();

            return response()->json([
                'success' => true,
                'mensaje' => "Segmento '{$nombre}' eliminado correctamente"
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Segmento no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ELIMINAR SEGMENTO CON CASCADA (elimina también detalleRuta)
     * POST /api/segmentos/{id}/cascada
     */
    public function eliminarCascada($id)
    {
        try {
            DB::beginTransaction();

            // Eliminar detalles de ruta asociados
            DB::table('detalleRuta')
                ->where('segmento_codsegmento', $id)
                ->delete();

            // Buscar y eliminar segmento
            $segmento = Segmento::where('codsegmento', $id)->firstOrFail();
            $nombre = $segmento->nombre;

            $segmento->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => "Segmento '{$nombre}' y sus rutas eliminados correctamente"
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Segmento no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
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
                'color'       => $seg['colorHex'] ?? $seg['color'] ?? '#FFFFFF',
                'cordenadas'  => json_encode($seg['cordenadas'] ?? []),
                'bounds'      => json_encode($seg['bounds'] ?? []),
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        if (!empty($batch)) {
            Segmento::upsert(
                $batch,
                ['codsegmento'],
                ['nombre', 'color', 'cordenadas', 'bounds', 'updated_at']
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Segmentos sincronizados correctamente'
        ]);
    }

    public function verificarRutasVinculadas($codsegmento)
    {
        try {
            $detalles = DB::table('detalleRuta')
                ->join('ruta', 'detalleRuta.ruta_codruta', '=', 'ruta.codruta')
                ->where('detalleRuta.segmento_codsegmento', $codsegmento)
                ->select(
                    'detalleRuta.iddetalleRuta',
                    'detalleRuta.ruta_codruta',
                    'ruta.nombre as ruta_nombre',
                    'detalleRuta.velocidadPermitida',
                    'detalleRuta.mensaje'
                )
                ->get();

            if ($detalles->isEmpty()) {
                return response()->json([
                    'detalles' => [],
                    'existe' => false,
                    'mensaje' => 'El segmento no está vinculado a ninguna ruta'
                ], 200);
            }

            return response()->json([
                'detalles' => $detalles,
                'existe' => true,
                'cantidad' => $detalles->count(),
                'mensaje' => 'Este segmento está vinculado a ' . $detalles->count() . ' ruta(s)'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al verificar rutas: ' . $e->getMessage()
            ], 500);
        }
    }

}