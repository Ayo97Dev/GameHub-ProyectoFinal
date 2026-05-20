<?php

/**
 * API ROUTES CONFIGURATION
 * 
 * Este archivo define todos los endpoints de la API de GameHub.
 * Está dividido en rutas públicas (acceso libre) y protegidas (requieren token).
 */

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\SaveController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/**
 * RUTAS PÚBLICAS
 * No requieren autenticación. Incluye registro, login y consulta de catálogo.
 */
Route::get('/telemetry', [TelemetryController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Consulta de catálogo de juegos
Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{slug}', [GameController::class, 'show']);

// Ranking público (con middleware de seguridad específico para Connect4)
Route::get('/leaderboard/{slug}', [StatsController::class, 'leaderboard'])->middleware('connect4.leaderboard.auth');

/**
 * RUTAS PROTEGIDAS (Middleware Sanctum)
 * Estas rutas requieren un Bearer Token válido obtenido mediante login/register.
 */
Route::middleware('auth:sanctum')->group(function () {
    // Gestión de Sesión y Perfil
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/password', [UserController::class, 'updatePassword']);

    /**
     * MOTOR DE JUEGOS (Por Slug)
     * Centralizamos las acciones de juego bajo un middleware de throttling 
     * para prevenir abusos/hacking de puntuaciones.
     */
    Route::post('/games/{slug}/play', [GameController::class, 'play']);
    Route::post('/games/{slug}/action', [GameController::class, 'action'])->middleware('game.throttle');
    Route::get('/games/{slug}/load', [GameController::class, 'load']);
    Route::post('/games/{slug}/save', [GameController::class, 'save']);
    Route::post('/games/{slug}/complete', [GameController::class, 'complete']);
    Route::delete('/games/{slug}/reset', [GameController::class, 'reset']);

    // Persistencia de estados (Legacy support)
    Route::post('/games/{gameId}/save', [SaveController::class, 'store']);
    Route::get('/games/{gameId}/save', [SaveController::class, 'get']);

    // Estadísticas
    Route::post('/games/{gameId}/stats', [StatsController::class, 'update']);

    // Logros (Achievements)
    Route::get('/achievements', [AchievementController::class, 'index']);
    Route::get('/achievements/{slug}', [AchievementController::class, 'byGame']);

    // Inventario Global
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index']);
    Route::post('/inventory/update', [\App\Http\Controllers\InventoryController::class, 'update']);
    Route::post('/inventory/sync', [\App\Http\Controllers\InventoryController::class, 'sync']);
});
