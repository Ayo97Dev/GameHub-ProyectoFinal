<?php

namespace App\Models;

/**
 * USER MODEL
 * 
 * Representa a un jugador en la plataforma.
 * Gestiona la autenticación, perfil y relaciones con el progreso de juegos.
 */
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * ATRIBUTOS ASIGNABLES EN MASA
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'bio',
        'password',
    ];

    /**
     * ATRIBUTOS OCULTOS (SERIALIZACIÓN)
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * CASTING DE ATRIBUTOS
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ACCESOR DE AVATAR
     * Normaliza la URL del avatar. Si es una ruta local, añade el prefijo /storage/.
     * SIEMPRE debe devolver una URL válida o null.
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value 
                ? (str_starts_with($value, 'http') ? $value : '/storage/' . $value) 
                : null,
        );
    }

    /**
     * RELACIÓN: ESTADÍSTICAS DE JUEGO
     * Historial de puntuaciones y tiempo jugado por juego.
     */
    public function gameStats(): HasMany
    {
        return $this->hasMany(GameStat::class);
    }

    /**
     * RELACIÓN: PARTIDAS GUARDADAS
     * Almacena el estado serializado (JSON) de las partidas.
     */
    public function gameSaves(): HasMany
    {
        return $this->hasMany(GameSave::class);
    }

    /**
     * RELACIÓN: LOGROS (Achievements)
     * Muchos a muchos. Registra cuándo se obtuvo cada logro.
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class)
            ->withPivot('earned_at');
    }

    /**
     * RELACIÓN: SESIONES ACTIVAS
     * Seguimiento de partidas en curso para telemetría.
     */
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    /**
     * RELACIÓN: INVENTARIO
     * Items cosméticos o de juego desbloqueados por el usuario.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }
}
