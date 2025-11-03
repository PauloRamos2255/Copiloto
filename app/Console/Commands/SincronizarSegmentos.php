<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Segmento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\WialonSidController;

class SincronizarSegmentos extends Command
{
    /**
     * Nombre y firma del comando
     */
    protected $signature = 'app:sincronizar-segmentos';

    /**
     * Descripción del comando
     */
    protected $description = 'Sincroniza automáticamente los segmentos desde Wialon a la base de datos';

    /**
     * Ejecutar el comando
     */
    public function handle()
    {
        $itemId = 402037903; // ID fijo de tu recurso en Wialon

        try {
            // 🔹 Obtener SID automáticamente desde tu controlador
            $sidController = new WialonSidController();
            $sidResponse = $sidController->obtenerSID();

            if (!$sidResponse->getData()->success) {
                $this->error('No se pudo obtener el SID');
                Log::error('No se pudo obtener SID', ['data' => $sidResponse->getData()]);
                return 1;
            }

            $sid = $sidResponse->getData()->sid;

            // 🔹 Llamada a la API de Wialon
            $response = Http::withOptions(['verify' => false])
                ->asForm()
                ->post('https://hst-api.wialon.com/wialon/ajax.html', [
                    'svc' => 'resource/get_zone_data',
                    'params' => json_encode(['itemId' => $itemId]),
                    'sid' => $sid,
                ]);

            $zonas = $response->json();

            if (isset($zonas['error'])) {
                Log::error('Error al obtener zonas desde Wialon', ['data' => $zonas]);
                $this->error('Error al obtener zonas desde Wialon');
                return 1;
            }

            // 🔹 Guardar o actualizar en la base de datos
            foreach ($zonas as $z) {

    // 🔹 Color: convertir a hexadecimal si viene como número
    $color = '#FFFFFF';
    if (isset($z['c'])) {
        if (is_numeric($z['c'])) {
            $color = '#' . str_pad(dechex($z['c']), 6, '0', STR_PAD_LEFT);
        } elseif (is_string($z['c'])) {
            $color = (strpos($z['c'], '#') === 0) ? $z['c'] : '#' . ltrim($z['c'], '#');
        }
    }

    // 🔹 Coordenadas: depurar
    $cordenadas = null;
    if (!empty($z['p']) && is_array($z['p'])) {
        $uniquePoints = [];
        foreach ($z['p'] as $p) {
            if (!isset($p['x']) || !isset($p['y'])) continue;
            $key = $p['x'] . '-' . $p['y'];
            $uniquePoints[$key] = $p;
        }
        foreach ($uniquePoints as &$p) {
            if (isset($p['r']) && $p['r'] === 0) unset($p['r']);
        }
        $cordenadas = array_values($uniquePoints);
    }

    // 🔹 Guardar o actualizar
    Segmento::updateOrCreate(
        ['codsegmento' => $z['id']],
        [
            'nombre'      => $z['n'] ?? 'Sin nombre',
            'color'       => $color,
            'cordenadas'  => $cordenadas,
            'bounds'      => $z['b'] ?? null,
        ]
    );
}


            $total = count($zonas);
            Log::info('Sincronización automática completada', ['total' => $total]);
            $this->info("Sincronización completada. Total zonas: $total");

        } catch (\Exception $e) {
            Log::error('Error sincronizando segmentos', ['mensaje' => $e->getMessage()]);
            $this->error('Error sincronizando segmentos: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
