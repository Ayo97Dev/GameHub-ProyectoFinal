<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
    ];

    public function gameStats(): HasMany
    {
        return $this->hasMany(GameStat::class);
    }

    public function gameSaves(): HasMany
    {
        return $this->hasMany(GameSave::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }
}
