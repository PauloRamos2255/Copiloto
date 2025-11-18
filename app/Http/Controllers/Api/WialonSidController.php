<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

class WialonSidController extends Controller
{
    public function obtenerSID()
    {
        try {
            $token = env('WIALON_TOKEN');
            $cacheKey = 'wialon_sid';
            $cacheTime = 180; // 3 minutos


            if (Cache::has($cacheKey)) {
                return response()->json([
                    'success' => true,
                    'sid' => Cache::get($cacheKey),
                    'cached' => true
                ]);
            }

            $url = "https://hst-api.wialon.com/wialon/ajax.html";
            $params = [
                'svc' => 'token/login',
                'params' => json_encode(['token' => $token]),
            ];


            $response = Http::withOptions([
                'verify' => false
            ])->asForm()->post($url, $params);

            $data = $response->json();

            if (isset($data['eid'])) {
                $sid = $data['eid'];
                Cache::put($cacheKey, $sid, $cacheTime); // Guardar en cache

                return response()->json([
                    'success' => true,
                    'sid' => $sid,
                    'cached' => false
                ]);
            }

            // ⚠️ Error si no se obtuvo el SID
            return response()->json([
                'success' => false,
                'error' => 'No se pudo obtener el SID',
                'response' => $data
            ]);

        } catch (\Exception $e) {
            // ⚠️ Capturar errores de conexión o SSL
            return response()->json([
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage()
            ], 500);
        }
    }
}
