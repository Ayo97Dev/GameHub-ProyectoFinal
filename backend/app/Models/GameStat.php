<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GAME STAT MODEL
 * 
 * Almacena el rendimiento histórico de un usuario en un juego.
 * Incluye récords de puntuación y tiempo acumulado de juego.
 */
class GameStat extends Model
{
    use HasFactory;

    /**
     * ATRIBUTOS ASIGNABLES
     */
    protected $fillable = [
        'user_id',
        'game_id',
        'high_score',
        'time_played',
        'last_played_at',
    ];

    /**
     * CASTING DE ATRIBUTOS
     */
    protected function casts(): array
    {
        return [
            'last_played_at' => 'datetime',
        ];
    }

    /**
     * RELACIÓN: JUGADOR
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELACIÓN: JUEGO
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
