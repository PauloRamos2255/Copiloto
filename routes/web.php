<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Acceso\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 🔹 Página de login (acceso público)
Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

// 🔹 Acción de inicio de sesión
Route::post('/acceso', [AuthController::class, 'acceso'])->name('acceso');

// 🔹 Cierre de sesión (requiere autenticación)
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// 🔹 Rutas protegidas (solo usuarios autenticados)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/zona', fn() => Inertia::render('ZonaComponent'))->name('zona');
    Route::get('/mapa', fn() => Inertia::render('MapaComponent'))->name('mapa');
    Route::get('/listasegmento', fn() => Inertia::render('TablaSegmento'))->name('segmento');
    Route::get('/usuarios', fn() => Inertia::render('TablaUsuarios'))->name('usuarios');
    Route::get('/rutas', fn() => Inertia::render('Rutas'))->name('rutas');
});

// 🔹 Catch-all 404 para cualquier ruta no definida
Route::get('/{any}', fn() => Inertia::render('NotFound'))
    ->where('any', '.*');
