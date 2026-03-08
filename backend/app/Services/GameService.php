<?php

namespace App\Services;

use App\Models\Achievement;
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
     * Guarda el progreso en game_saves y actualiza game_stats.
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

        if ($score > $stat->high_score) {
            $stat->high_score = $score;
        }

        $stat->time_played    += $playtime;
        $stat->last_played_at  = now();
        $stat->save();

        return $save;
    }

    /**
     * Carga el save del usuario para este juego.
     */
    public function loadProgress(): ?GameSave
    {
        return GameSave::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->first();
    }

    /**
     * Crea una sesión de juego con estado inicial.
     */
    public function createSession(array $initialState): GameSession
    {
        // Abandonar sesión anterior activa si existe
        GameSession::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'abandoned', 'ended_at' => now()]);

        return GameSession::create([
            'user_id'      => $this->user->id,
            'game_id'      => $this->game->id,
            'session_data' => $initialState,
            'status'       => 'in_progress',
            'started_at'   => now(),
        ]);
    }

    /**
     * Cierra la sesión, guarda progreso y comprueba logros.
     */
    public function completeSession(GameSession $session, int $finalScore, int $duration): array
    {
        $session->update([
            'score'            => $finalScore,
            'duration_seconds' => $duration,
            'status'           => 'completed',
            'ended_at'         => now(),
        ]);

        $this->saveProgress($session->session_data ?? [], $finalScore, $duration);

        $achievementService  = new AchievementService();
        $newAchievements     = $achievementService->checkAndUnlock(
            $this->user,
            $this->game->id,
            ['score' => $finalScore, 'duration' => $duration]
        );

        return [
            'score'                 => $finalScore,
            'achievements_unlocked' => $newAchievements,
        ];
    }

    abstract public function executeAction(string $action, array $payload): array;

    abstract protected function validateAction(string $action, array $payload): bool;

    abstract public function getInitialState(): array;
}
