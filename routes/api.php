<?php

use App\Http\Controllers\Ruta\RutaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WialonSidController;
use App\Http\Controllers\Api\WialonController;
use App\Http\Controllers\Api\SegmentoController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Acceso\AuthController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Http\Request;

// routes/api.php
Route::get('/obtener-sid', [WialonSidController::class, 'obtenerSid']);
Route::post('/zone-data', [WialonController::class, 'obtenerZonas']);
Route::get('/segmentos', [SegmentoController::class, 'index']);
Route::post('/segmentos', [SegmentoController::class, 'store']);
Route::post('/segmentos/guardar', [SegmentoController::class, 'guardar']);
Route::get('/geocode', [GeoController::class, 'geocode']);
Route::post('/acceso', [AuthController::class, 'acceso']);
Route::get('/users', [UsuarioController::class, 'index']);
Route::delete('/segmentos/{id}', [SegmentoController::class, 'destroy']);
Route::get('/segmentos/{id}', [SegmentoController::class, 'show']);
Route::post('/segmentos/sincronizar', [SegmentoController::class, 'sincronizar']);
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->tokens()->delete();
    return response()->json(['message' => 'Sesión cerrada correctamente']);
});
Route::get('/rutas', [RutaController::class, 'index']);
Route::get('/rutas/{id}', [RutaController::class, 'show']);
Route::post('/rutas', [RutaController::class, 'store']);
Route::post('/duplicar', [RutaController::class, 'duplicar']);
Route::put('/rutas/{id}', [RutaController::class, 'update']);
Route::delete('/rutas/{id}', [RutaController::class, 'destroy']);
Route::get('/detallesRuta/{codruta}', [RutaController::class, 'detallesRuta']);




