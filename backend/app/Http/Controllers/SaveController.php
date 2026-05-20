<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaveRequest;
use App\Http\Resources\GameSaveResource;
use App\Models\GameSave;
use Illuminate\Http\Request;

/**
 * SAVE CONTROLLER
 * 
 * Gestiona la persistencia del estado de las partidas.
 * Permite guardar y recuperar el progreso serializado (payload) de cada juego.
 */
class SaveController extends Controller
{
    /**
     * GUARDAR PROGRESO
     * Crea o actualiza el punto de guardado para un usuario y juego específicos.
     */
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

    /**
     * RECUPERAR PROGRESO
     * Obtiene el último estado guardado del juego para el usuario.
     */
    public function get(Request $request, $gameId)
    {
        $save = GameSave::where('user_id', $request->user()->id)
            ->where('game_id', $gameId)
            ->first();

        if (! $save) {
            return response()->json(['message' => 'No save found'], 404);
        }

        return new GameSaveResource($save);
    }
}
