<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WialonSidController;
use App\Http\Controllers\Api\WialonController;
use App\Http\Controllers\Api\SegmentoController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Acceso\AuthController;
use App\Http\Controllers\Api\UsuarioController;

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





