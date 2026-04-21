<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Usuario de prueba
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => Hash::make('password')]
        );

        // Juegos
        $clicker = Game::firstOrCreate(['slug' => 'clicker'], [
            'title'       => 'ClickMaster',
            'description' => 'Haz clic, mejora tu producción y domina el ranking.',
            'is_active'   => true,
            'config'      => [
                'allowed_actions'       => ['click', 'buy_upgrade', 'prestige'],
                'rate_limit_per_minute' => 120,
            ],
        ]);

        $rpg = Game::firstOrCreate(['slug' => 'rpg'], [
            'title'       => 'Dungeon RPG',
            'description' => 'Explora mazmorras y sube de nivel a tu héroe.',
            'is_active'   => true,
            'config'      => [
                'allowed_actions'       => ['move', 'attack', 'heal'],
                'rate_limit_per_minute' => 60,
            ],
        ]);

        $quiz = Game::firstOrCreate(['slug' => 'quiz'], [
            'title'       => 'Quiz',
            'description' => 'Preguntas y respuestas.',
            'is_active'   => true,
            'config'      => [
                'allowed_actions'       => ['answer_question', 'skip_question'],
                'rate_limit_per_minute' => 120,
            ],
        ]);

        Game::firstOrCreate(['slug' => 'battleship'], [
            'title'       => 'Fleet Strategy',
            'description' => 'Comanda tu flota para ser el mejor en el mar.',
            'is_active'   => true,
            'config'      => [
                'allowed_actions'       => ['fire', 'scan', 'restart'],
                'rate_limit_per_minute' => 90,
            ],
        ]);

        $chess = Game::firstOrCreate(['slug' => 'chess'], [
            'title'       => 'Board King',
            'description' => 'Sé el rey del tablero y sé el primero del ranking.',
            'is_active'   => true,
            'config'      => [
                'allowed_actions'       => ['move', 'promote', 'restart'],
                'rate_limit_per_minute' => 90,
            ],
        ]);

        // Achievements globales
        $achievementsData = [
            // ── Puntuación ──────────────────────────────────────────────────────
            ['slug' => 'first-click',        'title' => 'Primer Click',             'description' => 'Consigue tu primer punto.',                  'game_id' => $clicker->id, 'points_reward' => 10,  'rarity' => 'common',    'condition' => ['field' => 'score', 'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'clicker-rookie',     'title' => 'Clicker Novato',           'description' => 'Alcanza 100 puntos.',                        'game_id' => $clicker->id, 'points_reward' => 25,  'rarity' => 'common',    'condition' => ['field' => 'score', 'operator' => 'greater_than_or_equal', 'value' => 100]],
            ['slug' => 'clicker-pro',        'title' => 'Clicker Pro',              'description' => 'Alcanza 1.000 puntos.',                      'game_id' => $clicker->id, 'points_reward' => 50,  'rarity' => 'uncommon',  'condition' => ['field' => 'score', 'operator' => 'greater_than_or_equal', 'value' => 1000]],
            ['slug' => 'clicker-master',     'title' => 'Maestro del Click',        'description' => 'Alcanza 10.000 puntos.',                     'game_id' => $clicker->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'score', 'operator' => 'greater_than_or_equal', 'value' => 10000]],
            ['slug' => 'clicker-legend',     'title' => 'Leyenda del Click',        'description' => 'Alcanza 100.000 puntos.',                    'game_id' => $clicker->id, 'points_reward' => 250, 'rarity' => 'epic',      'condition' => ['field' => 'score', 'operator' => 'greater_than_or_equal', 'value' => 100000]],
            ['slug' => 'clicker-halfmil',    'title' => 'Medio Millón',             'description' => 'Alcanza 500.000 puntos.',                    'game_id' => $clicker->id, 'points_reward' => 350, 'rarity' => 'epic',      'condition' => ['field' => 'score', 'operator' => 'greater_than_or_equal', 'value' => 500000]],
            ['slug' => 'clicker-god',        'title' => 'Dios del Click',           'description' => 'Alcanza 1.000.000 puntos.',                  'game_id' => $clicker->id, 'points_reward' => 500, 'rarity' => 'legendary', 'condition' => ['field' => 'score', 'operator' => 'greater_than_or_equal', 'value' => 1000000]],

            // ── Clics totales ────────────────────────────────────────────────
            ['slug' => 'clicks-100',         'title' => 'Dedo Activo',              'description' => 'Realiza 100 clics.',                         'game_id' => $clicker->id, 'points_reward' => 15,  'rarity' => 'common',    'condition' => ['field' => 'total_clicks', 'operator' => 'greater_than_or_equal', 'value' => 100]],
            ['slug' => 'clicks-1k',          'title' => 'Clic-adicto',              'description' => 'Realiza 1.000 clics.',                       'game_id' => $clicker->id, 'points_reward' => 30,  'rarity' => 'uncommon',  'condition' => ['field' => 'total_clicks', 'operator' => 'greater_than_or_equal', 'value' => 1000]],
            ['slug' => 'clicks-10k',         'title' => 'Maratonista del Click',    'description' => 'Realiza 10.000 clics.',                      'game_id' => $clicker->id, 'points_reward' => 75,  'rarity' => 'rare',      'condition' => ['field' => 'total_clicks', 'operator' => 'greater_than_or_equal', 'value' => 10000]],
            ['slug' => 'clicks-100k',        'title' => 'Incansable',               'description' => 'Realiza 100.000 clics.',                     'game_id' => $clicker->id, 'points_reward' => 150, 'rarity' => 'epic',      'condition' => ['field' => 'total_clicks', 'operator' => 'greater_than_or_equal', 'value' => 100000]],

            // ── Mejoras ──────────────────────────────────────────────────────
            ['slug' => 'first-upgrade',      'title' => 'Primera Mejora',           'description' => 'Compra tu primera mejora.',                  'game_id' => $clicker->id, 'points_reward' => 15,  'rarity' => 'common',    'condition' => ['field' => 'total_upgrades_bought', 'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'upgrade-collector',  'title' => 'Coleccionista',            'description' => 'Compra la misma mejora 5 veces.',            'game_id' => $clicker->id, 'points_reward' => 40,  'rarity' => 'uncommon',  'condition' => ['field' => 'max_upgrade_count',     'operator' => 'greater_than_or_equal', 'value' => 5]],
            ['slug' => 'upgrade-fanatic',    'title' => 'Fanático',                 'description' => 'Compra la misma mejora 10 veces.',           'game_id' => $clicker->id, 'points_reward' => 80,  'rarity' => 'rare',      'condition' => ['field' => 'max_upgrade_count',     'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'upgrade-hoarder',    'title' => 'Acaparador',               'description' => 'Compra un total de 20 mejoras.',             'game_id' => $clicker->id, 'points_reward' => 60,  'rarity' => 'uncommon',  'condition' => ['field' => 'total_upgrades_bought', 'operator' => 'greater_than_or_equal', 'value' => 20]],
            ['slug' => 'upgrade-hoarder-50', 'title' => 'Arsenal Completo',         'description' => 'Compra un total de 50 mejoras.',             'game_id' => $clicker->id, 'points_reward' => 120, 'rarity' => 'rare',      'condition' => ['field' => 'total_upgrades_bought', 'operator' => 'greater_than_or_equal', 'value' => 50]],
            // Tier 2
            ['slug' => 'turbo-owner',        'title' => 'Motor Turbo',              'description' => 'Desbloquea el Motor Turbo.',                 'game_id' => $clicker->id, 'points_reward' => 75,  'rarity' => 'rare',      'condition' => ['field' => 'upgrade_count', 'upgrade_id' => 5,  'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'neural-owner',       'title' => 'Red Neuronal',             'description' => 'Desbloquea la Red Neuronal.',                'game_id' => $clicker->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'upgrade_count', 'upgrade_id' => 7,  'operator' => 'greater_than_or_equal', 'value' => 1]],
            // Tier 3
            ['slug' => 'quantum-owner',      'title' => 'Propulsor Cuántico',       'description' => 'Desbloquea el Propulsor Cuántico.',          'game_id' => $clicker->id, 'points_reward' => 200, 'rarity' => 'epic',      'condition' => ['field' => 'upgrade_count', 'upgrade_id' => 9,  'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'universe-owner',     'title' => 'Núcleo Universal',         'description' => 'Desbloquea el Núcleo Universal.',            'game_id' => $clicker->id, 'points_reward' => 400, 'rarity' => 'legendary', 'condition' => ['field' => 'upgrade_count', 'upgrade_id' => 11, 'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'cosmic-owner',       'title' => 'Mano Cósmica',             'description' => 'Desbloquea la Mano Cósmica.',                'game_id' => $clicker->id, 'points_reward' => 400, 'rarity' => 'legendary', 'condition' => ['field' => 'upgrade_count', 'upgrade_id' => 12, 'operator' => 'greater_than_or_equal', 'value' => 1]],

            // ── Prestige ─────────────────────────────────────────────────────
            ['slug' => 'prestige-first',     'title' => 'Renacido',                 'description' => 'Realiza tu primer Prestige.',                'game_id' => $clicker->id, 'points_reward' => 50,  'rarity' => 'uncommon',  'condition' => ['field' => 'prestige_level', 'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'prestige-3',         'title' => 'Triple Renacimiento',      'description' => 'Realiza 3 Prestiges.',                       'game_id' => $clicker->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'prestige_level', 'operator' => 'greater_than_or_equal', 'value' => 3]],
            ['slug' => 'prestige-master',    'title' => 'Maestro del Renacimiento', 'description' => 'Realiza 5 Prestiges.',                       'game_id' => $clicker->id, 'points_reward' => 200, 'rarity' => 'epic',      'condition' => ['field' => 'prestige_level', 'operator' => 'greater_than_or_equal', 'value' => 5]],
            ['slug' => 'prestige-legend',    'title' => 'Eterno',                   'description' => 'Realiza 10 Prestiges.',                      'game_id' => $clicker->id, 'points_reward' => 500, 'rarity' => 'legendary', 'condition' => ['field' => 'prestige_level', 'operator' => 'greater_than_or_equal', 'value' => 10]],

            // ── Ajedrez (resultados) ───────────────────────────────────────
            ['slug' => 'chess-first-win',    'title' => 'Primer Win',               'description' => 'Gana tu primera partida de ajedrez.',         'game_id' => $chess->id,   'points_reward' => 25,  'rarity' => 'common',    'condition' => ['field' => 'wins',   'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'chess-first-draw',   'title' => 'Primer Draw',              'description' => 'Consigue tu primer empate en ajedrez.',       'game_id' => $chess->id,   'points_reward' => 20,  'rarity' => 'common',    'condition' => ['field' => 'draws',  'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'chess-first-loss',   'title' => 'Primer Loss',              'description' => 'Termina tu primera partida perdida en ajedrez.', 'game_id' => $chess->id, 'points_reward' => 15,  'rarity' => 'common',    'condition' => ['field' => 'losses', 'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'chess-10-wins',      'title' => '10 Wins',                  'description' => 'Consigue 10 victorias en ajedrez.',            'game_id' => $chess->id,   'points_reward' => 100, 'rarity' => 'uncommon',  'condition' => ['field' => 'wins',   'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'chess-10-draws',     'title' => '10 Draws',                 'description' => 'Consigue 10 empates en ajedrez.',              'game_id' => $chess->id,   'points_reward' => 80,  'rarity' => 'uncommon',  'condition' => ['field' => 'draws',  'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'chess-10-losses',    'title' => '10 Losses',                'description' => 'Registra 10 derrotas en ajedrez.',             'game_id' => $chess->id,   'points_reward' => 60,  'rarity' => 'common',    'condition' => ['field' => 'losses', 'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'chess-streak-5',     'title' => 'Racha Sin Derrota x5',     'description' => 'Encadena 5 partidas sin perder en ajedrez.',   'game_id' => $chess->id,   'points_reward' => 120, 'rarity' => 'rare',      'condition' => ['field' => 'non_loss_streak', 'operator' => 'greater_than_or_equal', 'value' => 5]],
            ['slug' => 'chess-streak-10',    'title' => 'Racha Sin Derrota x10',    'description' => 'Encadena 10 partidas sin perder en ajedrez.',  'game_id' => $chess->id,   'points_reward' => 220, 'rarity' => 'epic',      'condition' => ['field' => 'non_loss_streak', 'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'chess-streak-20',    'title' => 'Racha Sin Derrota x20',    'description' => 'Encadena 20 partidas sin perder en ajedrez.',  'game_id' => $chess->id,   'points_reward' => 400, 'rarity' => 'legendary', 'condition' => ['field' => 'non_loss_streak', 'operator' => 'greater_than_or_equal', 'value' => 20]],
            ['slug' => 'chess-win-no-queen-loss', 'title' => 'Victoria Sin Caer La Reina', 'description' => 'Gana una partida sin perder tu reina.', 'game_id' => $chess->id, 'points_reward' => 180, 'rarity' => 'rare', 'condition' => ['field' => 'wins_without_queen_loss', 'operator' => 'greater_than_or_equal', 'value' => 1]],

            // ── Global ───────────────────────────────────────────────────────
            ['slug' => 'speed-runner',       'title' => 'Speed Runner',             'description' => 'Completa una sesión en menos de 60 segundos.', 'game_id' => null,       'points_reward' => 75,  'rarity' => 'epic',      'condition' => ['field' => 'duration', 'operator' => 'less_than_or_equal', 'value' => 60]],
        ];

        foreach ($achievementsData as $data) {
            Achievement::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}

