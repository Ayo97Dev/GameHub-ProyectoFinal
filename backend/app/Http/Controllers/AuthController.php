<?php

namespace App\Http\Controllers;

/**
 * AUTH CONTROLLER
 * 
 * Gestiona el ciclo de vida de la sesión del usuario.
 * Utiliza Laravel Sanctum para la emisión y validación de tokens API.
 */
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * REGISTRO DE USUARIO
     * Crea un nuevo perfil y emite el primer token de acceso.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Cargamos relaciones para que el frontend reciba el perfil completo.
        $user->load(['gameStats.game', 'inventoryItems']);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * INICIO DE SESIÓN
     * Valida credenciales y genera un nuevo token.
     */
    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->load(['gameStats.game', 'inventoryItems']);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * CIERRE DE SESIÓN
     * Invalida el token actual en la base de datos.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * PERFIL DE USUARIO
     * Devuelve los datos del usuario autenticado con sus estadísticas y logros.
     */
    public function user(Request $request)
    {
        $user = $request->user()->load(['gameStats.game', 'inventoryItems']);
        return new UserResource($user);
    }
}
