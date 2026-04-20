<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_stats', function (Blueprint $table) {
            if (!Schema::hasColumn('game_stats', 'current_non_loss_streak')) {
                $table->unsignedInteger('current_non_loss_streak')->default(0);
            }

            if (!Schema::hasColumn('game_stats', 'best_non_loss_streak')) {
                $table->unsignedInteger('best_non_loss_streak')->default(0);
            }

            if (!Schema::hasColumn('game_stats', 'wins_without_queen_loss')) {
                $table->unsignedInteger('wins_without_queen_loss')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_stats', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('game_stats', 'current_non_loss_streak')) {
                $columnsToDrop[] = 'current_non_loss_streak';
            }

            if (Schema::hasColumn('game_stats', 'best_non_loss_streak')) {
                $columnsToDrop[] = 'best_non_loss_streak';
            }

            if (Schema::hasColumn('game_stats', 'wins_without_queen_loss')) {
                $columnsToDrop[] = 'wins_without_queen_loss';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
