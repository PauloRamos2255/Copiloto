<?php

use App\Http\Controllers\Ruta\RutaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WialonSidController;
use App\Http\Controllers\Api\WialonController;
use App\Http\Controllers\Api\SegmentoController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Acceso\AuthController;
use App\Http\Controllers\Api\AsignacionController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Moviapi\MovilUsuarioController;
use App\Http\Controllers\Api\ActualizacionController;
use Illuminate\Http\Request;

// routes/api.php
Route::get('/obtener-sid', [WialonSidController::class, 'obtenerSid']);
Route::post('/zone-data', [WialonController::class, 'obtenerZonas']);
Route::get('/segmentos', [SegmentoController::class, 'index']);
Route::post('/segmentos', [SegmentoController::class, 'store']);
Route::post('/segmentos/guardar', [SegmentoController::class, 'guardar']);
Route::get('/geocode', [GeoController::class, 'geocode']);
Route::post('/acceso', [AuthController::class, 'acceso']);
Route::get('/usuarios', [UsuarioController::class, 'index']);
Route::get('/conductores', [UsuarioController::class, 'listarTipoC']);
Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
Route::get('/verificar_usuario/{id}', [UsuarioController::class, 'verificarUsuario']);
Route::post('/usuarios', [UsuarioController::class, 'store']);
Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
Route::get( 'actualizacion/pendiente/{codusuario}',[UsuarioController::class, 'tieneActualizacionPendiente']);
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
Route::get('/verificaruta/{id}', [RutaController::class, 'verificarRuta']);
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
Route::get('/asignacion', [AsignacionController::class, 'index']);
Route::get('/asignacion/{id}', [AsignacionController::class, 'obtenerRutasPorUsuario']);
Route::get('/asignacion_segmen', [AsignacionController::class, 'obtenerTodasLasRutas']);
Route::post('/asignacion_save', [AsignacionController::class, 'guardarAsignaciones']);
Route::put('/asignacion_update', [AsignacionController::class, 'editarAsignaciones']);
Route::delete('/asignacion_delete/{id}', [AsignacionController::class, 'eliminarAsignacionesUsuario']);
Route::get('/asignacion_get/{id}', [AsignacionController::class, 'obtenerRutasPorConductor']);
Route::get('/conductor_sin_ruta', [AsignacionController::class, 'conductoresSinRutas']);
Route::get('/asignacion_activo/{id}', [AsignacionController::class, 'validarAsignacionActivaPorUsuario']);


//Movil
Route::post('/conductor_login', [MovilUsuarioController::class, 'loginConductor']);
Route::post('/validar_usuario', [MovilUsuarioController::class, 'validarUsuario']);
Route::get('/obtener_rutas_conductor/{id}', [MovilUsuarioController::class, 'obtenerRutasConductor']);
Route::post('/actualizacion_save', [ActualizacionController::class, 'registrar']);
Route::put('actualizacion_update', [ActualizacionController::class, 'actualizarPorId']);
Route::put('actualizacion_error/{usuarioId}', [ActualizacionController::class, 'finalizarActualizacionPorUsuario']);
Route::post('/logout/{id}', [MovilUsuarioController::class, 'logoutConductor']);
Route::get('/obtenerrutas/{idRuta}', [MovilUsuarioController::class, 'obtenerSegmentos']);
Route::post('/historicoViaje_save', [MovilUsuarioController::class, 'insertarViaje']);
Route::put('/historicoViaje_update', [MovilUsuarioController::class, 'actualizarEstadoPorViaje']);
Route::post('/evento_save', [MovilUsuarioController::class, 'insertarEvento']);
Route::put('/evento_update', [MovilUsuarioController::class, 'EventoUpdateFin']);




