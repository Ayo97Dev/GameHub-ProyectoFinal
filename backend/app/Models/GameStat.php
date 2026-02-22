<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'high_score',
        'time_played',
        'last_played_at',
    ];

    protected function casts(): array
    {
        return [
            'last_played_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
