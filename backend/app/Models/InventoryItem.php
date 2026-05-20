<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * INVENTORY ITEM MODEL
 * 
 * Representa un recurso o consumible en el inventario del usuario.
 * Vincula una clave única (item_key) con una cantidad disponible.
 */
class InventoryItem extends Model
{
    use HasFactory;

    /**
     * ATRIBUTOS ASIGNABLES
     */
    protected $fillable = [
        'user_id',
        'item_key',
        'quantity',
    ];

    /**
     * RELACIÓN: DUEÑO DEL ITEM
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
