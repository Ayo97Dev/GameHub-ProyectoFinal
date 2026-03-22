<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $arcade = DB::table('games')->where('slug', 'arcade')->first();
        $quiz = DB::table('games')->where('slug', 'quiz')->first();

        if ($arcade && !$quiz) {
            DB::table('games')
                ->where('id', $arcade->id)
                ->update([
                    'slug' => 'quiz',
                    'title' => 'Quiz',
                    'description' => 'Preguntas y respuestas.',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $arcade = DB::table('games')->where('slug', 'arcade')->first();
        $quiz = DB::table('games')->where('slug', 'quiz')->first();

        if ($quiz && !$arcade) {
            DB::table('games')
                ->where('id', $quiz->id)
                ->update([
                    'slug' => 'arcade',
                    'title' => 'Cyber Arcade Blaster',
                    'description' => 'Acción arcade retro con reflejos al límite.',
                    'updated_at' => now(),
                ]);
        }
    }
};
