<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Models\User;
use App\Services\UsuarioService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UsuarioService $service,
    ) {}

    // ──────────────────────────────────────────────
    // GET /api/v1/user/profile
    // ──────────────────────────────────────────────

    /**
     * Return the authenticated user's full profile.
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            data: $this->service->getProfile($user),
            message: 'Perfil obtenido correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // PUT /api/v1/user/profile
    // ──────────────────────────────────────────────

    /**
     * Validate and update allowed profile fields.
     * Only the fields present in the request body are updated.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $updated = $this->service->updateProfile($user, $request->validated());

        return $this->success(
            data: $this->service->getProfile($updated),
            message: 'Perfil actualizado correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // PUT /api/v1/user/password
    // ──────────────────────────────────────────────

    /**
     * Validate the current password, then hash and store the new one.
     * Returns 422 if the current password does not match.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->service->updatePassword(
                user: $user,
                currentPassword: $request->string('current_password')->value(),
                newPassword: $request->string('new_password')->value(),
            );
        } catch (\RuntimeException $e) {
            return $this->error(
                message: $e->getMessage(),
                code: 422,
            );
        }

        return $this->success(
            data: null,
            message: 'Contraseña actualizada correctamente.',
        );
    }
}
