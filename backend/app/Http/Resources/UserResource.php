<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'global_stats' => GameStatResource::collection($this->whenLoaded('gameStats')),
            'inventory' => $this->whenLoaded('inventoryItems', function () {
                return $this->inventoryItems->pluck('quantity', 'item_key');
            }),
        ];
    }
}
