<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Segmento;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SegmentosSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $zonas;

    public function __construct(array $zonas)
    {
        $this->zonas = $zonas;
    }

    public function handle()
    {
        $dataParaUpsert = [];

        foreach ($this->zonas as $z) {
            if (!isset($z['id'])) continue;

            // Preparar coordenadas
            $coordenadas = $z['p'] ?? [];
            $hashActual = md5(json_encode($coordenadas));
            $cacheKey = "segmento_hash_{$z['id']}";
            $hashAnterior = Cache::get($cacheKey);

            if ($hashActual === $hashAnterior) {
                continue;
            }

            Cache::put($cacheKey, $hashActual, 60 * 24); 

            // Color
            $color = '#FFFFFF';
            if (isset($z['c'])) {
                $color = is_numeric($z['c']) ? sprintf('#%06X', $z['c']) : '#' . ltrim($z['c'], '#');
            }

            // Eliminar puntos duplicados
            $uniquePoints = [];
            foreach ($coordenadas as $p) {
                if (!isset($p['x'], $p['y'])) continue;
                $uniquePoints["{$p['x']}-{$p['y']}"] = array_filter($p, fn($v) => $v !== 0 && $v !== null);
            }

            $dataParaUpsert[] = [
                'codsegmento' => $z['id'],
                'nombre'      => $z['n'] ?? 'Sin nombre',
                'color'       => $color,
                'cordenadas'  => json_encode(array_values($uniquePoints)),
                'bounds'      => json_encode($z['b'] ?? null),
                'updated_at'  => now(),
            ];
        }

        if (!empty($dataParaUpsert)) {
            Segmento::upsert(
                $dataParaUpsert,
                ['codsegmento'],
                ['nombre', 'color', 'cordenadas', 'bounds', 'updated_at']
            );
            Log::info('Segmentos sincronizados correctamente', ['total' => count($dataParaUpsert)]);
        }
    }
}
