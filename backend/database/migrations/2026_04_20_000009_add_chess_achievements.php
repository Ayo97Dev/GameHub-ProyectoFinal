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
                'slug' => 'chess-first-win',
                'title' => 'Primer Win',
                'description' => 'Gana tu primera partida de ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 25,
                'rarity' => 'common',
                'condition' => [
                    'field' => 'wins',
                    'operator' => 'greater_than_or_equal',
                    'value' => 1,
                ],
            ],
            [
                'slug' => 'chess-first-draw',
                'title' => 'Primer Draw',
                'description' => 'Consigue tu primer empate en ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 20,
                'rarity' => 'common',
                'condition' => [
                    'field' => 'draws',
                    'operator' => 'greater_than_or_equal',
                    'value' => 1,
                ],
            ],
            [
                'slug' => 'chess-first-loss',
                'title' => 'Primer Loss',
                'description' => 'Termina tu primera partida perdida en ajedrez.',
                'game_id' => $chess->id,
                'points_reward' => 15,
                'rarity' => 'common',
                'condition' => [
                    'field' => 'losses',
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
