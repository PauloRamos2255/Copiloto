<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Acceso\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 🔹 Página principal (Login)
Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

// 🔹 Acción de inicio de sesión
Route::post('/acceso', [AuthController::class, 'acceso'])->name('acceso');

// 🔹 Rutas protegidas (solo autenticados)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/zona', fn() => Inertia::render('ZonaComponent'))->name('zona');
    Route::get('/mapa', fn() => Inertia::render('MapaComponent'))->name('mapa');
    Route::get('/listasegmento', fn() => Inertia::render('TablaSegmento'))->name('segmento');
});

// 🔹 Configuración adicional
require __DIR__ . '/settings.php';
