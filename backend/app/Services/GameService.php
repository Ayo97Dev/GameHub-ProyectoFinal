<?php

namespace App\Services;

/**
 * BASE GAME SERVICE
 * 
 * Clase abstracta que define el contrato para todos los juegos de la plataforma.
 * Proporciona métodos comunes para persistencia, sesiones y telemetría.
 */
use App\Models\Game;
use App\Models\GameSave;
use App\Models\GameSession;
use App\Models\GameStat;
use App\Models\User;

abstract class GameService
{
    public function __construct(
        protected User $user,
        protected Game $game,
    ) {}

    /**
     * PERSISTENCIA DE PROGRESO
     * Guarda el estado serializado (JSON) y actualiza el récord personal (high score).
     */
    public function saveProgress(array $state, int $score, int $playtime = 0): GameSave
    {
        $save = GameSave::updateOrCreate(
            ['user_id' => $this->user->id, 'game_id' => $this->game->id],
            ['payload' => $state]
        );

        $stat = GameStat::firstOrNew([
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
        ]);

        // Solo actualizamos si la puntuación actual supera el récord histórico.
        if ($score > $stat->high_score) {
            $stat->high_score = $score;
        }

        $stat->time_played += $playtime;
        $stat->last_played_at = now();
        $stat->save();

        return $save;
    }

    /**
     * CARGA DE PROGRESO
     */
    public function loadProgress(): ?GameSave
    {
        return GameSave::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->first();
    }

    /**
     * GESTIÓN DE SESIONES
     * Crea una nueva sesión activa. Si ya existía una abierta, la marca como ABANDONADA.
     */
    public function createSession(array $initialState): GameSession
    {
        // Limpieza de sesiones huérfanas
        GameSession::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'abandoned', 'ended_at' => now()]);

        return GameSession::create([
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
            'session_data' => $initialState,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * METADATOS DE LOGROS
     * Cada juego debe devolver un array con sus métricas específicas 
     * (ej: número de clicks, piezas colocadas) para que el AchievementService las evalúe.
     */
    public function getGameMetadata(array $state): array
    {
        return [];
    }

    /**
     * FINALIZACIÓN DE SESIÓN
     * Cierra el ciclo de juego y dispara la comprobación de logros.
     */
    public function completeSession(GameSession $session, int $finalScore, int $duration): array
    {
        $session->update([
            'score' => $finalScore,
            'duration_seconds' => $duration,
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        $this->saveProgress($session->session_data ?? [], $finalScore, $duration);

        // EVALUACIÓN DE LOGROS
        $achievementService = new AchievementService;
        $triggerData = array_merge(
            ['score' => $finalScore, 'duration' => $duration],
            $this->getGameMetadata($session->session_data ?? [])
        );

        $newAchievements = $achievementService->checkAndUnlock(
            $this->user,
            $this->game->id,
            $triggerData
        );

        return [
            'score' => $finalScore,
            'achievements_unlocked' => $newAchievements,
        ];
    }

    /**
     * CONTRATO OBLIGATORIO PARA SUBCLASES
     */
    abstract public function executeAction(string $action, array $payload): array;
    abstract protected function validateAction(string $action, array $payload): bool;
    abstract public function getInitialState(): array;
}
