<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Segmento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\WialonSidController;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SegmentosSyncJob;

class SincronizarSegmentos extends Command
{
    protected $signature = 'app:sincronizar-segmentos';
    protected $description = 'Sincroniza automáticamente los segmentos desde Wialon a la base de datos';

    public function handle()
    {
        $itemId = 402037903;

        try {
            // Obtener SID (usar cache para no pedirlo cada vez)
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

            // Obtener zonas desde Wialon
            $response = Http::withOptions(['verify' => false])
                ->asForm()
                ->post('https://hst-api.wialon.com/wialon/ajax.html', [
                    'svc' => 'resource/get_zone_data',
                    'params' => json_encode(['itemId' => $itemId]),
                    'sid' => $sid,
                ]);

            if (!$response->successful()) {
                Log::error('Error HTTP al conectar con Wialon', ['status' => $response->status()]);
                return self::FAILURE;
            }

            $zonas = $response->json();

            if (isset($zonas['error']) || empty($zonas)) {
                Log::error('Error o respuesta vacía al obtener zonas', ['data' => $zonas]);
                return self::FAILURE;
            }

            // Enviar las zonas al job para procesar en segundo plano
            SegmentosSyncJob::dispatch($zonas);

            $this->info("Sincronización enviada a job correctamente. Total zonas recibidas: " . count($zonas));
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
