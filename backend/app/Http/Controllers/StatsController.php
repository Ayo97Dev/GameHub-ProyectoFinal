<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStatRequest;
use App\Http\Resources\GameStatResource;
use App\Http\Resources\LeaderboardResource;
use App\Models\Game;
use App\Models\GameStat;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function update(UpdateStatRequest $request, string $gameIdentifier)
    {
        $game = $this->resolveGame($gameIdentifier);

        $stat = GameStat::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'game_id' => $game->id,
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

        $wins = max((int) ($request->validated('wins') ?? 0), 0);
        $draws = max((int) ($request->validated('draws') ?? 0), 0);
        $losses = max((int) ($request->validated('losses') ?? 0), 0);

        if ($wins > 0) {
            $stat->wins += $wins;
        }

        if ($draws > 0) {
            $stat->draws += $draws;
        }

        if ($losses > 0) {
            $stat->losses += $losses;
        }

        $stat->last_played_at = now();
        $stat->save();

        return new GameStatResource($stat->load('game'));
    }

    public function leaderboard(string $gameIdentifier)
    {
        $game = $this->resolveGame($gameIdentifier);

        $statsQuery = GameStat::with('user')
            ->where('game_id', $game->id);

        if ($game->slug === 'chess') {
            $statsQuery
                ->orderByDesc('wins')
                ->orderByDesc('draws')
                ->orderByDesc('high_score')
                ->orderBy('time_played');
        } else {
            $statsQuery->orderByDesc('high_score');
        }

        $stats = $statsQuery
            ->take(10)
            ->get();

        return LeaderboardResource::collection($stats);
    }

    private function resolveGame(string $gameIdentifier): Game
    {
        return is_numeric($gameIdentifier)
            ? Game::findOrFail((int) $gameIdentifier)
            : Game::where('slug', $gameIdentifier)->firstOrFail();
    }
}
