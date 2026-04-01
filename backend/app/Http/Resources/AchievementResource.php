<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    private ?string $earnedAt;

    public function __construct($resource, ?string $earnedAt = null)
    {
        parent::__construct($resource);
        $this->earnedAt = $earnedAt;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'icon_url' => $this->icon_url,
            'points_reward' => $this->points_reward,
            'rarity' => $this->rarity,
            'game_id' => $this->game_id,
            'unlocked' => $this->earnedAt !== null,
            'earned_at' => $this->earnedAt,
        ];
    }
}
