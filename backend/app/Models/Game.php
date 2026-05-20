<?php

namespace App\Models;

/**
 * GAME MODEL
 * 
 * Representa un juego disponible en la plataforma.
 * Cada juego se identifica de forma única mediante su SLUG.
 */
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    /**
     * ATRIBUTOS ASIGNABLES
     * config: Almacena parámetros específicos del juego (dificultad, límites, etc).
     */
    protected $fillable = [
        'slug',
        'title',
        'category',
        'description',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * SCOPE: JUEGOS ACTIVOS
     * Filtra solo los juegos que deben mostrarse en el catálogo.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * RELACIÓN: ESTADÍSTICAS
     * Puntuaciones de todos los usuarios para este juego.
     */
    public function gameStats(): HasMany
    {
        return $this->hasMany(GameStat::class);
    }

    /**
     * RELACIÓN: PARTIDAS GUARDADAS
     */
    public function gameSaves(): HasMany
    {
        return $this->hasMany(GameSave::class);
    }

    /**
     * RELACIÓN: LOGROS ESPECÍFICOS
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    /**
     * RELACIÓN: SESIONES DE TELEMETRÍA
     */
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }
}
