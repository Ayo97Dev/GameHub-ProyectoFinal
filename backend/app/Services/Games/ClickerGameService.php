<?php

namespace App\Services\Games;

/**
 * CORE CLICKER SERVICE
 * 
 * Implementa la lógica de un juego incremental (Idle/Clicker).
 * Incluye validaciones anti-cheat (timestamps) y mecánicas de prestigio.
 */
use App\Models\Game;
use App\Models\User;
use App\Services\GameService;

class ClickerGameService extends GameService
{
    private const PRESTIGE_BASE_MIN_BALANCE = 1_000_000;

    /**
     * CONFIGURACIÓN DE MEJORAS
     * Tier 1: Mejoras de entrada.
     * Tier 2: Incrementos significativos.
     * Tier 3: Bonus legendarios.
     */
    private const UPGRADES = [
        // ── Tier 1: Básico ──────────────────────────────────────────────
        1 => ['name' => 'Autoclicker',  'cost' => 10,      'dps_bonus' => 0.1,   'click_bonus' => 0,     'tier' => 1],
        2 => ['name' => 'Dedos Rápidos',  'cost' => 50,      'dps_bonus' => 0,     'click_bonus' => 1,     'tier' => 1],
        3 => ['name' => 'Bot Clicker',     'cost' => 200,     'dps_bonus' => 2,     'click_bonus' => 0,     'tier' => 1],
        4 => ['name' => 'Manos Ágiles',   'cost' => 400,     'dps_bonus' => 0,     'click_bonus' => 4,     'tier' => 1],
        // ── Tier 2: Avanzado ────────────────────────────────────────────
        5 => ['name' => 'Motor Turbo',  'cost' => 2_000,   'dps_bonus' => 20,    'click_bonus' => 0,     'tier' => 2],
        6 => ['name' => 'Guante de Poder',   'cost' => 5_000,   'dps_bonus' => 0,     'click_bonus' => 25,    'tier' => 2],
        7 => ['name' => 'Red Neuronal',    'cost' => 30_000,  'dps_bonus' => 200,   'click_bonus' => 0,     'tier' => 2],
        8 => ['name' => 'Reflejos Turbo',  'cost' => 20_000,  'dps_bonus' => 0,     'click_bonus' => 100,   'tier' => 2],
        // ── Tier 3: Legendario ──────────────────────────────────────────
        9 => ['name' => 'Propulsor Cuántico',    'cost' => 150_000, 'dps_bonus' => 800,   'click_bonus' => 0,     'tier' => 3],
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
            'balance' => 0,
            'click_power' => 1,
            'dps' => 0,
            'upgrades' => [],
            'total_clicks' => 0,
            'prestige_level' => 0,
            'prestige_click_bonus' => 0,
            'prestige_dps_mul' => 1.0,
        ];
    }

    /**
     * PROCESAMIENTO DE ACCIONES
     */
    public function executeAction(string $action, array $payload): array
    {
        if (! $this->validateAction($action, $payload)) {
            return ['error' => 'Invalid action', 'code' => 'INVALID_ACTION'];
        }

        return match ($action) {
            'click' => $this->handleClick($payload),
            'buy_upgrade' => $this->handleBuyUpgrade($payload),
            'prestige' => $this->handlePrestige($payload),
            default => ['error' => 'Unknown action', 'code' => 'UNKNOWN_ACTION'],
        };
    }

    protected function validateAction(string $action, array $payload): bool
    {
        return match ($action) {
            'click' => isset($payload['timestamp']),
            'buy_upgrade' => isset($payload['upgrade_id']),
            'prestige' => ! isset($payload['expected_balance']) || is_numeric($payload['expected_balance']),
            default => false,
        };
    }

    /**
     * GESTIÓN DE CLICKS
     * Incluye una validación de tiempo (±5s) para evitar ataques de repetición.
     */
    private function handleClick(array $payload): array
    {
        // ANTI-CHEAT: Verificación de desfase temporal.
        $now = (int) (microtime(true) * 1000);
        if (abs($now - (int) $payload['timestamp']) > 5000) {
            return ['error' => 'Invalid timestamp', 'code' => 'TIMESTAMP_MISMATCH'];
        }

        // Límite de ráfaga para prevenir macros masivas.
        $count = max(1, min((int) ($payload['count'] ?? 1), 100));

        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        $state['balance'] += $state['click_power'] * $count;
        $state['total_clicks'] += $count;

        $this->saveProgress($state, (int) $state['balance']);

        return [
            'success' => true,
            'balance' => $state['balance'],
            'total_clicks' => $state['total_clicks'],
        ];
    }

    /**
     * COMPRA DE MEJORAS
     * El coste escala exponencialmente: CosteBase * 1.15^N
     */
    private function handleBuyUpgrade(array $payload): array
    {
        $upgradeId = (int) $payload['upgrade_id'];
        $upgrade = self::UPGRADES[$upgradeId] ?? null;

        if (! $upgrade) {
            return ['error' => 'Upgrade not found', 'code' => 'UPGRADE_NOT_FOUND'];
        }

        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        $currentCount = (int) ($state['upgrades'][$upgradeId] ?? 0);
        $scaledCost = (int) ceil($upgrade['cost'] * (1.15 ** $currentCount));

        if ($state['balance'] < $scaledCost) {
            return ['error' => 'Insufficient balance', 'code' => 'INSUFFICIENT_BALANCE'];
        }

        $state['balance'] -= $scaledCost;
        $state['upgrades'][$upgradeId] = $currentCount + 1;

        // RECALCULAR ESTADÍSTICAS
        // Evitamos sumas incrementales para prevenir errores de redondeo acumulados.
        [$dps, $clickPower] = $this->recalcStats($state);
        $state['dps'] = $dps;
        $state['click_power'] = $clickPower;

        $this->saveProgress($state, (int) $state['balance']);

        return [
            'success' => true,
            'balance' => $state['balance'],
            'upgrades' => $state['upgrades'],
            'dps' => $state['dps'],
            'click_power' => $state['click_power'],
        ];
    }

    /** 
     * RECALCULO DE STATS
     * Deriva Click Power y DPS del inventario actual y el nivel de prestigio.
     */
    private function recalcStats(array $state): array
    {
        $dps = 0.0;
        $prestigeClickBonus = $state['prestige_click_bonus'] ?? (($state['prestige_level'] ?? 0) * 0.5);
        $clickPower = 1.0 + $prestigeClickBonus;
        $dpsMul = $state['prestige_dps_mul'] ?? (1.0 + (($state['prestige_level'] ?? 0) * 0.05));
        
        foreach ($state['upgrades'] as $upId => $count) {
            $u = self::UPGRADES[(int) $upId] ?? null;
            if ($u) {
                $dps += $u['dps_bonus'] * $count;
                $clickPower += $u['click_bonus'] * $count;
            }
        }

        return [$dps * $dpsMul, $clickPower];
    }

    /**
     * PRESTIGIO (Soft Reset)
     * Reinicia el progreso a cambio de multiplicadores permanentes de DPS y Click Power.
     */
    private function handlePrestige(array $payload = []): array
    {
        $progress = $this->loadProgress();
        $state = $progress ? $progress->payload : $this->getInitialState();

        $currentPrestigeLevel = (int) ($state['prestige_level'] ?? 0);
        $requiredBalance = $this->getPrestigeRequiredBalance($currentPrestigeLevel);
        $savedBalance = (float) ($state['balance'] ?? 0);
        $clientBalance = (float) ($payload['expected_balance'] ?? 0);
        $effectiveBalance = max($savedBalance, $clientBalance);

        if ($effectiveBalance < $requiredBalance) {
            return [
                'error' => 'Insufficient balance for prestige',
                'code' => 'PRESTIGE_INSUFFICIENT_BALANCE',
                'required_balance' => $requiredBalance,
                'current_balance' => $effectiveBalance,
            ];
        }

        $prestigeLevel = $currentPrestigeLevel + 1;
        $currentClickBonus = (float) ($state['prestige_click_bonus'] ?? ($currentPrestigeLevel * 0.5));
        $currentDpsMul = (float) ($state['prestige_dps_mul'] ?? (1.0 + ($currentPrestigeLevel * 0.05)));
        
        $clickIncrement = $this->getPrestigeClickIncrement($currentPrestigeLevel);
        $dpsFactor = $this->getPrestigeDpsFactor($currentPrestigeLevel);
        
        $newClickBonus = round($currentClickBonus + $clickIncrement, 4);
        $newDpsMul = round($currentDpsMul * $dpsFactor, 4);

        // REINICIO DE ESTADO CON MANTENIMIENTO DE BONUS
        $newState = [
            'balance' => 0,
            'click_power' => 1.0 + $newClickBonus,
            'dps' => 0,
            'upgrades' => [],
            'total_clicks' => 0,
            'prestige_level' => $prestigeLevel,
            'prestige_click_bonus' => $newClickBonus,
            'prestige_dps_mul' => $newDpsMul,
        ];

        $this->saveProgress($newState, 0);

        return [
            'success' => true,
            'prestige_level' => $prestigeLevel,
            'click_power' => $newState['click_power'],
            'prestige_click_bonus' => $newClickBonus,
            'prestige_dps_mul' => $newState['prestige_dps_mul'],
            'applied_click_increment' => $clickIncrement,
            'applied_dps_factor' => $dpsFactor,
            'next_required_balance' => $this->getPrestigeRequiredBalance($prestigeLevel),
        ];
    }

    private function getPrestigeRequiredBalance(int $prestigeLevel): int
    {
        return (int) round(self::PRESTIGE_BASE_MIN_BALANCE * (2 ** max($prestigeLevel, 0)));
    }

    private function getPrestigeClickIncrement(int $prestigeLevel): float
    {
        return round(0.5 + ($prestigeLevel * 0.25), 4);
    }

    private function getPrestigeDpsFactor(int $prestigeLevel): float
    {
        return round(1.05 + (min($prestigeLevel, 10) * 0.01), 4);
    }

    public function getAvailableUpgrades(): array
    {
        return array_map(fn ($id, $u) => array_merge($u, ['id' => $id]), array_keys(self::UPGRADES), self::UPGRADES);
    }

    /**
     * METADATOS PARA LOGROS
     */
    public function getGameMetadata(array $state): array
    {
        $upgrades = $state['upgrades'] ?? [];
        $upgradeValues = array_values(array_map('intval', $upgrades));

        return [
            'upgrades' => array_map('intval', $upgrades),
            'total_clicks' => (int) ($state['total_clicks'] ?? 0),
            'prestige_level' => (int) ($state['prestige_level'] ?? 0),
            'total_upgrades_bought' => array_sum($upgradeValues),
            'max_upgrade_count' => $upgradeValues ? max($upgradeValues) : 0,
        ];
    }
}
