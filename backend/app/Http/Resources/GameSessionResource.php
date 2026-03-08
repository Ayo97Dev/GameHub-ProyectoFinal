<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'game_id'          => $this->game_id,
            'status'           => $this->status,
            'score'            => $this->score,
            'duration_seconds' => $this->duration_seconds,
            'started_at'       => $this->started_at,
            'ended_at'         => $this->ended_at,
        ];
    }
}
