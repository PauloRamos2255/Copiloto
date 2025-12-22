<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Api\UsuarioController;

Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');


Route::post('/acceso', [UsuarioController::class, 'login'])->name('acceso');

Route::post('/logout', [UsuarioController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/segmentos', fn() => Inertia::render('MapaComponent'))->name('mapa');
    Route::get('/listasegmento', fn() => Inertia::render('TablaSegmento'))->name('segmento');
    Route::get('/usuarios', fn() => Inertia::render('TablaUsuarios'))->name('usuarios');
    Route::get('/rutas', fn() => Inertia::render('Rutas'))->name('rutas');
    Route::get('/empresa', fn() => Inertia::render('Empresa'))->name('empresa');
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/asignar', fn() => Inertia::render('AsignarRuta'))->name('asignar');
});

Route::get('/{any}', fn() => Inertia::render('NotFound'))
    ->where('any', '.*');
