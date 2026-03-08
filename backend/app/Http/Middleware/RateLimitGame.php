<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitGame
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $game = \App\Models\Game::where('slug', $request->route('slug'))->first();

        $limit = $game?->config['rate_limit_per_minute'] ?? 120;
        $key   = "game_rl:{$user->id}:" . ($game?->id ?? 0);

        $count = (int) Cache::get($key, 0);

        if ($count >= $limit) {
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }

        Cache::put($key, $count + 1, 60);

        return $next($request);
    }
}
