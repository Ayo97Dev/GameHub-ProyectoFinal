<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TowerDefenseAchievementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Game $towerDefenseGame;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // El juego debería ser creado por el seeder o manualmente aquí
        $this->towerDefenseGame = Game::where('slug', 'proyecto-cortafuegos')->first();
        if (!$this->towerDefenseGame) {
            $this->towerDefenseGame = $this->createTowerDefenseGame();
        }
    }

    private function createTowerDefenseGame(): Game
    {
        return Game::updateOrCreate(
            ['slug' => 'proyecto-cortafuegos'],
            [
                'title' => 'Proyecto Cortafuegos',
                'description' => 'Defiende tu base de oleadas de enemigos.',
                'config' => [
                    'allowed_actions' => ['wave_start', 'build_tower', 'upgrade_tower', 'sell_tower', 'complete_wave', 'lose_game'],
                    'rate_limit_per_minute' => 1000,
                ],
                'is_active' => true,
            ]
        );
    }

    public function test_wave_5_achievement_unlocks_on_save(): void
    {
        // Crear o actualizar el logro
        $achievement = Achievement::updateOrCreate(
            ['slug' => 'td-wave-5', 'game_id' => $this->towerDefenseGame->id],
            [
                'title' => 'Onda 5',
                'description' => 'Alcanza la oleada 5.',
                'points_reward' => 25,
                'rarity' => 'common',
                'is_active' => true,
                'condition' => ['field' => 'max_wave_reached', 'operator' => 'greater_than_or_equal', 'value' => 5],
            ]
        );

        // Crear la partida
        $this->actingAs($this->user)->post('/api/games/proyecto-cortafuegos/play');

        $gameState = [
            'lives' => 20,
            'gold' => 150,
            'wave' => 5,
            'waveActive' => false,
            'gameOver' => false,
            'towers' => [],
        ];

        // Guardar partida con onda 5
        $response = $this->actingAs($this->user)->post('/api/games/proyecto-cortafuegos/save', [
            'game_state' => $gameState,
            'score' => 5,
            'playtime' => 100,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['saved' => true]);

        // Verificar que el logro se desbloqueó
        $this->assertTrue(
            $this->user->achievements()->where('achievement_id', $achievement->id)->exists(),
            'Wave 5 achievement should be unlocked'
        );
    }

    public function test_tower_5_achievement_unlocks_on_save(): void
    {
        // Crear o actualizar el logro
        $achievement = Achievement::updateOrCreate(
            ['slug' => 'td-towers-5', 'game_id' => $this->towerDefenseGame->id],
            [
                'title' => 'Defensor Inicial',
                'description' => 'Construye 5 torres.',
                'points_reward' => 15,
                'rarity' => 'common',
                'is_active' => true,
                'condition' => ['field' => 'total_towers_built', 'operator' => 'greater_than_or_equal', 'value' => 5],
            ]
        );

        $this->actingAs($this->user)->post('/api/games/proyecto-cortafuegos/play');

        $gameState = [
            'lives' => 20,
            'gold' => 50,
            'wave' => 2,
            'waveActive' => false,
            'gameOver' => false,
            'towers' => [
                ['x' => 1, 'y' => 1, 'type' => 'basic', 'level' => 1],
                ['x' => 2, 'y' => 1, 'type' => 'basic', 'level' => 1],
                ['x' => 3, 'y' => 1, 'type' => 'basic', 'level' => 1],
                ['x' => 4, 'y' => 1, 'type' => 'basic', 'level' => 1],
                ['x' => 5, 'y' => 1, 'type' => 'basic', 'level' => 1],
            ],
        ];

        // Guardar partida con 5 torres
        $response = $this->actingAs($this->user)->post('/api/games/proyecto-cortafuegos/save', [
            'game_state' => $gameState,
            'score' => 2,
            'playtime' => 50,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['saved' => true]);

        // Verificar que el logro se desbloqueó
        $this->assertTrue(
            $this->user->achievements()->where('achievement_id', $achievement->id)->exists(),
            'Tower 5 achievement should be unlocked'
        );
    }

    public function test_gold_500_achievement_does_not_unlock_with_low_gold_spent(): void
    {
        // Crear o actualizar el logro
        $achievement = Achievement::updateOrCreate(
            ['slug' => 'td-gold-500', 'game_id' => $this->towerDefenseGame->id],
            [
                'title' => 'Inversor',
                'description' => 'Gasta 500 de oro.',
                'points_reward' => 30,
                'rarity' => 'uncommon',
                'is_active' => true,
                'condition' => ['field' => 'total_gold_spent', 'operator' => 'greater_than_or_equal', 'value' => 500],
            ]
        );

        $this->actingAs($this->user)->post('/api/games/proyecto-cortafuegos/play');

        // Solo gastamos 150 (150 inicial - 0 final = 150)
        $gameState = [
            'lives' => 20,
            'gold' => 0,
            'wave' => 2,
            'waveActive' => false,
            'gameOver' => true,
            'towers' => [],
        ];

        // Guardar partida
        $response = $this->actingAs($this->user)->post('/api/games/proyecto-cortafuegos/save', [
            'game_state' => $gameState,
            'score' => 2,
            'playtime' => 50,
        ]);

        $response->assertStatus(200);

        // Verificar que el logro NO se desbloqueó
        $this->assertFalse(
            $this->user->achievements()->where('achievement_id', $achievement->id)->exists(),
            'Gold 500 achievement should NOT be unlocked with only 150 gold spent'
        );
    }
}

