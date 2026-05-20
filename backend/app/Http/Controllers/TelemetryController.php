<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * TELEMETRY CONTROLLER
 * 
 * Orquestador de la monitorización en tiempo real.
 * Proporciona datos sobre usuarios activos, sesiones de juego y estado del servidor.
 */
class TelemetryController extends Controller
{
    /**
     * OBTENER TELEMETRÍA GLOBAL
     * Genera un reporte del estado actual del ecosistema (Uptime, Usuarios, Sesiones).
     */
    public function index(): JsonResponse
    {
        // Simulamos el "Uptime" del servidor usando la fecha de inicio guardada en caché
        // (Esto representa cuánto tiempo lleva el servicio activo)
        $startTime = Cache::rememberForever('app_service_start_time', function () {
            return now()->timestamp;
        });
        
        $uptimeSeconds = now()->timestamp - $startTime;
        
        // Contamos tokens usados en los últimos 15 minutos como "usuarios activos"
        $activeUsers = PersonalAccessToken::where('last_used_at', '>=', now()->subMinutes(15))->count();
        
        // Sesiones de juego activas (en progreso y empezadas hace menos de 2 horas)
        $activeGameSessions = GameSession::where('status', 'in_progress')
            ->where('started_at', '>=', now()->subHours(2))
            ->count();

        // Telemetría por juego específico
        $games = Game::active()->get();
        $gamesTelemetry = $games->mapWithKeys(function ($game) {
            return [$game->slug => GameSession::where('game_id', $game->id)
                ->where('status', 'in_progress')
                ->where('started_at', '>=', now()->subHours(2))
                ->count()];
        });

        return response()->json([
            'active_users' => $activeUsers,
            'active_sessions' => $activeGameSessions,
            'games_telemetry' => $gamesTelemetry,
            'server_uptime' => $uptimeSeconds,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
