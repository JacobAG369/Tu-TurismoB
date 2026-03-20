<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Models\Sesion;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $service,
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
            'telefono' => $request->filled('telefono') ? $request->string('telefono')->trim()->value() : null,
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
        try {
            $data = $this->service->login($request, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->error(
                message: $e->getMessage(),
                code: 401,
            );
        }

        return $this->success(
            data: $data,
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
        Sesion::where('usuario_id', (string) $user->_id)->delete();

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

    /**
     * GET /api/v1/auth/session
     *
     * Returns the complete authenticated user object for frontend state.
     */
    public function session(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Transform _id to string manually if required, but the default serialization should handle it if casted or expected.
        // Actually, to keep it clean and similar to 'me', we can return the array representation.
        $userData = $user->toArray();
        $userData['id'] = (string) $user->_id;

        return $this->success(
            data: $userData,
            message: 'Sesión obtenida correctamente.',
        );
    }
}
