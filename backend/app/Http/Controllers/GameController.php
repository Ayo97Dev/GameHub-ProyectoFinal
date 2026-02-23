<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index()
    {
        return GameResource::collection(Game::all());
    }

    public function show($slug)
    {
        $game = Game::where('slug', $slug)->firstOrFail();
        return new GameResource($game);
    }
}
