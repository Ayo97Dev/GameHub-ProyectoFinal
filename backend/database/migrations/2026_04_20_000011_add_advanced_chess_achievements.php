<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $chess = DB::table('games')->where('slug', 'chess')->first();
        if (!$chess) {
            return;
        }

        $achievements = [
            [
                'slug' => 'chess-10-wins',
                'title' => '10 Wins',
                'description' => 'Consigue 10 victorias en ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 100,
                'rarity' => 'uncommon',
                'condition' => [
                    'field' => 'wins',
                    'operator' => 'greater_than_or_equal',
                    'value' => 10,
                ],
            ],
            [
                'slug' => 'chess-10-draws',
                'title' => '10 Draws',
                'description' => 'Consigue 10 empates en ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 80,
                'rarity' => 'uncommon',
                'condition' => [
                    'field' => 'draws',
                    'operator' => 'greater_than_or_equal',
                    'value' => 10,
                ],
            ],
            [
                'slug' => 'chess-10-losses',
                'title' => '10 Losses',
                'description' => 'Registra 10 derrotas en ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 60,
                'rarity' => 'common',
                'condition' => [
                    'field' => 'losses',
                    'operator' => 'greater_than_or_equal',
                    'value' => 10,
                ],
            ],
            [
                'slug' => 'chess-streak-5',
                'title' => 'Racha Sin Derrota x5',
                'description' => 'Encadena 5 partidas sin perder en ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 120,
                'rarity' => 'rare',
                'condition' => [
                    'field' => 'non_loss_streak',
                    'operator' => 'greater_than_or_equal',
                    'value' => 5,
                ],
            ],
            [
                'slug' => 'chess-streak-10',
                'title' => 'Racha Sin Derrota x10',
                'description' => 'Encadena 10 partidas sin perder en ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 220,
                'rarity' => 'epic',
                'condition' => [
                    'field' => 'non_loss_streak',
                    'operator' => 'greater_than_or_equal',
                    'value' => 10,
                ],
            ],
            [
                'slug' => 'chess-streak-20',
                'title' => 'Racha Sin Derrota x20',
                'description' => 'Encadena 20 partidas sin perder en ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 400,
                'rarity' => 'legendary',
                'condition' => [
                    'field' => 'non_loss_streak',
                    'operator' => 'greater_than_or_equal',
                    'value' => 20,
                ],
            ],
            [
                'slug' => 'chess-win-no-queen-loss',
                'title' => 'Victoria Sin Caer La Reina',
                'description' => 'Gana una partida sin perder tu reina.',
                'game_id' => $chess->id,
                'points_reward' => 180,
                'rarity' => 'rare',
                'condition' => [
                    'field' => 'wins_without_queen_loss',
                    'operator' => 'greater_than_or_equal',
                    'value' => 1,
                ],
            ],
        ];

        foreach ($achievements as $achievement) {
            $exists = DB::table('achievements')
                ->where('slug', $achievement['slug'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('achievements')->insert([
                'slug' => $achievement['slug'],
                'title' => $achievement['title'],
                'description' => $achievement['description'],
                'game_id' => $achievement['game_id'],
                'icon_url' => null,
                'points_reward' => $achievement['points_reward'],
                'rarity' => $achievement['rarity'],
                'condition' => json_encode($achievement['condition'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No se elimina para evitar perder historial de desbloqueos en producción.
    }
};
