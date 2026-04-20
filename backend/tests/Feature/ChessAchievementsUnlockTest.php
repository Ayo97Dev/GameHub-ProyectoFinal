<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChessAchievementsUnlockTest extends TestCase
{
    use RefreshDatabase;

    private function ensureAchievement(
        int $gameId,
        string $slug,
        string $title,
        string $description,
        int $points,
        string $rarity,
        array $condition
    ): Achievement {
        return Achievement::firstOrCreate(
            ['slug' => $slug],
            [
                'slug' => $slug,
                'title' => $title,
                'description' => $description,
                'game_id' => $gameId,
                'icon_url' => null,
                'points_reward' => $points,
                'rarity' => $rarity,
                'condition' => $condition,
                'is_active' => true,
            ]
        );
    }

    public function test_it_unlocks_chess_result_achievement_when_stats_are_reported(): void
    {
        $chess = Game::firstOrCreate(['slug' => 'chess'], [
            'slug' => 'chess',
            'title' => 'Board King',
            'description' => 'Chess game',
            'config' => [],
            'is_active' => true,
        ]);

        $firstWin = $this->ensureAchievement(
            $chess->id,
            'chess-first-win',
            'Primer Win',
            'Gana tu primera partida de ajedrez.',
            25,
            'common',
            [
                'field' => 'wins',
                'operator' => 'greater_than_or_equal',
                'value' => 1,
            ]
        );

        $firstDraw = $this->ensureAchievement(
            $chess->id,
            'chess-first-draw',
            'Primer Draw',
            'Consigue tu primer empate en ajedrez.',
            20,
            'common',
            [
                'field' => 'draws',
                'operator' => 'greater_than_or_equal',
                'value' => 1,
            ]
        );

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/games/chess/stats', [
            'wins' => 1,
            'time_played' => 45,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $firstWin->id,
        ]);

        $this->assertDatabaseMissing('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $firstDraw->id,
        ]);
    }

    public function test_it_unlocks_advanced_chess_achievements_and_tracks_streaks(): void
    {
        $chess = Game::firstOrCreate(['slug' => 'chess'], [
            'slug' => 'chess',
            'title' => 'Board King',
            'description' => 'Chess game',
            'config' => [],
            'is_active' => true,
        ]);

        $achievements = [
            'wins10' => $this->ensureAchievement(
                $chess->id,
                'chess-10-wins',
                '10 Wins',
                'Consigue 10 victorias en ajedrez.',
                100,
                'uncommon',
                [
                    'field' => 'wins',
                    'operator' => 'greater_than_or_equal',
                    'value' => 10,
                ]
            ),
            'draws10' => $this->ensureAchievement(
                $chess->id,
                'chess-10-draws',
                '10 Draws',
                'Consigue 10 empates en ajedrez.',
                80,
                'uncommon',
                [
                    'field' => 'draws',
                    'operator' => 'greater_than_or_equal',
                    'value' => 10,
                ]
            ),
            'losses10' => $this->ensureAchievement(
                $chess->id,
                'chess-10-losses',
                '10 Losses',
                'Registra 10 derrotas en ajedrez.',
                60,
                'common',
                [
                    'field' => 'losses',
                    'operator' => 'greater_than_or_equal',
                    'value' => 10,
                ]
            ),
            'streak5' => $this->ensureAchievement(
                $chess->id,
                'chess-streak-5',
                'Racha Sin Derrota x5',
                'Encadena 5 partidas sin perder en ajedrez.',
                120,
                'rare',
                [
                    'field' => 'non_loss_streak',
                    'operator' => 'greater_than_or_equal',
                    'value' => 5,
                ]
            ),
            'streak10' => $this->ensureAchievement(
                $chess->id,
                'chess-streak-10',
                'Racha Sin Derrota x10',
                'Encadena 10 partidas sin perder en ajedrez.',
                220,
                'epic',
                [
                    'field' => 'non_loss_streak',
                    'operator' => 'greater_than_or_equal',
                    'value' => 10,
                ]
            ),
            'streak20' => $this->ensureAchievement(
                $chess->id,
                'chess-streak-20',
                'Racha Sin Derrota x20',
                'Encadena 20 partidas sin perder en ajedrez.',
                400,
                'legendary',
                [
                    'field' => 'non_loss_streak',
                    'operator' => 'greater_than_or_equal',
                    'value' => 20,
                ]
            ),
            'winNoQueenLoss' => $this->ensureAchievement(
                $chess->id,
                'chess-win-no-queen-loss',
                'Victoria Sin Caer La Reina',
                'Gana una partida sin perder tu reina.',
                180,
                'rare',
                [
                    'field' => 'wins_without_queen_loss',
                    'operator' => 'greater_than_or_equal',
                    'value' => 1,
                ]
            ),
        ];

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/games/chess/stats', [
            'wins' => 10,
            'wins_without_queen_loss' => 1,
            'time_played' => 300,
        ])->assertSuccessful();

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievements['wins10']->id,
        ]);
        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievements['streak5']->id,
        ]);
        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievements['streak10']->id,
        ]);
        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievements['winNoQueenLoss']->id,
        ]);

        $this->assertDatabaseMissing('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievements['streak20']->id,
        ]);

        $this->postJson('/api/games/chess/stats', [
            'draws' => 10,
            'time_played' => 120,
        ])->assertSuccessful();

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievements['draws10']->id,
        ]);
        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievements['streak20']->id,
        ]);

        $this->postJson('/api/games/chess/stats', [
            'losses' => 10,
            'time_played' => 90,
        ])->assertSuccessful();

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievements['losses10']->id,
        ]);

        $this->assertDatabaseHas('game_stats', [
            'user_id' => $user->id,
            'game_id' => $chess->id,
            'wins' => 10,
            'draws' => 10,
            'losses' => 10,
            'current_non_loss_streak' => 0,
            'best_non_loss_streak' => 20,
            'wins_without_queen_loss' => 1,
        ]);
    }
}
