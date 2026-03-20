<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUsuarioRequest;
use App\Http\Requests\Api\V1\UpdateUsuarioRequest;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Models\User;
use App\Services\UsuarioService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function index(): JsonResponse
    {
        $users = $this->service->getAll()->map(fn (User $user): array => $this->service->getProfile($user));

        return $this->success(
            data: $users,
            message: 'Usuarios obtenidos correctamente.',
        );
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        if ($this->serviceUserExistsByEmail($request->string('email')->value())) {
            return $this->error('Ya existe un usuario con este correo.', 422);
        }

        $user = $this->service->create($request->validated());

        return $this->success(
            data: $this->service->getProfile($user),
            message: 'Usuario creado correctamente.',
            code: 201,
        );
    }

    public function update(UpdateUsuarioRequest $request, string $id): JsonResponse
    {
        try {
            $user = $this->findUserById($id);
        } catch (ModelNotFoundException) {
            return $this->error('Usuario no encontrado.', 404);
        }

        $email = $request->string('email')->value();
        if ($email !== '' && $email !== $user->email && $this->serviceUserExistsByEmail($email)) {
            return $this->error('Ya existe un usuario con este correo.', 422);
        }

        $updated = $this->service->update($user, $request->validated());

        return $this->success(
            data: $this->service->getProfile($updated),
            message: 'Usuario actualizado correctamente.',
        );
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = $this->findUserById($id);
        } catch (ModelNotFoundException) {
            return $this->error('Usuario no encontrado.', 404);
        }

        $this->service->delete($user);

        return $this->success(
            data: null,
            message: 'Usuario eliminado correctamente.',
        );
    }

    private function findUserById(string $id): User
    {
        $user = User::find($id);

        if (! $user instanceof User) {
            throw (new ModelNotFoundException())->setModel(User::class, $id);
        }

        return $user;
    }

    private function serviceUserExistsByEmail(string $email): bool
    {
        return $this->service->findByEmail($email) instanceof User;
    }
}
