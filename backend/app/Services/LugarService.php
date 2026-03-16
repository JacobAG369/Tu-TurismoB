<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lugar;
use App\Repositories\LugarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LugarService
{
    public function __construct(
        private readonly LugarRepositoryInterface $lugares,
    ) {}

    // ──────────────────────────────────────────────
    // Queries
    // ──────────────────────────────────────────────

    /**
     * Return all Lugares (with categoria eager-loaded).
     *
     * @return Collection<int, Lugar>
     */
    public function getAll(): Collection
    {
        return $this->lugares->all();
    }

    /**
     * Find a single Lugar or throw 404.
     *
     * @throws ModelNotFoundException
     */
    public function findById(string $id): Lugar
    {
        $lugar = $this->lugares->findById($id);

        if ($lugar === null) {
            throw (new ModelNotFoundException())->setModel(Lugar::class, $id);
        }

        return $lugar;
    }

    /**
     * Return all Lugares within $radiusInMeters of the given point.
     *
     * @return Collection<int, Lugar>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection
    {
        return $this->lugares->searchByRadius($lat, $lng, $radiusInMeters);
    }

    // ──────────────────────────────────────────────
    // Mutations
    // ──────────────────────────────────────────────

    /**
     * Build GeoJSON `ubicacion` from raw latitud/longitud and persist.
     *
     * GeoJSON spec: coordinates are [longitude, latitude] (lng FIRST).
     *
     * @param array<string, mixed> $data  Must contain latitud, longitud, nombre, descripcion, categoria_id.
     */
    public function create(array $data): Lugar
    {
        $payload = $this->buildPayload($data);

        /** @var Lugar $lugar */
        $lugar = $this->lugares->create($payload);

        return $lugar;
    }

    /**
     * Update a Lugar, rebuilding GeoJSON if coordinates are provided.
     *
     * @param array<string, mixed> $data
     * @throws ModelNotFoundException
     */
    public function update(string $id, array $data): Lugar
    {
        // Ensure the record exists before updating
        $this->findById($id);

        $payload = $this->buildPayload($data);

        /** @var Lugar $lugar */
        $lugar = $this->lugares->update($id, $payload);

        return $lugar;
    }

    /**
     * Delete a Lugar by id.
     *
     * @throws ModelNotFoundException
     */
    public function delete(string $id): void
    {
        $this->findById($id);  // throws if not found

        $this->lugares->delete($id);
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
