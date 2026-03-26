<?php

// gestión de usuarios: crear, actualizar, cambiar contraseña. lo de siempre.

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UsuarioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UsuarioService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function getAll(): Collection
    {
        return $this->usuarios->getAllOrdered();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->usuarios->findByEmail($email);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $payload = $data;
        $payload['password'] = Hash::make((string) $data['password']);

        /** @var User $user */
        $user = $this->usuarios->create($payload);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $payload = $data;

        if (array_key_exists('password', $payload)) {
            if ($payload['password'] === null || $payload['password'] === '') {
                unset($payload['password']);
            } else {
                $payload['password'] = Hash::make((string) $payload['password']);
            }
        }

        /** @var User $updated */
        $updated = $this->usuarios->update((string) $user->_id, $payload);

        return $updated;
    }

    public function delete(User $user): void
    {
        $this->usuarios->delete((string) $user->_id);
    }

    // ──────────────────────────────────────────────
    // Profile
    // ──────────────────────────────────────────────

    /**
     * Return a safe, serialisable array of the user's public profile.
     *
     * @return array<string, mixed>
     */
    public function getProfile(User $user): array
    {
        return [
            'id'           => (string) $user->_id,
            'nombre'       => $user->nombre,
            'apellido'     => $user->apellido,
            'email'        => $user->email,
            'telefono'     => $user->telefono     ?? null,
            'direccion'    => $user->direccion     ?? null,
            'idioma'       => $user->idioma        ?? null,
            'imagen_perfil'=> $user->imagen_perfil ?? null,
            'rol'          => $user->rol,
        ];
    }

    /**
     * Update allowed profile fields and return the refreshed user.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $updated = $this->usuarios->updateProfile((string) $user->_id, $data);

        // updateProfile returns the refreshed model; fallback to original if null
        return $updated ?? $user;
    }

    // ──────────────────────────────────────────────
    // Password
    // ──────────────────────────────────────────────

    /**
     * Verify the current password and update to the new one (hashed).
     *
     * @throws \RuntimeException  When the current password is incorrect.
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \RuntimeException('La contraseña actual es incorrecta.');
        }

        $this->usuarios->updatePassword(
            (string) $user->_id,
            Hash::make($newPassword),
        );
    }
}
