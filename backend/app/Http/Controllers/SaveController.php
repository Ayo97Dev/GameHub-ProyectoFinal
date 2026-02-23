<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaveRequest;
use App\Http\Resources\GameSaveResource;
use App\Models\GameSave;
use Illuminate\Http\Request;

class SaveController extends Controller
{
    public function store(StoreSaveRequest $request, $gameId)
    {
        $save = GameSave::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'game_id' => $gameId,
            ],
            [
                'payload' => $request->validated('payload'),
            ]
        );

        return new GameSaveResource($save);
    }

    public function get(Request $request, $gameId)
    {
        $save = GameSave::where('user_id', $request->user()->id)
            ->where('game_id', $gameId)
            ->first();

        if (!$save) {
            return response()->json(['message' => 'No save found'], 404);
        }

        return new GameSaveResource($save);
    }
}
