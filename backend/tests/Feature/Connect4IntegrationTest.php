<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameSave;
use App\Models\GameStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Connect4IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect4_leaderboard_requires_authentication(): void
    {
        $this->createConnect4Game();

        $response = $this->getJson('/api/leaderboard/connect4');

        $response->assertUnauthorized();
    }

    public function test_authenticated_users_can_view_connect4_leaderboard_ordered_by_wins(): void
    {
        $connect4 = $this->createConnect4Game();

        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        $viewer = User::factory()->create(['name' => 'Viewer']);

        GameStat::create([
            'user_id' => $alice->id,
            'game_id' => $connect4->id,
            'high_score' => 6,
            'time_played' => 150,
        ]);

        GameStat::create([
            'user_id' => $bob->id,
            'game_id' => $connect4->id,
            'high_score' => 12,
            'time_played' => 90,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/leaderboard/connect4');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.username', 'Bob')
            ->assertJsonPath('data.0.high_score', 12)
            ->assertJsonPath('data.1.username', 'Alice')
            ->assertJsonPath('data.1.high_score', 6);
    }

    public function test_connect4_save_persists_stats_and_unlocks_wins_achievement(): void
    {
        $connect4 = $this->createConnect4Game();

        $achievement = Achievement::create([
            'slug' => 'connect4-wins-1',
            'title' => 'Primera Victoria',
            'description' => 'Gana 1 partida de Connect4.',
            'game_id' => $connect4->id,
            'points_reward' => 20,
            'rarity' => 'common',
            'condition' => [
                'field' => 'wins',
                'operator' => 'greater_than_or_equal',
                'value' => 1,
            ],
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/games/connect4/play', [
            'load_save' => true,
        ])->assertOk();

        $this->postJson('/api/games/connect4/save', [
            'game_state' => [
                'wins' => 1,
                'losses' => 0,
            ],
            'score' => 999,
            'playtime' => 180,
        ])->assertOk()->assertJsonPath('saved', true);

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);

        $stat = GameStat::where('user_id', $user->id)
            ->where('game_id', $connect4->id)
            ->firstOrFail();

        $this->assertSame(1, (int) $stat->high_score);
        $this->assertSame(180, (int) $stat->time_played);

        $save = GameSave::where('user_id', $user->id)
            ->where('game_id', $connect4->id)
            ->firstOrFail();

        $this->assertSame(1, (int) ($save->payload['wins'] ?? 0));
        $this->assertSame(0, (int) ($save->payload['losses'] ?? 0));

        $this->postJson('/api/games/connect4/save', [
            'game_state' => [
                'wins' => 1,
                'losses' => 1,
            ],
            'score' => 0,
            'playtime' => 20,
        ])->assertOk()->assertJsonPath('saved', true);

        $updatedStat = GameStat::where('user_id', $user->id)
            ->where('game_id', $connect4->id)
            ->firstOrFail();

        $this->assertSame(1, (int) $updatedStat->high_score);
        $this->assertSame(200, (int) $updatedStat->time_played);

        $this->getJson('/api/games/connect4/load')
            ->assertOk()
            ->assertJsonPath('game_state.wins', 1)
            ->assertJsonPath('game_state.losses', 1)
            ->assertJsonPath('score', 1)
            ->assertJsonPath('playtime', 200);
    }

    private function createConnect4Game(): Game
    {
        return Game::create([
            'slug' => 'connect4',
            'title' => 'Connect 4',
            'description' => 'Conecta cuatro fichas contra la IA.',
            'config' => [
                'allowed_actions' => [],
                'rate_limit_per_minute' => 60,
            ],
            'is_active' => true,
        ]);
    }
}
