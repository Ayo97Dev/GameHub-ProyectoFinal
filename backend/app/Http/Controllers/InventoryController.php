<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    /**
     * Get user inventory.
     */
    public function index()
    {
        $items = Auth::user()->inventoryItems;
        
        // Return as key-value pairs for easy frontend consumption
        return response()->json(
            $items->pluck('quantity', 'item_key')->toArray()
        );
    }

    /**
     * Update (add/remove) items in inventory.
     */
    public function update(Request $request)
    {
        $request->validate([
            'item_key' => 'required|string',
            'quantity' => 'required|integer',
        ]);

        $user = Auth::user();
        $itemKey = $request->item_key;
        $quantity = $request->quantity;

        $inventoryItem = $user->inventoryItems()->where('item_key', $itemKey)->first();

        if ($inventoryItem) {
            $inventoryItem->quantity += $quantity;
            if ($inventoryItem->quantity < 0) $inventoryItem->quantity = 0;
            $inventoryItem->save();
        } else {
            $inventoryItem = $user->inventoryItems()->create([
                'item_key' => $itemKey,
                'quantity' => max(0, $quantity),
            ]);
        }

        return response()->json([
            'message' => 'Inventory updated',
            'item' => [
                'key' => $inventoryItem->item_key,
                'quantity' => $inventoryItem->quantity,
            ]
        ]);
    }

    /**
     * Bulk sync inventory (e.g., from localStorage on first login).
     */
    public function sync(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $user = Auth::user();
        $items = $request->items;

        foreach ($items as $key => $quantity) {
            $user->inventoryItems()->updateOrCreate(
                ['item_key' => $key],
                ['quantity' => $quantity]
            );
        }

        return response()->json([
            'message' => 'Inventory synced successfully',
            'inventory' => $user->inventoryItems->pluck('quantity', 'item_key')
        ]);
    }
}
