<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('games')->updateOrInsert(
            ['slug' => 'tower-defense'],
            [
                'title' => 'Tower Defense',
                'description' => 'Defiende el reactor y escala la dificultad por oleadas.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('games')->where('slug', 'tower-defense')->delete();
    }
};
