<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireConnect4LeaderboardAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route('slug') !== 'connect4') {
            return $next($request);
        }

        if (!auth('sanctum')->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
