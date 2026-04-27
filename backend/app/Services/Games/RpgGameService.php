<?php

namespace App\Services\Games;

use App\Services\GameService;

class RpgGameService extends GameService
{
    /**
     * RPG execution is primarily handled on the frontend.
     * We use this service mainly for saving, loading, and metadata extraction.
     */
    public function executeAction(string $action, array $payload): array
    {
        // For RPG, most logic is frontend-side. 
        // Actions here could be used for server-side validation if needed.
        return ['success' => true];
    }

    protected function validateAction(string $action, array $payload): bool
    {
        return true;
    }

    public function getInitialState(): array
    {
        return [
            'floor' => 1,
            'roomInFloor' => 1,
            'gold' => 0,
            'hero' => null,
            'log' => ['Entrando en el Abismo Infinito...'],
            'phase' => 'classSelect'
        ];
    }

    /**
     * Extract metadata for achievement checking.
     */
    public function getGameMetadata(array $state): array
    {
        $hero = $state['hero'] ?? null;
        
        return [
            'floor' => $state['floor'] ?? 1,
            'gold_run' => $state['gold'] ?? 0,
            'level' => $hero['level'] ?? 1,
            'class' => $hero['classId'] ?? 'none',
            'max_hp' => $hero['maxHp'] ?? 0,
            'attack' => $hero['attack'] ?? 0,
            'defense' => $hero['defense'] ?? 0,
            'magic_attack' => $hero['magicAttack'] ?? 0,
            'magic_defense' => $hero['magicDefense'] ?? 0,
            'speed' => $hero['speed'] ?? 0,
            'bosses_defeated' => $this->calculateBossesDefeated($state['floor'] ?? 1),
        ];
    }

    private function calculateBossesDefeated(int $floor): int
    {
        // Floor 10, 20, 30... have bosses.
        return floor($floor / 10);
    }
}
