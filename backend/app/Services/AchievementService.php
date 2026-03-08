<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;

class AchievementService
{
    /**
     * Comprueba condiciones y desbloquea los logros que correspondan.
     * triggerData debe incluir: score, upgrades (array id=>count), total_clicks,
     * prestige_level, total_upgrades_bought, max_upgrade_count.
     */
    public function checkAndUnlock(User $user, int $gameId, array $triggerData): array
    {
        $newAchievements = [];

        $achievements = Achievement::where('is_active', true)
            ->where(function ($q) use ($gameId) {
                $q->where('game_id', $gameId)->orWhereNull('game_id');
            })
            ->get();

        foreach ($achievements as $achievement) {
            if ($this->isUnlocked($user, $achievement->id)) {
                continue;
            }

            if ($achievement->condition && $this->conditionMet($achievement->condition, $triggerData)) {
                $user->achievements()->attach($achievement->id, ['earned_at' => now()]);
                $newAchievements[] = $achievement;
            }
        }

        return $newAchievements;
    }

    private function conditionMet(array $condition, array $data): bool
    {
        $field    = $condition['field']    ?? null;
        $operator = $condition['operator'] ?? null;
        $target   = $condition['value']    ?? null;

        if ($field === null) {
            return false;
        }

        // Valor a comparar según el campo
        $value = match ($field) {
            // Puntuación/balance actual
            'score'                 => $data['score'] ?? 0,
            // Duración de sesión en segundos
            'duration'              => $data['duration'] ?? PHP_INT_MAX,
            // Clics totales
            'total_clicks'          => $data['total_clicks'] ?? 0,
            // Nivel de prestige
            'prestige_level'        => $data['prestige_level'] ?? 0,
            // Total de mejoras compradas (suma de todas)
            'total_upgrades_bought' => $data['total_upgrades_bought'] ?? 0,
            // Máximo de veces que se ha comprado UNA MISMA mejora
            'max_upgrade_count'     => $data['max_upgrade_count'] ?? 0,
            // Número de veces que se ha comprado una mejora específica
            'upgrade_count'         => $data['upgrades'][$condition['upgrade_id'] ?? 0] ?? 0,
            default                 => null,
        };

        if ($value === null) {
            return false;
        }

        return match ($operator) {
            'greater_than'          => $value > $target,
            'greater_than_or_equal' => $value >= $target,
            'equal'                 => $value == $target,
            'less_than'             => $value < $target,
            'less_than_or_equal'    => $value <= $target,
            default                 => false,
        };
    }

    private function isUnlocked(User $user, int $achievementId): bool
    {
        return $user->achievements()->where('achievement_id', $achievementId)->exists();
    }
}
