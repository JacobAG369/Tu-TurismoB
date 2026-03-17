<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Evento;
use App\Repositories\EventoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EventoService
{
    public function __construct(
        private readonly EventoRepositoryInterface $eventos,
    ) {}

    // ──────────────────────────────────────────────
    // Queries
    // ──────────────────────────────────────────────

    /**
     * Return all Eventos.
     *
     * @return Collection<int, Evento>
     */
    public function getAll(): Collection
    {
        return $this->eventos->all();
    }

    /**
     * Find a single Evento or throw 404.
     *
     * @throws ModelNotFoundException
     */
    public function findById(string $id): Evento
    {
        $evento = $this->eventos->findById($id);

        if ($evento === null) {
            throw (new ModelNotFoundException())->setModel(Evento::class, $id);
        }

        return $evento;
    }

    /**
     * Return all Eventos within $radiusInMeters of the given point.
     *
     * @return Collection<int, Evento>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection
    {
        return $this->eventos->searchByRadius($lat, $lng, $radiusInMeters);
    }

    // ──────────────────────────────────────────────
    // Mutations
    // ──────────────────────────────────────────────

    /**
     * Build GeoJSON `ubicacion` from raw latitud/longitud and persist.
     *
     * GeoJSON spec: coordinates are [longitude, latitude] (lng FIRST).
     *
     * @param array<string, mixed> $data Must contain latitud, longitud, nombre, descripcion, fecha, imagen.
     */
    public function create(array $data): Evento
    {
        $payload = $this->buildPayload($data);

        /** @var Evento $evento */
        $evento = $this->eventos->create($payload);

        return $evento;
    }

    /**
     * Update an Evento, rebuilding GeoJSON if coordinates are provided.
     *
     * @param array<string, mixed> $data
     * @throws ModelNotFoundException
     */
    public function update(string $id, array $data): Evento
    {
        // Ensure the record exists before updating
        $this->findById($id);

        $payload = $this->buildPayload($data);

        /** @var Evento $evento */
        $evento = $this->eventos->update($id, $payload);

        return $evento;
    }

    /**
     * Delete an Evento by id.
     *
     * @throws ModelNotFoundException
     */
    public function delete(string $id): void
    {
        $this->findById($id);  // throws if not found

        $this->eventos->delete($id);
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
