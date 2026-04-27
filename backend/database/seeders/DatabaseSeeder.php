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
        $clicker = Game::firstOrCreate(['slug' => 'core-clicker'], [
            'title' => 'CoreClicker',
            'category' => 'CLICKER',
            'description' => 'Optimiza el núcleo del sistema mediante clics de alta frecuencia.',
            'is_active' => true,
            'config' => [
                'allowed_actions' => ['click', 'buy_upgrade', 'prestige'],
                'rate_limit_per_minute' => 120,
            ],
        ]);

        $rpg = Game::firstOrCreate(['slug' => 'descenso-al-abismo'], [
            'title' => 'Descenso al Abismo',
            'category' => 'RPG',
            'description' => 'Sobrevive a las profundidades en este RPG.',
            'is_active' => true,
            'config' => [
                'allowed_actions' => ['move', 'attack', 'heal'],
                'rate_limit_per_minute' => 60,
            ],
        ]);

        $quiz = Game::firstOrCreate(['slug' => 'quiz'], [
            'title' => 'Quiz',
            'category' => 'PUZZLE',
            'description' => 'Preguntas y respuestas.',
            'is_active' => true,
            'config' => [
                'allowed_actions' => ['answer_question', 'skip_question'],
                'rate_limit_per_minute' => 120,
            ],
        ]);

        $connect4 = Game::firstOrCreate(['slug' => 'connect4'], [
            'title' => 'Connect 4',
            'category' => 'PUZZLE',
            'description' => 'Conecta cuatro fichas contra la IA.',
            'is_active' => true,
            'config' => [
                'allowed_actions' => [],
                'rate_limit_per_minute' => 60,
            ],
        ]);

        Game::firstOrCreate(['slug' => 'proyecto-cortafuegos'], [
            'title' => 'Proyecto Cortafuegos',
            'category' => 'DEFENSE',
            'description' => 'Protege la red central contra intrusiones masivas desplegando contramedidas tácticas.',
            'is_active' => true,
            'config' => [
                'allowed_actions' => [],
                'rate_limit_per_minute' => 60,
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

            // ── Connect4: Victorias ───────────────────────────────────────────
            ['slug' => 'connect4-wins-1',    'title' => 'Primera Victoria',        'description' => 'Gana 1 partida de Connect4.',                'game_id' => $connect4->id, 'points_reward' => 20,  'rarity' => 'common',    'condition' => ['field' => 'wins', 'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'connect4-wins-10',   'title' => 'Racha Inicial',           'description' => 'Gana 10 partidas de Connect4.',              'game_id' => $connect4->id, 'points_reward' => 60,  'rarity' => 'uncommon',  'condition' => ['field' => 'wins', 'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'connect4-wins-20',   'title' => 'Competidor Serio',        'description' => 'Gana 20 partidas de Connect4.',              'game_id' => $connect4->id, 'points_reward' => 120, 'rarity' => 'rare',      'condition' => ['field' => 'wins', 'operator' => 'greater_than_or_equal', 'value' => 20]],
            ['slug' => 'connect4-wins-50',   'title' => 'Maestro de la Cuadricula', 'description' => 'Gana 50 partidas de Connect4.',             'game_id' => $connect4->id, 'points_reward' => 260, 'rarity' => 'epic',      'condition' => ['field' => 'wins', 'operator' => 'greater_than_or_equal', 'value' => 50]],
            ['slug' => 'connect4-wins-100',  'title' => 'Leyenda de Connect4',     'description' => 'Gana 100 partidas de Connect4.',             'game_id' => $connect4->id, 'points_reward' => 500, 'rarity' => 'legendary', 'condition' => ['field' => 'wins', 'operator' => 'greater_than_or_equal', 'value' => 100]],

            // ── RPG: Descenso al Abismo ───────────────────────────────────────
            // Progreso
            ['slug' => 'rpg-floor-2',   'title' => 'Primer Descenso',      'description' => 'Llega al Piso 2.',           'game_id' => $rpg->id, 'points_reward' => 10, 'rarity' => 'common',    'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 2]],
            ['slug' => 'rpg-floor-10',  'title' => 'Explorador del Abismo', 'description' => 'Llega al Piso 10.',          'game_id' => $rpg->id, 'points_reward' => 30, 'rarity' => 'uncommon',  'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'rpg-floor-20',  'title' => 'Veterano de Mazmorras', 'description' => 'Llega al Piso 20.',          'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare',      'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20]],
            ['slug' => 'rpg-floor-30',  'title' => 'Maestro del Calabozo',  'description' => 'Llega al Piso 30.',          'game_id' => $rpg->id, 'points_reward' => 75, 'rarity' => 'rare',      'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 30]],
            ['slug' => 'rpg-floor-40',  'title' => 'En las Sombras',        'description' => 'Llega al Piso 40.',          'game_id' => $rpg->id, 'points_reward' => 100, 'rarity' => 'epic',     'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 40]],
            ['slug' => 'rpg-floor-50',  'title' => 'Conquistador Abisal',   'description' => 'Llega al Piso 50.',          'game_id' => $rpg->id, 'points_reward' => 150, 'rarity' => 'epic',     'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 50]],
            ['slug' => 'rpg-floor-75',  'title' => 'Héroe del Vacío',       'description' => 'Llega al Piso 75.',          'game_id' => $rpg->id, 'points_reward' => 250, 'rarity' => 'legendary', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 75]],
            ['slug' => 'rpg-floor-100', 'title' => 'Leyenda del Abismo',    'description' => 'Llega al Piso 100.',         'game_id' => $rpg->id, 'points_reward' => 500, 'rarity' => 'legendary', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 100]],
            
            // Combate
            ['slug' => 'rpg-boss-1',    'title' => 'Caída del Guardián',    'description' => 'Derrota al primer jefe.',    'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'uncommon',  'condition' => ['field' => 'bosses_defeated', 'operator' => 'greater_than_or_equal', 'value' => 1]],
            ['slug' => 'rpg-boss-3',    'title' => 'Vencedor Abisal',       'description' => 'Derrota a 3 jefes.',         'game_id' => $rpg->id, 'points_reward' => 150, 'rarity' => 'rare',      'condition' => ['field' => 'bosses_defeated', 'operator' => 'greater_than_or_equal', 'value' => 3]],
            ['slug' => 'rpg-boss-5',    'title' => 'Soberano de las Sombras', 'description' => 'Derrota a 5 jefes.',       'game_id' => $rpg->id, 'points_reward' => 300, 'rarity' => 'epic',     'condition' => ['field' => 'bosses_defeated', 'operator' => 'greater_than_or_equal', 'value' => 5]],

            // Nivel
            ['slug' => 'rpg-level-5',   'title' => 'Creciendo en Poder',    'description' => 'Alcanza el nivel 5.',        'game_id' => $rpg->id, 'points_reward' => 20, 'rarity' => 'common',    'condition' => ['field' => 'level', 'operator' => 'greater_than_or_equal', 'value' => 5]],
            ['slug' => 'rpg-level-10',  'title' => 'Poder Absoluto',        'description' => 'Alcanza el nivel 10.',       'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'uncommon',  'condition' => ['field' => 'level', 'operator' => 'greater_than_or_equal', 'value' => 10]],
            ['slug' => 'rpg-level-20',  'title' => 'Héroe Ascendido',       'description' => 'Alcanza el nivel 20.',       'game_id' => $rpg->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'level', 'operator' => 'greater_than_or_equal', 'value' => 20]],
            ['slug' => 'rpg-level-30',  'title' => 'Semidiós del Abismo',   'description' => 'Alcanza el nivel 30.',       'game_id' => $rpg->id, 'points_reward' => 250, 'rarity' => 'epic',     'condition' => ['field' => 'level', 'operator' => 'greater_than_or_equal', 'value' => 30]],

            // Stats
            ['slug' => 'rpg-hp-500',    'title' => 'Carne Inmortal',        'description' => 'Alcanza 500 de vida máxima.', 'game_id' => $rpg->id, 'points_reward' => 40, 'rarity' => 'uncommon',  'condition' => ['field' => 'max_hp', 'operator' => 'greater_than_or_equal', 'value' => 500]],
            ['slug' => 'rpg-atk-100',   'title' => 'Maestro de la Espada',  'description' => 'Alcanza 100 de ataque.',     'game_id' => $rpg->id, 'points_reward' => 40, 'rarity' => 'uncommon',  'condition' => ['field' => 'attack', 'operator' => 'greater_than_or_equal', 'value' => 100]],
            ['slug' => 'rpg-def-100',   'title' => 'Muro Infranqueable',    'description' => 'Alcanza 100 de defensa.',    'game_id' => $rpg->id, 'points_reward' => 40, 'rarity' => 'uncommon',  'condition' => ['field' => 'defense', 'operator' => 'greater_than_or_equal', 'value' => 100]],
            ['slug' => 'rpg-mag-100',   'title' => 'Sabiduría del Archimago', 'description' => 'Alcanza 100 de ataque mágico.', 'game_id' => $rpg->id, 'points_reward' => 40, 'rarity' => 'uncommon',  'condition' => ['field' => 'magic_attack', 'operator' => 'greater_than_or_equal', 'value' => 100]],
            ['slug' => 'rpg-spd-50',    'title' => 'Veloz como el Viento',  'description' => 'Alcanza 50 de velocidad.',   'game_id' => $rpg->id, 'points_reward' => 40, 'rarity' => 'uncommon',  'condition' => ['field' => 'speed', 'operator' => 'greater_than_or_equal', 'value' => 50]],

            // Economía
            ['slug' => 'rpg-gold-500',  'title' => 'Acaparador Dorado',     'description' => 'Acumula 500 de oro en una partida.', 'game_id' => $rpg->id, 'points_reward' => 30, 'rarity' => 'uncommon',  'condition' => ['field' => 'gold_run', 'operator' => 'greater_than_or_equal', 'value' => 500]],
            ['slug' => 'rpg-gold-1000', 'title' => 'Toque de Midas',        'description' => 'Acumula 1000 de oro en una partida.', 'game_id' => $rpg->id, 'points_reward' => 75, 'rarity' => 'rare',      'condition' => ['field' => 'gold_run', 'operator' => 'greater_than_or_equal', 'value' => 1000]],

            // Clases
            ['slug' => 'rpg-class-warrior', 'title' => 'Voluntad de Hierro', 'description' => 'Llega al Piso 20 como Guerrero.', 'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20, 'class' => 'warrior']],
            ['slug' => 'rpg-class-paladin', 'title' => 'Protección Divina',  'description' => 'Llega al Piso 20 como Paladín.', 'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20, 'class' => 'paladin']],
            ['slug' => 'rpg-class-rogue',   'title' => 'Fantasma en la Oscuridad', 'description' => 'Llega al Piso 20 como Pícaro.', 'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20, 'class' => 'rogue']],
            ['slug' => 'rpg-class-mage',    'title' => 'Maestría Elemental', 'description' => 'Llega al Piso 20 como Mago.', 'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20, 'class' => 'mage']],
            ['slug' => 'rpg-class-hunter',  'title' => 'Ojo de la Naturaleza', 'description' => 'Llega al Piso 20 como Cazador.', 'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20, 'class' => 'hunter']],
            ['slug' => 'rpg-class-cleric',  'title' => 'Bendición Santa',     'description' => 'Llega al Piso 20 como Clérigo.', 'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20, 'class' => 'cleric']],
            ['slug' => 'rpg-class-necro',   'title' => 'Cosechador de Almas', 'description' => 'Llega al Piso 20 como Necromante.', 'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20, 'class' => 'necromancer']],
            ['slug' => 'rpg-class-berserk', 'title' => 'Furia Desatada',      'description' => 'Llega al Piso 20 como Berserker.', 'game_id' => $rpg->id, 'points_reward' => 50, 'rarity' => 'rare', 'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 20, 'class' => 'berserker']],

            // Más logros variados para llegar a 50
            ['slug' => 'rpg-floor-5',   'title' => 'Descenso Continuo',    'description' => 'Llega al Piso 5.',           'game_id' => $rpg->id, 'points_reward' => 15, 'rarity' => 'common',    'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 5]],
            ['slug' => 'rpg-floor-15',  'title' => 'Profundidades Conocidas', 'description' => 'Llega al Piso 15.',        'game_id' => $rpg->id, 'points_reward' => 40, 'rarity' => 'uncommon',  'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 15]],
            ['slug' => 'rpg-floor-25',  'title' => 'Donde Pocos Llegan',   'description' => 'Llega al Piso 25.',          'game_id' => $rpg->id, 'points_reward' => 60, 'rarity' => 'rare',      'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 25]],
            ['slug' => 'rpg-floor-60',  'title' => 'Más Allá de la Razón', 'description' => 'Llega al Piso 60.',          'game_id' => $rpg->id, 'points_reward' => 200, 'rarity' => 'epic',     'condition' => ['field' => 'floor', 'operator' => 'greater_than_or_equal', 'value' => 60]],
            ['slug' => 'rpg-hp-1000',   'title' => 'Titán del Abismo',     'description' => 'Alcanza 1000 de vida máxima.', 'game_id' => $rpg->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'max_hp', 'operator' => 'greater_than_or_equal', 'value' => 1000]],
            ['slug' => 'rpg-atk-200',   'title' => 'Segador de Almas',     'description' => 'Alcanza 200 de ataque.',     'game_id' => $rpg->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'attack', 'operator' => 'greater_than_or_equal', 'value' => 200]],
            ['slug' => 'rpg-def-200',   'title' => 'Fortaleza Andante',    'description' => 'Alcanza 200 de defensa.',    'game_id' => $rpg->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'defense', 'operator' => 'greater_than_or_equal', 'value' => 200]],
            ['slug' => 'rpg-mag-200',   'title' => 'Sabio de las Sombras', 'description' => 'Alcanza 200 de ataque mágico.', 'game_id' => $rpg->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'magic_attack', 'operator' => 'greater_than_or_equal', 'value' => 200]],
            ['slug' => 'rpg-spd-100',   'title' => 'Relámpago en la Oscuridad', 'description' => 'Alcanza 100 de velocidad.', 'game_id' => $rpg->id, 'points_reward' => 100, 'rarity' => 'rare',      'condition' => ['field' => 'speed', 'operator' => 'greater_than_or_equal', 'value' => 100]],
            ['slug' => 'rpg-gold-2000', 'title' => 'Rey de los Ladrones',   'description' => 'Acumula 2000 de oro en una partida.', 'game_id' => $rpg->id, 'points_reward' => 150, 'rarity' => 'epic',     'condition' => ['field' => 'gold_run', 'operator' => 'greater_than_or_equal', 'value' => 2000]],
            ['slug' => 'rpg-level-40',  'title' => 'Eternidad Alcanzada',   'description' => 'Alcanza el nivel 40.',       'game_id' => $rpg->id, 'points_reward' => 400, 'rarity' => 'legendary', 'condition' => ['field' => 'level', 'operator' => 'greater_than_or_equal', 'value' => 40]],
            ['slug' => 'rpg-boss-7',    'title' => 'Aniquilador de Mitos',  'description' => 'Derrota a 7 jefes.',         'game_id' => $rpg->id, 'points_reward' => 500, 'rarity' => 'legendary', 'condition' => ['field' => 'bosses_defeated', 'operator' => 'greater_than_or_equal', 'value' => 7]],
            ['slug' => 'rpg-boss-10',   'title' => 'Fin de los Tiempos',    'description' => 'Derrota a 10 jefes.',        'game_id' => $rpg->id, 'points_reward' => 1000, 'rarity' => 'legendary', 'condition' => ['field' => 'bosses_defeated', 'operator' => 'greater_than_or_equal', 'value' => 10]],

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
