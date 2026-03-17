<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Collection;

interface EventoRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Return all Eventos.
     *
     * @return Collection<int, Evento>
     */
    public function all(): Collection;

    /**
     * Find a single Evento by its MongoDB _id.
     */
    public function findById(string $id): ?Evento;

    /**
     * Return all Eventos within $radiusInMeters of the given coordinates.
     * Uses MongoDB $nearSphere geospatial operator.
     *
     * @return Collection<int, Evento>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection;
}
