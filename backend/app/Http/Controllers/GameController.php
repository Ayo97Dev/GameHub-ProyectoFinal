<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteGameRequest;
use App\Http\Requests\GameActionRequest;
use App\Http\Requests\PlayGameRequest;
use App\Http\Resources\AchievementResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\GameSessionResource;
use App\Models\Game;
use App\Models\GameSession;
use App\Services\AchievementService;
use App\Services\Games\BattleshipGameService;
use App\Services\Games\ClickerGameService;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index()
    {
        return GameResource::collection(Game::active()->get());
    }

    public function show($slug)
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        return new GameResource($game);
    }

    public function play(PlayGameRequest $request, string $slug): JsonResponse
    {
        $game    = Game::active()->where('slug', $slug)->firstOrFail();
        $service = $this->resolveService($request->user(), $game);

        $loadSave = $request->boolean('load_save', true);
        $progress = $loadSave ? $service->loadProgress() : null;
        $state    = $progress ? $progress->payload : $service->getInitialState();

        $session = $service->createSession($state);

        return response()->json([
            'session_id' => $session->id,
            'game_state' => $state,
            'game'       => new GameResource($game),
        ]);
    }

    public function action(GameActionRequest $request, string $slug): JsonResponse
    {
        $game    = Game::active()->where('slug', $slug)->firstOrFail();
        $service = $this->resolveService($request->user(), $game);

        $result = $service->executeAction(
            $request->input('action'),
            $request->input('payload')
        );

        if (isset($result['error'])) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success'   => true,
            'data'      => $result,
            'timestamp' => now()->getTimestampMs(),
        ]);
    }

    public function load(Request $request, string $slug): JsonResponse
    {
        $game     = Game::active()->where('slug', $slug)->firstOrFail();
        $service  = $this->resolveService($request->user(), $game);
        $progress = $service->loadProgress();
        $stat     = $request->user()->gameStats()->where('game_id', $game->id)->first();

        if (!$progress) {
            return response()->json([
                'game_state'  => $service->getInitialState(),
                'score'       => $stat?->high_score ?? 0,
                'playtime'    => $stat?->time_played ?? 0,
                'wins'        => $stat?->wins ?? 0,
                'draws'       => $stat?->draws ?? 0,
                'losses'      => $stat?->losses ?? 0,
                'last_played' => $stat?->last_played_at,
            ]);
        }

        return response()->json([
            'game_state'  => $progress->payload,
            'score'       => $stat?->high_score ?? 0,
            'playtime'    => $stat?->time_played ?? 0,
            'wins'        => $stat?->wins ?? 0,
            'draws'       => $stat?->draws ?? 0,
            'losses'      => $stat?->losses ?? 0,
            'last_played' => $stat?->last_played_at,
        ]);
    }

    public function save(Request $request, string $slug): JsonResponse
    {
        $game      = Game::active()->where('slug', $slug)->firstOrFail();
        $service   = $this->resolveService($request->user(), $game);
        $gameState = $request->input('game_state', []);
        $score     = (int) $request->input('score', 0);
        $playtime  = max((int) $request->input('playtime', 0), 0);

        $service->saveProgress($gameState, $score, $playtime);

        // Construir datos de disparo para los logros
        $upgrades              = $gameState['upgrades'] ?? [];
        $upgradeValues         = array_values(array_map('intval', $upgrades));
        $totalUpgradesBought   = array_sum($upgradeValues);
        $maxUpgradeCount       = $upgradeValues ? max($upgradeValues) : 0;

        $achievementService  = new AchievementService();
        $newAchievements     = $achievementService->checkAndUnlock(
            $request->user(),
            $game->id,
            [
                'score'                 => $score,
                'upgrades'              => array_map('intval', $upgrades),
                'total_clicks'          => (int) ($gameState['total_clicks'] ?? 0),
                'prestige_level'        => (int) ($gameState['prestige_level'] ?? 0),
                'total_upgrades_bought' => $totalUpgradesBought,
                'max_upgrade_count'     => $maxUpgradeCount,
            ]
        );

        return response()->json([
            'saved'                 => true,
            'achievements_unlocked' => AchievementResource::collection(collect($newAchievements)),
        ]);
    }

    public function reset(Request $request, string $slug): JsonResponse
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        // Borrar el guardado
        \App\Models\GameSave::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->delete();

        // Resetear las stats del juego
        \App\Models\GameStat::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->delete();

        // Abandonar sesiones activas
        GameSession::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'abandoned', 'ended_at' => now()]);

        return response()->json(['reset' => true]);
    }

    public function complete(CompleteGameRequest $request, string $slug): JsonResponse
    {
        $game    = Game::active()->where('slug', $slug)->firstOrFail();
        $session = GameSession::findOrFail($request->input('session_id'));

        if ($session->user_id !== $request->user()->id || $session->game_id !== $game->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $service = $this->resolveService($request->user(), $game);
        $result  = $service->completeSession(
            $session,
            $request->input('final_score'),
            $request->input('duration'),
            $request->input('game_state', [])
        );

        return response()->json($result);
    }

    private function resolveService($user, Game $game): GameService
    {
        return match ($game->slug) {
            'clicker' => new ClickerGameService($user, $game),
            'battleship' => new BattleshipGameService($user, $game),
            default   => new ClickerGameService($user, $game), // fallback genérico
        };
    }
}

