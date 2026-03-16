<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Models\Sesion;
use App\Models\User;
use App\Services\VigenereService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly VigenereService $vigenere,
    ) {}

    // ──────────────────────────────────────────────
    // Public endpoints
    // ──────────────────────────────────────────────

    /**
     * POST /api/v1/auth/register
     *
     * Validates and creates a new user account.
     * Passwords are hashed automatically via the User model's cast.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'nombre'   => $request->string('nombre')->trim()->value(),
            'apellido' => $request->string('apellido')->trim()->value(),
            'email'    => mb_strtolower($request->string('email')->trim()->value()),
            'password' => $request->string('password')->value(),
            'rol'      => 'turista',
        ]);

        return $this->success(
            data: ['id' => (string) $user->_id, 'email' => $user->email],
            message: 'Usuario registrado correctamente.',
            code: 201,
        );
    }

    /**
     * POST /api/v1/auth/login
     *
     * Validates credentials, issues a Sanctum token, encrypts it using the
     * Vigenere cipher, and persists the session record in MongoDB.
     * Returns the *plain* token to the client for use in Authorization headers.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = [
            'email'    => mb_strtolower($request->string('email')->trim()->value()),
            'password' => $request->string('password')->value(),
        ];

        if (! Auth::attempt($credentials)) {
            return $this->error(
                message: 'Credenciales incorrectas.',
                code: 401,
            );
        }

        /** @var User $user */
        $user = Auth::user();

        // Revoke all previous tokens to enforce single active session
        $user->tokens()->delete();

        // Also remove any leftover session records for this user
        Sesion::where('user_id', (string) $user->_id)->delete();

        // Generate a new Sanctum token
        $tokenResult  = $user->createToken('api-token');
        $plainToken   = $tokenResult->plainTextToken;

        // Encrypt the token string for server-side storage
        $vigenereKey      = (string) config('app.vigenere_key');
        $encryptedToken   = $this->vigenere->encrypt($plainToken, $vigenereKey);

        // Persist the encrypted session record
        Sesion::create([
            'user_id'         => (string) $user->_id,
            'encrypted_token' => $encryptedToken,
            'ip'              => $request->ip() ?? 'unknown',
            'device'          => $request->userAgent() ?? 'unknown',
            'expires_at'      => now()->addMinutes((int) config('sanctum.expiration', 43200)),
        ]);

        return $this->success(
            data: [
                'token'      => $plainToken,
                'token_type' => 'Bearer',
                'user'       => [
                    'id'       => (string) $user->_id,
                    'nombre'   => $user->nombre,
                    'apellido' => $user->apellido,
                    'email'    => $user->email,
                    'rol'      => $user->rol,
                ],
            ],
            message: 'Inicio de sesión exitoso.',
        );
    }

    // ──────────────────────────────────────────────
    // Protected endpoints  (auth:sanctum required)
    // ──────────────────────────────────────────────

    /**
     * POST /api/v1/auth/logout
     *
     * Revokes the current Sanctum token and deletes the sesion record.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Delete matching session from the sesiones collection
        Sesion::where('user_id', (string) $user->_id)->delete();

        // Revoke the current token
        $user->currentAccessToken()->delete();

        return $this->success(
            data: null,
            message: 'Sesión cerrada correctamente.',
        );
    }

    /**
     * GET /api/v1/auth/me
     *
     * Returns the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            data: [
                'id'       => (string) $user->_id,
                'nombre'   => $user->nombre,
                'apellido' => $user->apellido,
                'email'    => $user->email,
                'rol'      => $user->rol,
            ],
            message: 'Perfil obtenido correctamente.',
        );
    }
}
