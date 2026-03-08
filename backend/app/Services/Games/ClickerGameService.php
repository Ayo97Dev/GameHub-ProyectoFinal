<?php

namespace App\Services\Games;

use App\Models\Game;
use App\Models\User;
use App\Services\GameService;

class ClickerGameService extends GameService
{
    private const PRESTIGE_MIN_BALANCE = 1_000_000;

    private const UPGRADES = [
        // ── Tier 1: Básico ──────────────────────────────────────────────
        1  => ['name' => 'Autoclicker',  'cost' => 10,      'dps_bonus' => 0.1,   'click_bonus' => 0,     'tier' => 1],
        2  => ['name' => 'Dedos Rápidos',  'cost' => 50,      'dps_bonus' => 0,     'click_bonus' => 1,     'tier' => 1],
        3  => ['name' => 'Bot Clicker',     'cost' => 200,     'dps_bonus' => 2,     'click_bonus' => 0,     'tier' => 1],
        4  => ['name' => 'Manos Ágiles',   'cost' => 400,     'dps_bonus' => 0,     'click_bonus' => 4,     'tier' => 1],
        // ── Tier 2: Avanzado ────────────────────────────────────────────
        5  => ['name' => 'Motor Turbo',  'cost' => 2_000,   'dps_bonus' => 20,    'click_bonus' => 0,     'tier' => 2],
        6  => ['name' => 'Guante de Poder',   'cost' => 5_000,   'dps_bonus' => 0,     'click_bonus' => 25,    'tier' => 2],
        7  => ['name' => 'Red Neuronal',    'cost' => 30_000,  'dps_bonus' => 200,   'click_bonus' => 0,     'tier' => 2],
        8  => ['name' => 'Reflejos Turbo',  'cost' => 20_000,  'dps_bonus' => 0,     'click_bonus' => 100,   'tier' => 2],
        // ── Tier 3: Legendario ──────────────────────────────────────────
        9  => ['name' => 'Propulsor Cuántico',    'cost' => 150_000, 'dps_bonus' => 800,   'click_bonus' => 0,     'tier' => 3],
        10 => ['name' => 'Pulso Oscuro',    'cost' => 250_000, 'dps_bonus' => 0,     'click_bonus' => 600,   'tier' => 3],
        11 => ['name' => 'Núcleo Universal', 'cost' => 800_000, 'dps_bonus' => 6_000, 'click_bonus' => 0,     'tier' => 3],
        12 => ['name' => 'Mano Cósmica',   'cost' => 600_000, 'dps_bonus' => 0,     'click_bonus' => 2_500, 'tier' => 3],
    ];

    public function __construct(User $user, Game $game)
    {
        parent::__construct($user, $game);
    }

    public function getInitialState(): array
    {
        return [
            'balance'        => 0,
            'click_power'    => 1,
            'dps'            => 0,
            'upgrades'       => [],
            'total_clicks'   => 0,
            'prestige_level' => 0,
        ];
    }

    public function executeAction(string $action, array $payload): array
    {
        if (!$this->validateAction($action, $payload)) {
            return ['error' => 'Invalid action', 'code' => 'INVALID_ACTION'];
        }

        return match ($action) {
            'click'       => $this->handleClick($payload),
            'buy_upgrade' => $this->handleBuyUpgrade($payload),
            'prestige'    => $this->handlePrestige(),
            default       => ['error' => 'Unknown action', 'code' => 'UNKNOWN_ACTION'],
        };
    }

    protected function validateAction(string $action, array $payload): bool
    {
        return match ($action) {
            'click'       => isset($payload['timestamp']),
            'buy_upgrade' => isset($payload['upgrade_id']),
            'prestige'    => true,
            default       => false,
        };
    }

    private function handleClick(array $payload): array
    {
        // Anti-cheat: timestamp debe estar dentro de ±5 segundos
        $now = (int) (microtime(true) * 1000);
        if (abs($now - (int) $payload['timestamp']) > 5000) {
            return ['error' => 'Invalid timestamp', 'code' => 'TIMESTAMP_MISMATCH'];
        }

        // Número de clics en el lote; máximo 100 para prevenir abusos
        $count = max(1, min((int) ($payload['count'] ?? 1), 100));

        $progress = $this->loadProgress();
        $state    = $progress ? $progress->payload : $this->getInitialState();

        $state['balance']      += $state['click_power'] * $count;
        $state['total_clicks'] += $count;

        $this->saveProgress($state, (int) $state['balance']);

        return [
            'success'      => true,
            'balance'      => $state['balance'],
            'total_clicks' => $state['total_clicks'],
        ];
    }

    private function handleBuyUpgrade(array $payload): array
    {
        $upgradeId = (int) $payload['upgrade_id'];
        $upgrade   = self::UPGRADES[$upgradeId] ?? null;

        if (!$upgrade) {
            return ['error' => 'Upgrade not found', 'code' => 'UPGRADE_NOT_FOUND'];
        }

        $progress = $this->loadProgress();
        $state    = $progress ? $progress->payload : $this->getInitialState();

        // Coste escalado: baseCost × 1.15^cantidad_ya_comprada
        $currentCount = (int) ($state['upgrades'][$upgradeId] ?? 0);
        $scaledCost   = (int) ceil($upgrade['cost'] * (1.15 ** $currentCount));

        if ($state['balance'] < $scaledCost) {
            return ['error' => 'Insufficient balance', 'code' => 'INSUFFICIENT_BALANCE'];
        }

        $state['balance']              -= $scaledCost;
        $state['upgrades'][$upgradeId]  = $currentCount + 1;

        // Recalcular DPS y click_power desde cero para evitar desincronización
        [$dps, $clickPower] = $this->recalcStats($state);
        $state['dps']         = $dps;
        $state['click_power'] = $clickPower;

        $this->saveProgress($state, (int) $state['balance']);

        return [
            'success'     => true,
            'balance'     => $state['balance'],
            'upgrades'    => $state['upgrades'],
            'dps'         => $state['dps'],
            'click_power' => $state['click_power'],
        ];
    }

    /** Recalcula DPS y click_power a partir del array de mejoras. */
    private function recalcStats(array $state): array
    {
        $dps        = 0.0;
        $clickPower = 1.0 + (($state['prestige_level'] ?? 0) * 0.5);
        $dpsMul     = $state['prestige_dps_mul'] ?? 1.0;
        foreach ($state['upgrades'] as $upId => $count) {
            $u = self::UPGRADES[(int) $upId] ?? null;
            if ($u) {
                $dps        += $u['dps_bonus']   * $count;
                $clickPower += $u['click_bonus'] * $count;
            }
        }
        return [$dps * $dpsMul, $clickPower];
    }

    private function handlePrestige(): array
    {
        $progress = $this->loadProgress();
        $state    = $progress ? $progress->payload : $this->getInitialState();

        if (($state['balance'] ?? 0) < self::PRESTIGE_MIN_BALANCE) {
            return ['error' => 'Insufficient balance for prestige', 'code' => 'PRESTIGE_INSUFFICIENT_BALANCE'];
        }

        $prestigeLevel = ($state['prestige_level'] ?? 0) + 1;

        // Cada nivel de prestige: +0.5 click_power base + 5% de bonificación al DPS
        $newState = [
            'balance'          => 0,
            'click_power'      => 1.0 + ($prestigeLevel * 0.5),
            'dps'              => 0,
            'upgrades'         => [],
            'total_clicks'     => $state['total_clicks'] ?? 0,
            'prestige_level'   => $prestigeLevel,
            'prestige_dps_mul' => 1.0 + ($prestigeLevel * 0.05),
        ];

        $this->saveProgress($newState, 0);

        return [
            'success'          => true,
            'prestige_level'   => $prestigeLevel,
            'click_power'      => $newState['click_power'],
            'prestige_dps_mul' => $newState['prestige_dps_mul'],
        ];
    }

    public function getAvailableUpgrades(): array
    {
        return array_map(fn ($id, $u) => array_merge($u, ['id' => $id]), array_keys(self::UPGRADES), self::UPGRADES);
    }
}
