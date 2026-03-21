<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardBySlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_top_scores_for_the_requested_game_slug(): void
    {
        $clicker = Game::create([
            'slug' => 'clicker',
            'title' => 'ClickMaster',
            'description' => 'Clicker game',
            'config' => [],
            'is_active' => true,
        ]);

        $rpg = Game::create([
            'slug' => 'rpg',
            'title' => 'Dungeon RPG',
            'description' => 'RPG game',
            'config' => [],
            'is_active' => true,
        ]);

        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        $charlie = User::factory()->create(['name' => 'Charlie']);

        GameStat::create([
            'user_id' => $alice->id,
            'game_id' => $clicker->id,
            'high_score' => 5000,
            'time_played' => 120,
        ]);

        GameStat::create([
            'user_id' => $bob->id,
            'game_id' => $clicker->id,
            'high_score' => 9000,
            'time_played' => 90,
        ]);

        GameStat::create([
            'user_id' => $charlie->id,
            'game_id' => $rpg->id,
            'high_score' => 999999,
            'time_played' => 400,
        ]);

        $response = $this->getJson('/api/leaderboard/clicker');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.username', 'Bob')
            ->assertJsonPath('data.0.high_score', 9000)
            ->assertJsonPath('data.1.username', 'Alice')
            ->assertJsonPath('data.1.high_score', 5000);
    }
}
