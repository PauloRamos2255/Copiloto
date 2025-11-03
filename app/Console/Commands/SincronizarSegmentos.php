<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Segmento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\WialonSidController;
use Illuminate\Support\Facades\Cache;

class SincronizarSegmentos extends Command
{
    protected $signature = 'app:sincronizar-segmentos';
    protected $description = 'Sincroniza automáticamente los segmentos desde Wialon a la base de datos cada 30 segundos';

    public function handle()
    {
        while (true) {
            $this->sincronizar();
            sleep(10); // Espera 30 segundos antes de volver a ejecutar
        }
    }

    private function sincronizar()
    {
        $itemId = 402037903;

        try {
            $sid = Cache::remember('wialon_sid', 30 * 60, function () {
                $sidController = new WialonSidController();
                $sidResponse = $sidController->obtenerSID();

                if (!$sidResponse || !$sidResponse->getData()->success) {
                    Log::error('No se pudo obtener SID', ['data' => optional($sidResponse)->getData()]);
                    return null;
                }

                return $sidResponse->getData()->sid ?? null;
            });

            if (!$sid) {
                $this->error('SID vacío o inválido.');
                return self::FAILURE;
            }

            $response = Http::withOptions(['verify' => false])
                ->asForm()
                ->post('https://hst-api.wialon.com/wialon/ajax.html', [
                    'svc' => 'resource/get_zone_data',
                    'params' => json_encode(['itemId' => $itemId]),
                    'sid' => $sid,
                ]);

            if (!$response->successful()) {
                Log::error('Error HTTP al conectar con Wialon', ['status' => $response->status()]);
                $this->error('Error HTTP al conectar con Wialon.');
                return self::FAILURE;
            }

            $zonas = $response->json();

            if (isset($zonas['error']) || empty($zonas)) {
                Log::error('Error o respuesta vacía al obtener zonas', ['data' => $zonas]);
                $this->error('Error o respuesta vacía al obtener zonas desde Wialon.');
                return self::FAILURE;
            }

            $dataParaUpsert = [];

            foreach ($zonas as $z) {
                if (!isset($z['id'])) continue;

                $color = '#FFFFFF';
                if (isset($z['c'])) {
                    if (is_numeric($z['c'])) {
                        $color = sprintf('#%06X', $z['c']);
                    } elseif (is_string($z['c'])) {
                        $color = str_starts_with($z['c'], '#') ? $z['c'] : '#' . ltrim($z['c'], '#');
                    }
                }

                $coordenadas = null;
                if (!empty($z['p']) && is_array($z['p'])) {
                    $uniquePoints = [];
                    foreach ($z['p'] as $p) {
                        if (!isset($p['x'], $p['y'])) continue;
                        $uniquePoints["{$p['x']}-{$p['y']}"] = array_filter($p, fn($v) => $v !== 0 && $v !== null);
                    }
                    $coordenadas = array_values($uniquePoints);
                }

                $dataParaUpsert[] = [
                    'codsegmento' => $z['id'],
                    'nombre'      => $z['n'] ?? 'Sin nombre',
                    'color'       => $color,
                    'cordenadas'  => json_encode($coordenadas),
                    'bounds'      => json_encode($z['b'] ?? null),
                    'updated_at'  => now(),
                ];
            }

            Segmento::upsert(
                $dataParaUpsert,
                ['codsegmento'],
                ['nombre', 'color', 'cordenadas', 'bounds', 'updated_at']
            );

            $total = count($dataParaUpsert);

            Log::info('Sincronización automática completada', ['total' => $total]);
            $this->info("Sincronización completada correctamente. Total zonas: {$total}");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('Error sincronizando segmentos', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
            ]);
            $this->error('Error sincronizando segmentos: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
    
}
