<?php

namespace App\Services\Games;

use App\Services\GameService;

class TowerDefenseGameService extends GameService
{
    public function getInitialState(): array
    {
        return [
            'lives' => 20,
            'gold' => 150,
            'wave' => 1,
            'waveActive' => false,
            'gameOver' => false,
            'towers' => [],
            'enemies' => [],
        ];
    }

    public function executeAction(string $action, array $payload): array
    {
        return [
            'error' => 'Action not supported for this game',
            'code' => 'ACTION_NOT_SUPPORTED',
        ];
    }

    protected function validateAction(string $action, array $payload): bool
    {
        return false;
    }
}
