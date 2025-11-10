<?php

namespace App\Http\Controllers\Ruta;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\DetalleRuta;
use App\Models\Segmento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RutaController extends Controller
{
    // Listar todas las rutas con sus detalles
    public function index()
    {
        $rutas = Ruta::with('detallesRuta')->get();
        return response()->json($rutas, 200);
    }

    // Mostrar una ruta específica con sus detalles
    public function show($id)
    {
        $ruta = Ruta::with('detallesRuta')->findOrFail($id);
        return response()->json($ruta, 200);
    }

    // Crear una ruta con sus detalles
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:G,V',
            'limiteGeneral' => 'required|numeric|min:0',
            'detalles' => 'required|array|min:1',
            'detalles.*.segmento_codsegmento' => 'required|exists:segmento,codsegmento',
            'detalles.*.velocidadPermitida' => 'required|numeric|min:0',
            'detalles.*.mensaje' => 'nullable|string|max:255',
            'icono' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $ruta = new Ruta();
            $ruta->nombre = $request->nombre;
            $ruta->tipo = $request->tipo;
            $ruta->limiteGeneral = $request->limiteGeneral;
            $ruta->fechaCreacion = now();

            // Guardar logo si existe
            if ($request->hasFile('icono')) {
                $ruta->icono = $request->file('icono')->store('rutas/iconos', 'public');
            }

            $ruta->save();

            // Crear detalles
            foreach ($request->detalles as $detalle) {
                $ruta->detallesRuta()->create([
                    'segmento_codsegmento' => $detalle['segmento_codsegmento'],
                    'velocidadPermitida' => $detalle['velocidadPermitida'],
                    'mensaje' => $detalle['mensaje'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Ruta creada correctamente',
                'ruta' => $ruta->load('detallesRuta')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la ruta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function duplicar(Request $request)
    {
        try {
            // 🔹 Crear nueva ruta directamente (sin validaciones)
            $ruta = new Ruta();
            $ruta->nombre = $request->input('nombre');
            $ruta->tipo = $request->input('tipo');
            $ruta->limiteGeneral = $request->input('limiteGeneral');
            $ruta->icono = $request->input('icono'); // Puede ser null o cadena
            $ruta->save();

            // 🔹 Guardar los detalles (si existen)
            if ($request->has('detalles') && is_array($request->detalles)) {
                foreach ($request->detalles as $detalle) {
                    $ruta->detallesRuta()->create([
                        'segmento_codsegmento' => $detalle['segmento_codsegmento'] ?? null,
                        'velocidadPermitida' => $detalle['velocidadPermitida'] ?? 0,
                        'mensaje' => $detalle['mensaje'] ?? '',
                        'orden' => $detalle['orden'] ?? 0,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Ruta duplicada correctamente',
                'ruta' => $ruta->load('detallesRuta')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al duplicar la ruta',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Actualizar ruta y sus detalles
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|in:G,V',
            'limiteGeneral' => 'sometimes|numeric|min:0',
            'icono' => 'nullable|image|max:2048',
            'detalles' => 'sometimes|array|min:1',
            'detalles.*.segmento_codsegmento' => 'required_with:detalles|exists:segmento,codsegmento',
            'detalles.*.velocidadPermitida' => 'required_with:detalles|numeric|min:0',
            'detalles.*.mensaje' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $ruta = Ruta::findOrFail($id);

            // Actualizar campos básicos
            $ruta->update($validated);

            // Actualizar logo si se sube uno nuevo
            if ($request->hasFile('icono')) {
                if ($ruta->icono) {
                    Storage::disk('public')->delete($ruta->icono);
                }
                $ruta->icono = $request->file('icono')->store('rutas/iconos', 'public');
                $ruta->save();
            }

            // Actualizar detalles
            if (!empty($validated['detalles'])) {
                // Eliminar antiguos
                $ruta->detallesRuta()->delete();

                // Crear nuevos
                foreach ($validated['detalles'] as $detalle) {
                    $ruta->detallesRuta()->create([
                        'segmento_codsegmento' => $detalle['segmento_codsegmento'],
                        'velocidadPermitida' => $detalle['velocidadPermitida'],
                        'mensaje' => $detalle['mensaje'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Ruta actualizada correctamente',
                'ruta' => $ruta->load('detallesRuta')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la ruta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar ruta y sus detalles
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $ruta = Ruta::findOrFail($id);

            // Eliminar logo
            if ($ruta->icono) {
                Storage::disk('public')->delete($ruta->icono);
            }

            // Eliminar detalles
            $ruta->detallesRuta()->delete();

            // Eliminar ruta
            $ruta->delete();

            DB::commit();

            return response()->json(['message' => 'Ruta eliminada exitosamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la ruta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Obtener detalles de una ruta con nombres de segmentos
    public function detallesRuta($codruta)
    {
        $detalles = DetalleRuta::where('ruta_codruta', $codruta)->get();

        $result = $detalles->map(function ($detalle) {
            $segmento = Segmento::find($detalle->segmento_codsegmento);

            return [
                'id' => $detalle->id,
                'ruta_codruta' => $detalle->ruta_codruta,
                'segmento_codsegmento' => $detalle->segmento_codsegmento,
                'velocidadPermitida' => $detalle->velocidadPermitida,
                'mensaje' => $detalle->mensaje,
                'nombre' => $segmento ? $segmento->nombre : null,
                'created_at' => $detalle->created_at,
                'updated_at' => $detalle->updated_at,
            ];
        });

        return response()->json($result);
    }
}
