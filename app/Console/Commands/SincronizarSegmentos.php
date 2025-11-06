<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Segmento;
use App\Http\Controllers\Api\WialonSidController;

class SincronizarSegmentos extends Command
{
    protected $signature = 'app:sincronizar-segmentos';
    protected $description = 'Sincroniza los segmentos (geocercas) desde la API de Wialon con la base de datos local.';

    public function handle()
    {
        $this->info("🚀 Iniciando sincronización de segmentos...");

        $host = env('WIALON_HOST');
        $itemId = env('WIALON_ITEM_ID');

        // Obtener SID
        $sidController = new WialonSidController();
        $sidData = $sidController->obtenerSid();
        $sid = isset($sidData->getData()->sid) ? $sidData->getData()->sid : null;

        if (!$sid) {
            $this->error("❌ No se pudo obtener SID válido desde Wialon.");
            return 1;
        }

        // Llamada a la API
        $params = json_encode(['itemId' => (int)$itemId]);
        $url = "$host?svc=resource/get_zone_data&params=" . urlencode($params) . "&sid=$sid";
        $response = Http::withOptions(['verify' => false])->get($url);

        if ($response->failed()) {
            $this->error("❌ Error al obtener datos desde Wialon.");
            return 1;
        }

        $data = $response->json();

        if (!is_array($data) || isset($data['error'])) {
            $this->error("⚠️ Error en la respuesta de la API: " . ($data['reason'] ?? 'Desconocido'));
            return 1;
        }

        if (empty($data)) {
            $this->warn("⚠️ No se encontraron segmentos en la respuesta.");
            return 0;
        }

        $this->info("🌀 Procesando segmentos recibidos...");

        foreach ($data as $segmento) {
            if (!is_array($segmento) || !isset($segmento['id'])) {
                $this->warn("⚠️ Segmento inválido detectado y omitido.");
                continue;
            }

            $id = $segmento['id'];
            $nombre = $segmento['n'] ?? 'Sin nombre';
            $color = isset($segmento['c'])
                ? sprintf("#%06X", $segmento['c'] & 0xFFFFFF)
                : '#000000';

            // Coordenadas
            $coordenadas = [];
            if (isset($segmento['p']) && is_array($segmento['p'])) {
                foreach ($segmento['p'] as $punto) {
                    if (isset($punto['x'], $punto['y'])) {
                        $coordenadas[] = [
                            'lat' => (float) $punto['y'],
                            'lng' => (float) $punto['x'],
                        ];
                    }
                }
            }

            // Bounds
            $bounds = $segmento['b'] ?? [
                'min_x' => 0,
                'min_y' => 0,
                'max_x' => 0,
                'max_y' => 0,
            ];

            // Buscar si existe el registro
            $registro = Segmento::find($id);

            if (!$registro) {
                Segmento::create([
                    'codsegmento' => $id,
                    'nombre' => $nombre,
                    'color' => $color,
                    'cordenadas' => $coordenadas,
                    'bounds' => $bounds,
                ]);
                $this->info("🆕 Segmento '{$nombre}' creado.");
                continue;
            }

            // Comparar campo por campo
            $camposActualizados = [];

            if ($registro->nombre !== $nombre) {
                $camposActualizados['nombre'] = $nombre;
            }

            if ($registro->color !== $color) {
                $camposActualizados['color'] = $color;
            }

            if (json_encode($registro->cordenadas) !== json_encode($coordenadas)) {
                $camposActualizados['cordenadas'] = $coordenadas;
            }

            if (json_encode($registro->bounds) !== json_encode($bounds)) {
                $camposActualizados['bounds'] = $bounds;
            }

            // Si hay cambios, se actualiza
            if (!empty($camposActualizados)) {
                $registro->update($camposActualizados);
                $this->info("♻️ Segmento '{$nombre}' actualizado (".implode(', ', array_keys($camposActualizados)).").");
            } else {
                $this->line("✔️ Segmento '{$nombre}' sin cambios.");
            }
        }

        $this->info("✅ Sincronización completada con éxito.");
    }
}
