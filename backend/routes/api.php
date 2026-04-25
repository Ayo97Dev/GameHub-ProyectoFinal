<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\SaveController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/telemetry', [TelemetryController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{slug}', [GameController::class, 'show']);
Route::get('/leaderboard/{slug}', [StatsController::class, 'leaderboard'])->middleware('connect4.leaderboard.auth');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/password', [UserController::class, 'updatePassword']);

    // Game engine routes (por slug)
    Route::post('/games/{slug}/play', [GameController::class, 'play']);
    Route::post('/games/{slug}/action', [GameController::class, 'action'])->middleware('game.throttle');
    Route::get('/games/{slug}/load', [GameController::class, 'load']);
    Route::post('/games/{slug}/save', [GameController::class, 'save']);
    Route::post('/games/{slug}/complete', [GameController::class, 'complete']);
    Route::delete('/games/{slug}/reset', [GameController::class, 'reset']);

    // Save (mantener compatibilidad, usa gameId)
    Route::post('/games/{gameId}/save', [SaveController::class, 'store']);
    Route::get('/games/{gameId}/save', [SaveController::class, 'get']);

    // Stats (mantener compatibilidad)
    Route::post('/games/{gameId}/stats', [StatsController::class, 'update']);

    // Achievements
    Route::get('/achievements', [AchievementController::class, 'index']);
    Route::get('/achievements/{slug}', [AchievementController::class, 'byGame']);

    // Inventory
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index']);
    Route::post('/inventory/update', [\App\Http\Controllers\InventoryController::class, 'update']);
    Route::post('/inventory/sync', [\App\Http\Controllers\InventoryController::class, 'sync']);
});
