<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Restaurante;
use App\Repositories\RestauranteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RestauranteService
{
    public function __construct(
        private readonly RestauranteRepositoryInterface $restaurantes,
    ) {}

    // ──────────────────────────────────────────────
    // Queries
    // ──────────────────────────────────────────────

    /**
     * Return all Restaurantes.
     *
     * @return Collection<int, Restaurante>
     */
    public function getAll(): Collection
    {
        return $this->restaurantes->all();
    }

    /**
     * Find a single Restaurante or throw 404.
     *
     * @throws ModelNotFoundException
     */
    public function findById(string $id): Restaurante
    {
        $restaurante = $this->restaurantes->findById($id);

        if ($restaurante === null) {
            throw (new ModelNotFoundException())->setModel(Restaurante::class, $id);
        }

        return $restaurante;
    }

    /**
     * Return all Restaurantes within $radiusInMeters of the given point.
     *
     * @return Collection<int, Restaurante>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection
    {
        return $this->restaurantes->searchByRadius($lat, $lng, $radiusInMeters);
    }

    // ──────────────────────────────────────────────
    // Mutations
    // ──────────────────────────────────────────────

    /**
     * Build GeoJSON `ubicacion` from raw latitud/longitud and persist.
     *
     * GeoJSON spec: coordinates are [longitude, latitude] (lng FIRST).
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Restaurante
    {
        $payload = $this->buildPayload($data);

        /** @var Restaurante $restaurante */
        $restaurante = $this->restaurantes->create($payload);

        return $restaurante;
    }

    /**
     * Update a Restaurante, rebuilding GeoJSON if coordinates are provided.
     *
     * @param array<string, mixed> $data
     * @throws ModelNotFoundException
     */
    public function update(string $id, array $data): Restaurante
    {
        // Ensure the record exists before updating
        $this->findById($id);

        $payload = $this->buildPayload($data);

        /** @var Restaurante $restaurante */
        $restaurante = $this->restaurantes->update($id, $payload);

        return $restaurante;
    }

    /**
     * Delete a Restaurante by id.
     *
     * @throws ModelNotFoundException
     */
    public function delete(string $id): void
    {
        $this->findById($id);  // throws if not found

        $this->restaurantes->delete($id);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Build the persistence payload.
     * Converts latitud/longitud into a GeoJSON Point and removes the raw
     * coordinate fields so they are never stored as-is.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildPayload(array $data): array
    {
        $payload = $data;

        if (isset($data['latitud'], $data['longitud'])) {
            $payload['ubicacion'] = [
                'type'        => 'Point',
                'coordinates' => [
                    (float) $data['longitud'],  // GeoJSON: longitude first
                    (float) $data['latitud'],
                ],
            ];
        }

        // Remove raw coordinate keys — stored only inside `ubicacion`
        unset($payload['latitud'], $payload['longitud']);

        return $payload;
    }
}
