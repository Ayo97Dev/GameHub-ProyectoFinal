<?php

namespace App\Services\Games;

use App\Services\GameService;

class TowerDefenseGameService extends GameService
{
    /**
     * Definición de tipos de torres disponibles.
     */
    private const TOWER_TYPES = [
        'basic' => ['name' => 'Básica', 'cost' => 30, 'range' => 2.5, 'damage' => 15, 'cooldownMax' => 20, 'color' => '#38bdf8', 'effect' => 'none'],
        'rapid' => ['name' => 'Ametralladora', 'cost' => 60, 'range' => 2, 'damage' => 4, 'cooldownMax' => 4, 'color' => '#f59e0b', 'effect' => 'fast'],
        'sniper' => ['name' => 'Francotirador', 'cost' => 100, 'range' => 5, 'damage' => 60, 'cooldownMax' => 60, 'color' => '#f43f5e', 'effect' => 'none'],
        'heavy' => ['name' => 'Cañón', 'cost' => 120, 'range' => 2.5, 'damage' => 40, 'cooldownMax' => 45, 'color' => '#8b5cf6', 'effect' => 'splash'],
        'frost' => ['name' => 'Hielo', 'cost' => 70, 'range' => 2.8, 'damage' => 8, 'cooldownMax' => 28, 'color' => '#22d3ee', 'effect' => 'slow'],
        'poison' => ['name' => 'Veneno', 'cost' => 90, 'range' => 3, 'damage' => 10, 'cooldownMax' => 40, 'color' => '#22c55e', 'effect' => 'poison'],
    ];

    /**
     * Estado inicial: nueva partida
     */
    public function getInitialState(): array
    {
        return [
            'lives' => 20,
            'gold' => 150,
            'wave' => 1,
            'waveActive' => false,
            'gameOver' => false,
            'towers' => [],
        ];
    }

    /**
     * Ejecuta una acción del juego.
     */
    public function executeAction(string $action, array $payload): array
    {
        if (! $this->validateAction($action, $payload)) {
            return ['error' => 'Invalid action', 'code' => 'INVALID_ACTION'];
        }

        return match ($action) {
            'wave_start' => $this->handleWaveStart($payload),
            'build_tower' => $this->handleBuildTower($payload),
            'upgrade_tower' => $this->handleUpgradeTower($payload),
            'sell_tower' => $this->handleSellTower($payload),
            'complete_wave' => $this->handleCompleteWave($payload),
            'lose_game' => $this->handleLoseGame($payload),
            default => ['error' => 'Unknown action', 'code' => 'UNKNOWN_ACTION'],
        };
    }

    /**
     * Valida las acciones del juego.
     */
    protected function validateAction(string $action, array $payload): bool
    {
        return match ($action) {
            'wave_start' => true,
            'build_tower' => isset($payload['type'], $payload['x'], $payload['y']),
            'upgrade_tower' => isset($payload['x'], $payload['y']),
            'sell_tower' => isset($payload['x'], $payload['y']),
            'complete_wave' => isset($payload['gameState']),
            'lose_game' => isset($payload['gameState']),
            default => false,
        };
    }

    /**
     * Inicia una onda
     */
    private function handleWaveStart(array $payload): array
    {
        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        if ($state['gameOver'] || $state['waveActive']) {
            return ['error' => 'Cannot start wave', 'code' => 'WAVE_START_INVALID'];
        }

        $state['waveActive'] = true;

        return ['success' => true, 'state' => $state];
    }

    /**
     * Construye una torre
     */
    private function handleBuildTower(array $payload): array
    {
        $towerType = $payload['type'];
        $x = (int) $payload['x'];
        $y = (int) $payload['y'];

        if (! isset(self::TOWER_TYPES[$towerType])) {
            return ['error' => 'Invalid tower type', 'code' => 'INVALID_TOWER_TYPE'];
        }

        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        $cost = self::TOWER_TYPES[$towerType]['cost'];

        if ($state['gold'] < $cost) {
            return ['error' => 'Insufficient gold', 'code' => 'INSUFFICIENT_GOLD'];
        }

        $state['gold'] -= $cost;

        return ['success' => true, 'state' => $state];
    }

    /**
     * Mejora una torre
     */
    private function handleUpgradeTower(array $payload): array
    {
        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        $x = (int) $payload['x'];
        $y = (int) $payload['y'];

        // Buscar torre en las coordenadas
        $tower = null;
        foreach ($state['towers'] ?? [] as &$t) {
            if ($t['x'] === $x && $t['y'] === $y) {
                $tower = &$t;
                break;
            }
        }

        if (! $tower) {
            return ['error' => 'Tower not found', 'code' => 'TOWER_NOT_FOUND'];
        }

        $upgradeCost = (int) ceil($tower['baseCost'] * pow(1.5, $tower['level']));

        if ($state['gold'] < $upgradeCost) {
            return ['error' => 'Insufficient gold for upgrade', 'code' => 'INSUFFICIENT_GOLD'];
        }

        $state['gold'] -= $upgradeCost;
        $tower['level']++;
        $tower['damage'] *= 1.4;
        $tower['range'] += 0.1;
        $tower['totalSpent'] += $upgradeCost;

        return ['success' => true, 'state' => $state];
    }

    /**
     * Vende una torre
     */
    private function handleSellTower(array $payload): array
    {
        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        $x = (int) $payload['x'];
        $y = (int) $payload['y'];

        // Buscar y remover torre
        $towerIndex = null;
        $towerValue = 0;

        foreach ($state['towers'] ?? [] as $idx => $t) {
            if ($t['x'] === $x && $t['y'] === $y) {
                $towerIndex = $idx;
                $towerValue = (int) floor($t['totalSpent'] * 0.5);
                break;
            }
        }

        if ($towerIndex === null) {
            return ['error' => 'Tower not found', 'code' => 'TOWER_NOT_FOUND'];
        }

        unset($state['towers'][$towerIndex]);
        $state['towers'] = array_values($state['towers']);
        $state['gold'] += $towerValue;

        return ['success' => true, 'state' => $state];
    }

    /**
     * Completa una onda
     */
    private function handleCompleteWave(array $payload): array
    {
        $gameState = $payload['gameState'];
        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        $newWave = (int) ($gameState['wave'] ?? 1) + 1;
        $state['wave'] = $newWave;
        $state['waveActive'] = false;

        $this->saveProgress($state, $newWave);

        return ['success' => true, 'wave' => $newWave, 'state' => $state];
    }

    /**
     * Registra la pérdida de la partida
     */
    private function handleLoseGame(array $payload): array
    {
        $gameState = $payload['gameState'];

        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        $finalWave = (int) ($gameState['wave'] ?? 1);
        $finalGold = (int) ($gameState['gold'] ?? 0);

        $state['gameOver'] = true;
        $state['waveActive'] = false;

        $this->saveProgress($state, $finalWave);

        return [
            'success' => true,
            'final_wave' => $finalWave,
            'final_gold' => $finalGold,
            'state' => $state,
        ];
    }

    /**
     * Retorna metadatos sobre la partida para verificaciones de logros.
     */
    public function getGameMetadata(array $state): array
    {
        return [
            'max_wave_reached' => (int) ($state['wave'] ?? 1),
            'total_towers_built' => count($state['towers'] ?? []),
            'total_gold_spent' => (150 - ($state['gold'] ?? 0)),
            'towers_by_type' => $this->countTowersByType($state['towers'] ?? []),
        ];
    }

    /**
     * Cuenta torres agrupadas por tipo.
     */
    private function countTowersByType(array $towers): array
    {
        $counts = [];
        foreach ($towers as $tower) {
            $type = $tower['name'] ?? 'unknown';
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        return $counts;
    }
}
