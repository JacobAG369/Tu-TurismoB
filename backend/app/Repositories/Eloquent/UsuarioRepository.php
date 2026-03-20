<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\UsuarioRepositoryInterface;

class UsuarioRepository extends BaseRepository implements UsuarioRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new User());
    }

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User
    {
        /** @var User|null */
        return User::where('email', $email)->first();
    }

    public function getAllOrdered(): \Illuminate\Database\Eloquent\Collection
    {
        return User::orderByDesc('created_at')->get();
    }

    /**
     * Update allowed profile fields for the given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(string $id, array $data): ?User
    {
        /** @var User|null */
        $user = $this->update($id, $data);

        return $user;
    }

    /**
     * Persist an already-hashed new password for the given user.
     */
    public function updatePassword(string $id, string $hashedPassword): ?User
    {
        /** @var User|null */
        $user = $this->update($id, ['password' => $hashedPassword]);

        return $user;
    }
}
