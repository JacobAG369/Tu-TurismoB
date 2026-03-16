<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

interface UsuarioRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Update profile fields for the given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(string $id, array $data): ?User;

    /**
     * Persist an already-hashed new password for the given user.
     */
    public function updatePassword(string $id, string $hashedPassword): ?User;
}
