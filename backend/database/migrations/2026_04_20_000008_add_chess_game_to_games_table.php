<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('games')
            ->where('slug', 'chess')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('games')->insert([
            'slug' => 'chess',
            'title' => 'Board King',
            'description' => 'Sé el rey del tablero y sé el primero del ranking.',
            'config' => json_encode([
                'allowed_actions' => ['move', 'promote', 'restart'],
                'rate_limit_per_minute' => 90,
            ], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No se elimina para evitar borrar progreso de usuarios asociado a chess.
    }
};
