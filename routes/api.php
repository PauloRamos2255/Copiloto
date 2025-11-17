<?php

use App\Http\Controllers\Ruta\RutaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WialonSidController;
use App\Http\Controllers\Api\WialonController;
use App\Http\Controllers\Api\SegmentoController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Acceso\AuthController;
use App\Http\Controllers\Api\EmpresaController;
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
Route::get('/usuarios', [UsuarioController::class, 'index']);
Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
Route::post('/usuarios', [UsuarioController::class, 'store']);
Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
Route::get('/segmentos', [SegmentoController::class, 'index']);
Route::get('/segmentos/{id}', [SegmentoController::class, 'show']);
Route::put('/segmentos/{id}', [SegmentoController::class, 'update']);        
Route::delete('/segmentos/{id}', [SegmentoController::class, 'destroy']);    
Route::post('/segmentos/{id}/cascada', [SegmentoController::class, 'eliminarCascada']);
Route::get('/segmentos/{id}/detalles-rutas', [SegmentoController::class, 'verificarRutasVinculadas']);
Route::post('/segmentos/sincronizar', [SegmentoController::class, 'sincronizar']);
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->tokens()->delete();
    return response()->json(['message' => 'Sesión cerrada correctamente']);
});
Route::get('/rutas', [RutaController::class, 'index']);
Route::get('/rutas/{id}', [RutaController::class, 'show']);
Route::get('/rutasid/{id}', [RutaController::class, 'showID']);
Route::post('/rutas', [RutaController::class, 'store']);
Route::post('/duplicar', [RutaController::class, 'duplicar']);
Route::put('/rutas/{id}', [RutaController::class, 'update']);
Route::delete('/rutas/{id}', [RutaController::class, 'destroy']);
Route::get('/detallesRuta/{codruta}', [RutaController::class, 'detallesRuta']);
Route::prefix('empresas')->group(function () {
    Route::get('/', [EmpresaController::class, 'index']);
    Route::post('/', [EmpresaController::class, 'store']);
    Route::get('/{id}', [EmpresaController::class, 'show']);
    Route::put('/{id}', [EmpresaController::class, 'update']);
    Route::delete('/{id}', [EmpresaController::class, 'destroy']);
    Route::get('/{id}/usuarios', [EmpresaController::class, 'listarUsuarios']);
});



