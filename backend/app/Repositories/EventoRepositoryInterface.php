<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

interface EventoRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Return lightweight map markers from the eventos collection.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getMapMarkers(): Collection;

    /**
     * Return all Eventos.
     *
     * @return Collection<int, Evento>
     */
    public function all(): EloquentCollection;

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
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): EloquentCollection;
}
