<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'username' => $this->user->name ?? 'Unknown',
            'avatar' => $this->user->avatar ?? null,
            'high_score' => $this->high_score,
            'time_played' => $this->time_played,
        ];
    }
}
