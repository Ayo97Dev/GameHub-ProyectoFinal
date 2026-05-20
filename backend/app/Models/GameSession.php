<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GAME SESSION MODEL
 * 
 * Registra una instancia única de ejecución de un juego.
 * Utilizado para telemetría, control de usuarios activos y auditoría de puntuaciones.
 */
class GameSession extends Model
{
    use HasFactory;

    /**
     * ATRIBUTOS ASIGNABLES
     */
    protected $fillable = [
        'user_id',
        'game_id',
        'session_data',
        'score',
        'duration_seconds',
        'status',
        'started_at',
        'ended_at',
    ];

    /**
     * CASTING DE ATRIBUTOS
     */
    protected function casts(): array
    {
        return [
            'session_data' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
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
     * RELACIÓN: JUEGO EJECUTADO
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
