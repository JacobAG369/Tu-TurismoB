<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Favorito;
use Illuminate\Support\Collection;

interface FavoritoRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all favorites for a specific user.
     *
     * @return Collection<int, Favorito>
     */
    public function getFavoritesByUser(string $userId): Collection;

    /**
     * Find a specific favorite by user and reference.
     *
     * @param string $userId
     * @param string $tipo
     * @param string $referenciaId
     * @return Favorito|null
     */
    public function findByUserAndReference(string $userId, string $tipo, string $referenciaId): ?Favorito;

    public function findByUserAndResourceId(string $userId, string $referenciaId): ?Favorito;

    public function deleteByUserAndResourceId(string $userId, string $referenciaId): bool;
}
