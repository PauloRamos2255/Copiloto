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
        $this->info("Iniciando sincronización de segmentos...");

        $host = env('WIALON_HOST');
        $itemId = env('WIALON_ITEM_ID');

        // Obtener SID
        $sidController = new WialonSidController();
        $sidData = $sidController->obtenerSid();
        $sid = $sidData->getData()->sid ?? null;

        if (!$sid) {
            $this->error("No se pudo obtener SID válido desde Wialon.");
            return 1;
        }

        // Llamada a la API
        $params = json_encode(['itemId' => (int)$itemId]);
        $url = "$host?svc=resource/get_zone_data&params=" . urlencode($params) . "&sid=$sid";
        $response = Http::withOptions(['verify' => false])->get($url);

        if ($response->failed()) {
            $this->error("Error al obtener datos desde Wialon.");
            return 1;
        }

        $data = $response->json();

        if (!is_array($data) || isset($data['error'])) {
            $this->error("Error en la respuesta de la API: " . ($data['reason'] ?? 'Desconocido'));
            return 1;
        }

        if (empty($data)) {
            $this->warn("No se encontraron segmentos en la respuesta.");
            return 0;
        }

        $this->info("🌀 Procesando segmentos recibidos...");

        foreach ($data as $segmento) {
            if (!is_array($segmento) || !isset($segmento['id'])) {
                $this->warn("Segmento inválido detectado y omitido.");
                continue;
            }

            $id = $segmento['id'];
            $nombre = $segmento['n'] ?? 'Sin nombre';
            $color = isset($segmento['c'])
                ? sprintf("#%06X", $segmento['c'] & 0xFFFFFF)
                : '#000000';

            // Normalizar coordenadas manteniendo x, y, z
            $coordenadas = [];
            $raw = $segmento['coordenadas'] ?? $segmento['cordenadas'] ?? $segmento['p'] ?? [];

            // Si viene como string JSON
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : [];
            }

            if (is_array($raw)) {
                $seen = [];
                foreach ($raw as $punto) {
                    $x = $y = $z = null;

                    // Formato {x, y, z?}
                    if (isset($punto['x'], $punto['y'])) {
                        $x = (float)$punto['x'];
                        $y = (float)$punto['y'];
                        $z = isset($punto['z']) ? (float)$punto['z'] : null;
                    }
                    // Formato {lon, lat, z?}
                    elseif (isset($punto['lon'], $punto['lat'])) {
                        $x = (float)$punto['lon'];
                        $y = (float)$punto['lat'];
                        $z = isset($punto['z']) ? (float)$punto['z'] : null;
                    }
                    // Formato array [x, y, z?]
                    elseif (is_array($punto) && count($punto) >= 2) {
                        $x = (float)$punto[0];
                        $y = (float)$punto[1];
                        $z = isset($punto[2]) ? (float)$punto[2] : null;
                    }

                    if ($x !== null && $y !== null) {
                        $key = $x . '-' . $y . '-' . $z;
                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $coord = ['x' => $x, 'y' => $y];
                            if ($z !== null) $coord['z'] = $z;
                            $coordenadas[] = $coord;
                        }
                    }
                }
            }

            // Opcional: limitar número de coordenadas
            if (count($coordenadas) > 5000) {
                $coordenadas = array_slice($coordenadas, 0, 5000);
            }

            // Bounds
            $bounds = $segmento['b'] ?? [
                'min_x' => 0,
                'min_y' => 0,
                'max_x' => 0,
                'max_y' => 0,
            ];

            // Buscar o crear registro
            $registro = Segmento::find($id);

            $campos = [
                'codsegmento' => $id,
                'nombre' => $nombre,
                'color' => $color,
                'cordenadas' => $coordenadas,
                'bounds' => $bounds,
            ];

            if (!$registro) {
                Segmento::create($campos);
                $this->info("Segmento '{$nombre}' creado.");
                continue;
            }

            // Actualizar solo si hay cambios
            $actualizar = [];
            foreach (['nombre', 'color', 'cordenadas', 'bounds'] as $campo) {
                if (json_encode($registro->$campo) !== json_encode($campos[$campo])) {
                    $actualizar[$campo] = $campos[$campo];
                }
            }

            if (!empty($actualizar)) {
                $registro->update($actualizar);
                $this->info("Segmento '{$nombre}' actualizado (".implode(', ', array_keys($actualizar)).").");
            } else {
                $this->line("Segmento '{$nombre}' sin cambios.");
            }
        }

        $this->info("Sincronización completada con éxito.");
        return 0;
    }
}
