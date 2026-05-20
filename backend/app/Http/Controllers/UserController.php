<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * USER CONTROLLER
 * 
 * Gestiona la información del perfil del usuario.
 * Incluye la actualización de datos biográficos, avatar y seguridad (contraseña).
 */
class UserController extends Controller
{
    /**
     * ACTUALIZAR PERFIL
     * Modifica el nombre, biografía e imagen de avatar del usuario autenticado.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ]);

        $user->name = $request->name;
        $user->bio = $request->bio;

        if ($request->hasFile('avatar')) {
            // Eliminamos el avatar antiguo si existe y es local.
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load(['gameStats.game']),
        ]);
    }

    /**
     * ACTUALIZAR CONTRASEÑA
     * Valida la contraseña actual y establece una nueva con confirmación.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }
}
