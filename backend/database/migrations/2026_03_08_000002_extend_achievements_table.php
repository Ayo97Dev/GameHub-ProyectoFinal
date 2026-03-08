<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->string('slug')->unique()->after('id');
            $table->string('icon_url')->nullable()->after('description');
            $table->unsignedInteger('points_reward')->default(0)->after('icon_url');
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])->default('common')->after('points_reward');
            $table->json('condition')->nullable()->after('rarity');
            $table->boolean('is_active')->default(true)->after('condition');
            $table->foreignId('game_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['slug', 'icon_url', 'points_reward', 'rarity', 'condition', 'is_active']);
            $table->foreignId('game_id')->nullable(false)->change();
        });
    }
};
