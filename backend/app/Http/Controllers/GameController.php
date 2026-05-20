<?php

namespace App\Http\Controllers;

/**
 * GAME CONTROLLER
 * 
 * Orquestador principal de la ejecución de juegos.
 * Utiliza un patrón de ESTRATEGIA para delegar la lógica específica de cada juego 
 * a servicios especializados basados en el slug.
 */
use App\Http\Requests\CompleteGameRequest;
use App\Http\Requests\GameActionRequest;
use App\Http\Requests\PlayGameRequest;
use App\Http\Resources\AchievementResource;
use App\Http\Resources\GameResource;
use App\Models\Game;
use App\Models\GameSession;
use App\Services\AchievementService;
use App\Services\Games\ClickerGameService;
use App\Services\Games\Connect4GameService;
use App\Services\Games\TowerDefenseGameService;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * CATÁLOGO DE JUEGOS
     * Devuelve solo los juegos marcados como activos en la DB.
     */
    public function index()
    {
        return GameResource::collection(Game::active()->get());
    }

    public function show($slug)
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        return new GameResource($game);
    }

    /**
     * INICIAR PARTIDA
     * Inicializa el estado del juego, recuperando el progreso guardado si existe 
     * o generando el estado inicial por defecto.
     */
    public function play(PlayGameRequest $request, string $slug): JsonResponse
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        $service = $this->resolveService($request->user(), $game);

        $loadSave = $request->boolean('load_save', true);
        $progress = $loadSave ? $service->loadProgress() : null;
        $state = $progress ? $progress->payload : $service->getInitialState();

        // Registramos una nueva sesión para telemetría.
        $session = $service->createSession($state);

        return response()->json([
            'session_id' => $session->id,
            'game_state' => $state,
            'game' => new GameResource($game),
        ]);
    }

    /**
     * EJECUTAR ACCIÓN
     * Punto de entrada para cualquier interacción del usuario dentro del juego.
     * La lógica se delega al servicio correspondiente según el slug.
     */
    public function action(GameActionRequest $request, string $slug): JsonResponse
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        $service = $this->resolveService($request->user(), $game);

        $result = $service->executeAction(
            $request->input('action'),
            $request->input('payload')
        );

        if (isset($result['error'])) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
            'timestamp' => now()->getTimestampMs(),
        ]);
    }

    /**
     * CARGAR ESTADO
     */
    public function load(Request $request, string $slug): JsonResponse
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        $service = $this->resolveService($request->user(), $game);
        $progress = $service->loadProgress();

        if (! $progress) {
            return response()->json([
                'game_state' => $service->getInitialState(),
                'score' => 0,
                'playtime' => 0,
                'last_played' => null,
            ]);
        }

        $stat = $request->user()->gameStats()->where('game_id', $game->id)->first();

        return response()->json([
            'game_state' => $progress->payload,
            'score' => $stat?->high_score ?? 0,
            'playtime' => $stat?->time_played ?? 0,
            'last_played' => $stat?->last_played_at,
        ]);
    }

    /**
     * GUARDAR PROGRESO Y LOGROS
     * Persiste el estado actual y comprueba si se han desbloqueado nuevos logros.
     */
    public function save(Request $request, string $slug): JsonResponse
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        $service = $this->resolveService($request->user(), $game);
        $gameState = $request->input('game_state', []);
        $score = (int) $request->input('score', 0);
        $playtime = max((int) $request->input('playtime', 0), 0);
        $wins = max((int) ($gameState['wins'] ?? 0), 0);
        $losses = max((int) ($gameState['losses'] ?? 0), 0);

        // LÓGICA ESPECÍFICA: Connect4 basa su ranking solo en victorias.
        if ($game->slug === 'connect4') {
            $score = $wins;
            $gameState = ['wins' => $wins, 'losses' => $losses];
        }

        $service->saveProgress($gameState, $score, $playtime);

        // DISPARADOR DE LOGROS
        $triggerData = array_merge(
            [
                'score' => $score,
                'wins' => $wins,
                'losses' => $losses,
            ],
            $service->getGameMetadata($gameState)
        );

        $achievementService = new AchievementService;
        $newAchievements = $achievementService->checkAndUnlock(
            $request->user(),
            $game->id,
            $triggerData
        );

        return response()->json([
            'saved' => true,
            'achievements_unlocked' => AchievementResource::collection(collect($newAchievements)),
        ]);
    }

    /**
     * REINICIAR PROGRESO
     * Borra guardados, estadísticas y cierra sesiones activas.
     */
    public function reset(Request $request, string $slug): JsonResponse
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        \App\Models\GameSave::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->delete();

        \App\Models\GameStat::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->delete();

        GameSession::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'abandoned', 'ended_at' => now()]);

        return response()->json(['reset' => true]);
    }

    /**
     * FINALIZAR PARTIDA
     * Marca la sesión como completada y registra la puntuación final.
     */
    public function complete(CompleteGameRequest $request, string $slug): JsonResponse
    {
        $game = Game::active()->where('slug', $slug)->firstOrFail();
        $session = GameSession::findOrFail($request->input('session_id'));

        if ($session->user_id !== $request->user()->id || $session->game_id !== $game->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $service = $this->resolveService($request->user(), $game);
        $result = $service->completeSession(
            $session,
            $request->input('final_score'),
            $request->input('duration')
        );

        return response()->json($result);
    }

    /**
     * RESOLUCIÓN DE SERVICIO (Factory Pattern)
     * Determina qué clase de servicio manejará la lógica del juego basado en el slug.
     */
    private function resolveService($user, Game $game): GameService
    {
        return match ($game->slug) {
            'connect4' => new Connect4GameService($user, $game),
            'core-clicker' => new ClickerGameService($user, $game),
            'proyecto-cortafuegos' => new TowerDefenseGameService($user, $game),
            'descenso-al-abismo' => new \App\Services\Games\RpgGameService($user, $game),
            default => new ClickerGameService($user, $game), // Fallback genérico
        };
    }
}
