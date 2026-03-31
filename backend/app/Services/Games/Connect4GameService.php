<?php

namespace App\Services\Games;

use App\Services\GameService;

class Connect4GameService extends GameService
{
    public function getInitialState(): array
    {
        return [
            'wins' => 0,
            'losses' => 0,
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
