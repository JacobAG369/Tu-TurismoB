<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Favorito;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FavoritoRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all favorites for a specific user.
     *
     * @param string $userId
     * @param int $perPage
     * @return LengthAwarePaginator|Collection
     */
    public function getFavoritesByUser(string $userId, int $perPage = 15);

    /**
     * Find a specific favorite by user and reference.
     *
     * @param string $userId
     * @param string $tipo
     * @param string $referenciaId
     * @return Favorito|null
     */
    public function findByUserAndReference(string $userId, string $tipo, string $referenciaId): ?Favorito;
}
