<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Restaurante;
use Illuminate\Database\Eloquent\Collection;

interface RestauranteRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Return all Restaurantes.
     *
     * @return Collection<int, Restaurante>
     */
    public function all(): Collection;

    /**
     * Find a single Restaurante by its MongoDB _id.
     */
    public function findById(string $id): ?Restaurante;

    /**
     * Return all Restaurantes within $radiusInMeters of the given coordinates.
     * Uses MongoDB $nearSphere geospatial operator.
     *
     * @return Collection<int, Restaurante>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection;
}
