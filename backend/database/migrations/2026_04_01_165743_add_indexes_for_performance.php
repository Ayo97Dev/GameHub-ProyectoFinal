<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Índice único para game_saves (user_id + game_id)
        DB::statement('ALTER TABLE game_saves ADD UNIQUE KEY idx_user_game_save (user_id, game_id)');
        
        // Índice único para game_stats (user_id + game_id)
        DB::statement('ALTER TABLE game_stats ADD UNIQUE KEY idx_user_game_stat (user_id, game_id)');
        
        // Índice único para achievement_user (user_id + achievement_id)
        DB::statement('ALTER TABLE achievement_user ADD UNIQUE KEY idx_user_achievement (user_id, achievement_id)');
        
        // Índices para game_sessions por estado y usuario
        DB::statement('ALTER TABLE game_sessions ADD INDEX idx_user_status (user_id, status)');
        DB::statement('ALTER TABLE game_sessions ADD INDEX idx_user_game_status (user_id, game_id, status)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE game_saves DROP INDEX idx_user_game_save');
        DB::statement('ALTER TABLE game_stats DROP INDEX idx_user_game_stat');
        DB::statement('ALTER TABLE achievement_user DROP INDEX idx_user_achievement');
        DB::statement('ALTER TABLE game_sessions DROP INDEX idx_user_status');
        DB::statement('ALTER TABLE game_sessions DROP INDEX idx_user_game_status');
    }
};
