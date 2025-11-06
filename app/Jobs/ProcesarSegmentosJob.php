<?php

namespace App\Jobs;

use App\Models\Segmento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcesarSegmentosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $segmentos;

    public function __construct(array $segmentos)
    {
        $this->segmentos = $segmentos;
    }

    public function handle(): void
    {
        foreach ($this->segmentos as $key => $segmento) {
            if (!is_array($segmento) || !isset($segmento['id'])) {
                continue;
            }

            $segmentoId = $segmento['id'];
            $nombre = $segmento['n'] ?? 'Sin nombre';
            $descripcion = $segmento['d'] ?? '';
            $color = isset($segmento['c']) ? sprintf("#%06X", $segmento['c'] & 0xFFFFFF) : '#000000';

            $coordenadas = [];
            if (isset($segmento['p']) && is_array($segmento['p'])) {
                foreach ($segmento['p'] as $punto) {
                    $coordenadas[] = [
                        'x' => $punto['x'] ?? 0,
                        'y' => $punto['y'] ?? 0
                    ];
                }
            }

            $bounds = $segmento['b'] ?? [
                'min_x' => 0,
                'min_y' => 0,
                'max_x' => 0,
                'max_y' => 0
            ];

            $hash = md5(json_encode([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'color' => $color,
                'coordenadas' => $coordenadas,
                'bounds' => $bounds
            ]));

            $registro = Segmento::find($segmentoId);

            if (!$registro) {
                Segmento::create([
                    'id' => $segmentoId,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'color' => $color,
                    'coordenadas' => json_encode($coordenadas),
                    'bounds' => json_encode($bounds),
                    'hash' => $hash
                ]);
            } elseif ($registro->hash !== $hash) {
                $registro->update([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'color' => $color,
                    'coordenadas' => json_encode($coordenadas),
                    'bounds' => json_encode($bounds),
                    'hash' => $hash
                ]);
            }
        }
    }
}
