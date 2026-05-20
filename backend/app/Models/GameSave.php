<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GAME SAVE MODEL
 * 
 * Almacena el estado persistente de una partida (JSON).
 * Permite al usuario retomar el progreso en cualquier momento.
 */
class GameSave extends Model
{
    use HasFactory;

    /**
     * ATRIBUTOS ASIGNABLES
     */
    protected $fillable = [
        'user_id',
        'game_id',
        'payload',
    ];

    /**
     * CASTING DE ATRIBUTOS
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * RELACIÓN: PROPIETARIO
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
