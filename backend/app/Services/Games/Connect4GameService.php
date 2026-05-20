<?php

namespace App\Services\Games;

/**
 * CONNECT 4 SERVICE
 * 
 * Este juego es mayoritariamente CLIENT-SIDE. 
 * El servicio solo gestiona el contador de victorias/derrotas para el leaderboard.
 */
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

    /**
     * ACCIONES
     * Connect4 no procesa acciones en el servidor (el tablero se gestiona en Vue).
     */
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

    /**
     * METADATOS PARA LOGROS
     */
    public function getGameMetadata(array $state): array
    {
        return [
            'wins' => (int) ($state['wins'] ?? 0),
            'losses' => (int) ($state['losses'] ?? 0),
        ];
    }
}
