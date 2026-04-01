<?php

use App\Models\Achievement;
use App\Models\Game;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $towerDefense = Game::where('slug', 'tower-defense')->first();

        if (! $towerDefense) {
            return;
        }

        $achievementsData = [
            // ── Oleadas alcanzadas ───────────────────────────────────────────
            ['slug' => 'td-wave-5',         'title' => 'Onda 5',                    'description' => 'Alcanza la oleada 5.',                       'game_id' => $towerDefense->id, 'points_reward' => 25,  'rarity' => 'common',    'condition' => ['field' => 'max_wave', 'operator' => 'greater_than_or_equal', 'value' => 5]],
            ['slug' => 'td-wave-10',        'title' => 'Onda 10',                   'description' => 'Alcanza la oleada 10.',                      'game_id' => $towerDefense->id, 'points_reward' => 60,  'rarity' => 'uncommon',  'condition' => ['field' => 'max_wave', 'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'td-wave-15',        'title' => 'Onda 15',                   'description' => 'Alcanza la oleada 15.',                      'game_id' => $towerDefense->id, 'points_reward' => 120, 'rarity' => 'rare',      'condition' => ['field' => 'max_wave', 'operator' => 'greater_than_or_equal', 'value' => 15]],
            ['slug' => 'td-wave-20',        'title' => 'Onda 20',                   'description' => 'Alcanza la oleada 20.',                      'game_id' => $towerDefense->id, 'points_reward' => 200, 'rarity' => 'epic',      'condition' => ['field' => 'max_wave', 'operator' => 'greater_than_or_equal', 'value' => 20]],

            // ── Torres construidas ────────────────────────────────────────────
            ['slug' => 'td-towers-5',       'title' => 'Defensor Inicial',          'description' => 'Construye 5 torres.',                        'game_id' => $towerDefense->id, 'points_reward' => 15,  'rarity' => 'common',    'condition' => ['field' => 'total_towers_built', 'operator' => 'greater_than_or_equal', 'value' => 5]],
            ['slug' => 'td-towers-10',      'title' => 'Ingeniero',                 'description' => 'Construye 10 torres.',                       'game_id' => $towerDefense->id, 'points_reward' => 40,  'rarity' => 'uncommon',  'condition' => ['field' => 'total_towers_built', 'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'td-towers-20',      'title' => 'Arquitecto',                'description' => 'Construye 20 torres.',                       'game_id' => $towerDefense->id, 'points_reward' => 80,  'rarity' => 'rare',      'condition' => ['field' => 'total_towers_built', 'operator' => 'greater_than_or_equal', 'value' => 20]],

            // ── Oro gastado ───────────────────────────────────────────────────
            ['slug' => 'td-gold-500',       'title' => 'Inversor',                  'description' => 'Gasta 500 de oro.',                         'game_id' => $towerDefense->id, 'points_reward' => 30,  'rarity' => 'uncommon',  'condition' => ['field' => 'total_gold_spent', 'operator' => 'greater_than_or_equal', 'value' => 500]],
            ['slug' => 'td-gold-1000',      'title' => 'Gran Inversor',             'description' => 'Gasta 1000 de oro.',                        'game_id' => $towerDefense->id, 'points_reward' => 75,  'rarity' => 'rare',      'condition' => ['field' => 'total_gold_spent', 'operator' => 'greater_than_or_equal', 'value' => 1000]],
            ['slug' => 'td-gold-2000',      'title' => 'Magnate de la Guerra',      'description' => 'Gasta 2000 de oro.',                        'game_id' => $towerDefense->id, 'points_reward' => 150, 'rarity' => 'epic',      'condition' => ['field' => 'total_gold_spent', 'operator' => 'greater_than_or_equal', 'value' => 2000]],
        ];

        foreach ($achievementsData as $data) {
            Achievement::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_active' => true])
            );
        }
    }

    public function down(): void
    {
        $achievements = Achievement::where('slug', 'like', 'td-%')->get();
        foreach ($achievements as $achievement) {
            $achievement->delete();
        }
    }
};
