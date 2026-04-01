<?php

namespace App\Http\Controllers;

use App\Http\Resources\AchievementResource;
use App\Models\Achievement;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $unlockedIds = $user->achievements()->pluck('achievement_id')->toArray();

        $achievements = Achievement::where('is_active', true)
            ->get()
            ->map(fn ($a) => new AchievementResource($a, in_array($a->id, $unlockedIds)
                ? $user->achievements()->where('achievement_id', $a->id)->first()?->pivot->earned_at
                : null));

        return response()->json(['data' => $achievements]);
    }

    public function byGame(Request $request, string $slug): JsonResponse
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        $user = $request->user();
        $unlockedIds = $user->achievements()->pluck('achievement_id')->toArray();

        $achievements = Achievement::where('is_active', true)
            ->where('game_id', $game->id)
            ->get()
            ->map(fn ($a) => new AchievementResource($a, in_array($a->id, $unlockedIds)
                ? $user->achievements()->where('achievement_id', $a->id)->first()?->pivot->earned_at
                : null));

        return response()->json(['data' => $achievements]);
    }
}
