<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeoController extends Controller
{
    // GET /api/geocode?address=...
    public function geocode(Request $request)
    {
       

        $key = config('services.google_maps.server_key');

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'key' => $key,
        ]);

        return $response->json();
    }
}
