<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Lugar;
use Illuminate\Database\Eloquent\Collection;

interface LugarRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Return all Lugares with their categoria eager-loaded.
     */
    public function all(): Collection;

    /**
     * Find a single Lugar by its MongoDB _id, with categoria eager-loaded.
     */
    public function findById(string $id): ?Lugar;

    /**
     * Return all Lugares within $radiusInMeters of the given coordinates.
     * Uses MongoDB $nearSphere geospatial operator.
     *
     * @return Collection<int, Lugar>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection;
}
