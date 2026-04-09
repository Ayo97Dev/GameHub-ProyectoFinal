<?php

namespace App\Services\Games;

use App\Models\Game;
use App\Models\User;
use App\Services\GameService;

class BattleshipGameService extends GameService
{
    private const BOARD_SIZE = 8;

    private const SHIPS = [
        ['id' => 'carrier', 'name' => 'Portaaviones', 'size' => 4],
        ['id' => 'destroyer', 'name' => 'Destructor', 'size' => 3],
        ['id' => 'submarine', 'name' => 'Submarino', 'size' => 3],
        ['id' => 'frigate', 'name' => 'Fragata', 'size' => 2],
        ['id' => 'patrol', 'name' => 'Patrullero', 'size' => 2],
    ];

    public function __construct(User $user, Game $game)
    {
        parent::__construct($user, $game);
    }

    public function getInitialState(): array
    {
        $player = $this->buildRandomFleet();
        $enemy  = $this->buildRandomFleet();

        return [
            'playerBoard'     => $player['board'],
            'enemyBoard'      => $enemy['board'],
            'playerFleet'     => $player['fleet'],
            'enemyFleet'      => $enemy['fleet'],
            'gameStatus'      => 'playing',
            'turn'            => 'player',
            'playerShots'     => 0,
            'playerHits'      => 0,
            'enemyShots'      => 0,
            'enemyHits'       => 0,
            'enemyShipsSunk'  => 0,
            'playerShipsSunk' => 0,
            'battleLog'       => [
                [
                    'id'   => $this->makeLogId(),
                    'text' => 'Mision iniciada. Tu radar esta listo para disparar.',
                    'tone' => 'good',
                ],
            ],
            'enemyTargetQueue' => [],
            'enemyTargetSet'   => [],
        ];
    }

    public function executeAction(string $action, array $payload): array
    {
        if (!$this->validateAction($action, $payload)) {
            return ['error' => 'Invalid action', 'code' => 'INVALID_ACTION'];
        }

        return match ($action) {
            'restart' => $this->handleRestart(),
            'scan' => $this->handleScan($payload),
            'fire' => $this->handleFire($payload),
            default => ['error' => 'Unknown action', 'code' => 'UNKNOWN_ACTION'],
        };
    }

    protected function validateAction(string $action, array $payload): bool
    {
        return match ($action) {
            'restart' => true,
            'scan', 'fire' => isset($payload['x'], $payload['y']) && is_numeric($payload['x']) && is_numeric($payload['y']),
            default => false,
        };
    }

    private function handleRestart(): array
    {
        $state = $this->getInitialState();
        $this->saveProgress($state, 0, 0);

        return [
            'success' => true,
            'game_state' => $state,
        ];
    }

    private function handleScan(array $payload): array
    {
        $x = (int) $payload['x'];
        $y = (int) $payload['y'];

        if (!$this->isInBounds($x, $y)) {
            return ['error' => 'Invalid coordinate', 'code' => 'INVALID_COORDINATE'];
        }

        return [
            'success' => true,
            'coordinate' => ['x' => $x, 'y' => $y],
        ];
    }

    private function handleFire(array $payload): array
    {
        $x = (int) $payload['x'];
        $y = (int) $payload['y'];

        if (!$this->isInBounds($x, $y)) {
            return ['error' => 'Invalid coordinate', 'code' => 'INVALID_COORDINATE'];
        }

        return [
            'success' => true,
            'coordinate' => ['x' => $x, 'y' => $y],
        ];
    }

    private function buildRandomFleet(): array
    {
        $board = $this->createBoard();
        $fleet = [];

        foreach (self::SHIPS as $ship) {
            $placed = false;

            for ($attempt = 0; $attempt < 400 && !$placed; $attempt++) {
                $horizontal = (bool) random_int(0, 1);

                $startX = random_int(0, $horizontal ? self::BOARD_SIZE - $ship['size'] : self::BOARD_SIZE - 1);
                $startY = random_int(0, $horizontal ? self::BOARD_SIZE - 1 : self::BOARD_SIZE - $ship['size']);

                $coords = [];
                $collision = false;

                for ($i = 0; $i < $ship['size']; $i++) {
                    $x = $startX + ($horizontal ? $i : 0);
                    $y = $startY + ($horizontal ? 0 : $i);

                    if ($board[$y][$x]['hasShip']) {
                        $collision = true;
                        break;
                    }

                    $coords[] = ['x' => $x, 'y' => $y];
                }

                if ($collision) {
                    continue;
                }

                foreach ($coords as $coord) {
                    $board[$coord['y']][$coord['x']]['hasShip'] = true;
                    $board[$coord['y']][$coord['x']]['shipId'] = $ship['id'];
                }

                $fleet[$ship['id']] = [
                    'id' => $ship['id'],
                    'name' => $ship['name'],
                    'size' => $ship['size'],
                    'hits' => 0,
                    'sunk' => false,
                    'cells' => $coords,
                ];

                $placed = true;
            }

            if (!$placed) {
                return $this->buildRandomFleet();
            }
        }

        return [
            'board' => $board,
            'fleet' => $fleet,
        ];
    }

    private function createBoard(): array
    {
        $board = [];

        for ($y = 0; $y < self::BOARD_SIZE; $y++) {
            $row = [];
            for ($x = 0; $x < self::BOARD_SIZE; $x++) {
                $row[] = [
                    'hasShip' => false,
                    'shipId' => null,
                    'state' => 'unknown',
                ];
            }
            $board[] = $row;
        }

        return $board;
    }

    private function isInBounds(int $x, int $y): bool
    {
        return $x >= 0 && $x < self::BOARD_SIZE && $y >= 0 && $y < self::BOARD_SIZE;
    }

    private function makeLogId(): string
    {
        return (string) now()->getTimestampMs() . '-' . bin2hex(random_bytes(3));
    }
}
