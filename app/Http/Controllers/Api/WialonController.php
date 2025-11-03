<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WialonController extends Controller
{
    public function obtenerZonas(Request $request)
{
    $itemId = $request->input('itemId');
    $sid = $request->input('sid');

    if (!$itemId || !$sid) {
        return response()->json(['success' => false, 'error' => 'Faltan parámetros']);
    }

    $response = Http::withOptions(['verify' => false])
        ->asForm()
        ->post('https://hst-api.wialon.com/wialon/ajax.html', [
            'svc' => 'resource/get_zone_data',
            'params' => json_encode(['itemId' => (int) $itemId]),
            'sid' => $sid,
        ]);

    $data = $response->json();


    if (isset($data['error'])) {
        return response()->json(['success' => false, 'error' => '⚠️ Error desde Wialon.', 'details' => $data]);
    }

    if (empty($data)) {
        return response()->json(['success' => true, 'zones' => []]);
    }

    return response()->json(['success' => true, 'zones' => array_values($data)]);
}

}
