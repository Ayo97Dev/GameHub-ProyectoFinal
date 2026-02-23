<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStatRequest;
use App\Http\Resources\GameStatResource;
use App\Http\Resources\LeaderboardResource;
use App\Models\GameStat;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function update(UpdateStatRequest $request, $gameId)
    {
        $stat = GameStat::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'game_id' => $gameId,
            ]
        );

        $newHighScore = $request->validated('high_score');
        if ($newHighScore !== null && $newHighScore > $stat->high_score) {
            $stat->high_score = $newHighScore;
        }

        $timePlayed = $request->validated('time_played');
        if ($timePlayed !== null) {
            $stat->time_played += $timePlayed;
        }

        $stat->last_played_at = now();
        $stat->save();

        return new GameStatResource($stat->load('game'));
    }

    public function leaderboard($gameId)
    {
        $stats = GameStat::with('user')
            ->where('game_id', $gameId)
            ->orderByDesc('high_score')
            ->take(10)
            ->get();

        return LeaderboardResource::collection($stats);
    }
}
