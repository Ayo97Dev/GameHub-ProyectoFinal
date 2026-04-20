<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStatRequest;
use App\Http\Resources\GameStatResource;
use App\Http\Resources\LeaderboardResource;
use App\Models\Game;
use App\Models\GameStat;
use App\Services\AchievementService;
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
        $winsWithoutQueenLoss = max((int) ($request->validated('wins_without_queen_loss') ?? 0), 0);

        if ($wins > 0) {
            $stat->wins += $wins;
        }

        if ($draws > 0) {
            $stat->draws += $draws;
        }

        if ($losses > 0) {
            $stat->losses += $losses;
        }

        if ($winsWithoutQueenLoss > 0) {
            $stat->wins_without_queen_loss += $winsWithoutQueenLoss;
        }

        $nonLossEvents = $wins + $draws;
        if ($losses > 0) {
            // Cualquier derrota corta la racha actual.
            $stat->current_non_loss_streak = 0;
        } elseif ($nonLossEvents > 0) {
            $stat->current_non_loss_streak += $nonLossEvents;
            if ($stat->current_non_loss_streak > $stat->best_non_loss_streak) {
                $stat->best_non_loss_streak = $stat->current_non_loss_streak;
            }
        }

        $stat->last_played_at = now();
        $stat->save();

        $achievementService = new AchievementService();
        $achievementService->checkAndUnlock(
            $request->user(),
            $game->id,
            [
                'score' => (int) $stat->high_score,
                'duration' => $timePlayed ?? PHP_INT_MAX,
                'wins' => (int) $stat->wins,
                'draws' => (int) $stat->draws,
                'losses' => (int) $stat->losses,
                'non_loss_streak' => (int) $stat->current_non_loss_streak,
                'wins_without_queen_loss' => (int) $stat->wins_without_queen_loss,
            ]
        );

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
