<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameStatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'game_id' => $this->game_id,
            'high_score' => $this->high_score,
            'time_played' => $this->time_played,
            'last_played_at' => $this->last_played_at,
            'game' => new GameResource($this->whenLoaded('game')),
        ];
    }
}
