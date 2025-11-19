<?php

namespace App\Http\Controllers\Ruta;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Ruta;
use App\Models\DetalleRuta;
use App\Models\Segmento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class RutaController extends Controller
{
    // Listar todas las rutas con sus detalles
    public function index()
    {
        // Traer todas las rutas con sus detalles y segmentos
        $rutasPlano = DB::table('ruta as r')
            ->leftJoin('detalleRuta as d', 'r.codruta', '=', 'd.ruta_codruta')
            ->leftJoin('segmento as s', 'd.segmento_codsegmento', '=', 's.codsegmento')
            ->select(
                'r.codruta',
                'r.nombre',
                'r.tipo',
                'r.icono',
                'r.limiteGeneral',
                'd.segmento_codsegmento',
                'd.mensaje',
                'd.velocidadPermitida',
                's.nombre as segmento_nombre',
                's.color as segmento_color'
            )
            ->get();

        // Agrupar los segmentos por ruta
        $rutas = [];
        foreach ($rutasPlano as $row) {
            $rutaId = $row->codruta;

            if (!isset($rutas[$rutaId])) {
                $rutas[$rutaId] = [
                    'id' => $row->codruta,
                    'nombre' => $row->nombre,
                    'tipo' => $row->tipo,
                    'icono' => $row->icono,
                    'limiteGeneral' => $row->limiteGeneral,
                    'detalles_ruta' => [],
                ];
            }

            if ($row->segmento_codsegmento) {
                $rutas[$rutaId]['detalles_ruta'][] = [
                    'id' => $row->segmento_codsegmento,
                    'nombre' => $row->segmento_nombre ?? "Segmento {$row->segmento_codsegmento}",
                    'color' => $row->segmento_color ?? '#cccccc',
                    'mensaje' => $row->mensaje ?? '',
                    'velocidadPermitida' => (float) ($row->velocidadPermitida ?? 0),
                ];
            }
        }

        // Reindexar el array para que sea consecutivo
        $rutas = array_values($rutas);

        return response()->json($rutas, 200);
    }



    public function show($id)
    {
        $detalles = DB::table('ruta as r')
            ->leftJoin('detalleRuta as d', 'r.codruta', '=', 'd.ruta_codruta')
            ->leftJoin('segmento as s', 'd.segmento_codsegmento', '=', 's.codsegmento')
            ->where('r.codruta', $id)
            ->select(
                'r.codruta as ruta_id',
                'r.nombre as ruta_nombre',
                'd.segmento_codsegmento',
                's.nombre as segmento_nombre',
                'd.mensaje',
                'd.velocidadPermitida'
            )
            ->get();

        // Si no hay segmentos, devolver array vacío
        if ($detalles->isEmpty()) {
            return response()->json([], 200);
        }

        $segmentos = $detalles->map(fn($d) => [
            'id' => $d->segmento_codsegmento,
            'nombre' => $d->segmento_nombre ?? "Segmento {$d->segmento_codsegmento}",
            'mensaje' => $d->mensaje ?? '',
            'velocidad' => (float) ($d->velocidadPermitida ?? 0),
        ]);

        // Devuelve directamente el array de segmentos
        return response()->json($segmentos, 200);
    }


    public function showID($id)
    {
        // Obtener la ruta principal
        $ruta = DB::table('ruta')
            ->where('codruta', $id)
            ->select('codruta', 'nombre', 'tipo', 'icono', 'limiteGeneral')
            ->first();

        if (!$ruta) {
            return response()->json(['error' => 'Ruta no encontrada'], 404);
        }

        // Obtener los detalles de la ruta con los segmentos asociados y su color
        $detalles = DB::table('detalleRuta as d')
            ->leftJoin('segmento as s', 'd.segmento_codsegmento', '=', 's.codsegmento')
            ->where('d.ruta_codruta', $id)
            ->select(
                'd.iddetalleRuta as detalle_id', // corregido aquí
                'd.segmento_codsegmento',
                'd.mensaje',
                'd.velocidadPermitida',
                's.nombre as segmento_nombre',
                's.color as segmento_color',
                's.cordenadas'
            )
            ->get();

        // Estructurar los detalles de la ruta
        $detallesRuta = $detalles->map(function ($d) {
            return [
                'id' => $d->segmento_codsegmento,
                'nombre' => $d->segmento_nombre ?? "Segmento {$d->segmento_codsegmento}",
                'mensaje' => $d->mensaje ?? '',
                'velocidadPermitida' => (float) ($d->velocidadPermitida ?? 0),
                'color' => $d->segmento_color ?? '#ffffff',
                'cordenadas' => json_decode($d->cordenadas) ?? []
            ];
        });

        // Armar la respuesta final
        return response()->json([
            'id' => $ruta->codruta,
            'nombre' => $ruta->nombre,
            'tipo' => $ruta->tipo,
            'icono' => $ruta->icono,
            'limiteGeneral' => $ruta->limiteGeneral,
            'detalles_ruta' => $detallesRuta,
        ], 200);
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
            $ruta = Ruta::with('detallesRuta')->findOrFail($id);

            $ruta->update($validated);

            if ($request->hasFile('icono')) {
                if ($ruta->icono) {
                    Storage::disk('public')->delete($ruta->icono);
                }
                $ruta->icono = $request->file('icono')->store('rutas/iconos', 'public');
                $ruta->save();
            }

            if (!empty($validated['detalles'])) {
                // Preparar datos
                $detallesData = array_map(fn($d) => [
                    'ruta_codruta' => $ruta->codruta,
                    'segmento_codsegmento' => $d['segmento_codsegmento'],
                    'velocidadPermitida' => $d['velocidadPermitida'],
                    'mensaje' => $d['mensaje'] ?? null,
                ], $validated['detalles']);

                $ruta->detallesRuta()->delete();
                $ruta->detallesRuta()->insert($detallesData);
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

            if (!empty($ruta->icono) && Storage::disk('public')->exists($ruta->icono)) {
                Storage::disk('public')->delete($ruta->icono);
            }

            // Verificar que la tabla de detalles exista antes de borrar
            if (Schema::hasTable('detalle_ruta')) {
                DB::table('detalle_ruta')->where('ruta_codruta', $id)->delete();
            } elseif (Schema::hasTable('detalleRuta')) {
                DB::table('detalleRuta')->where('ruta_codruta', $id)->delete();
            }

            $ruta->delete();

            DB::commit();
            return response()->json(['message' => 'Ruta eliminada exitosamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la ruta',
                'error' => $e->getMessage(),
                'linea' => $e->getLine()
            ], 500);
        }
    }



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


    public function verificarRuta($idRuta)
    {
        // Suponiendo que tienes un modelo Asignacion
        $existe = Asignacion::where('ruta_codruta', $idRuta)->exists();

        if ($existe) {
            return response()->json([
                'success' => true,
                'mensaje' => 'La ruta existe en la tabla asignacion'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'mensaje' => 'La ruta no existe en la tabla asignacion'
            ]);
        }
    }
}
