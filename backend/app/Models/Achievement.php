<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ACHIEVEMENT MODEL
 * 
 * Representa un logro o trofeo desbloqueable en la plataforma.
 * Contiene la lógica de condiciones y recompensas asociadas.
 */
class Achievement extends Model
{
    use HasFactory;

    /**
     * ATRIBUTOS ASIGNABLES
     */
    protected $fillable = [
        'slug',
        'title',
        'description',
        'game_id',
        'icon_url',
        'points_reward',
        'rarity',
        'condition',
        'is_active',
    ];

    /**
     * CASTING DE ATRIBUTOS
     */
    protected function casts(): array
    {
        return [
            'condition' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * RELACIÓN: JUEGO ASOCIADO
     * Un logro puede pertenecer a un juego específico o ser global (null).
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * RELACIÓN: USUARIOS QUE LO POSEEN
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('earned_at');
    }
}
