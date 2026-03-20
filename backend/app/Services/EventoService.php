<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\EventoCreado;
use App\Events\NuevoEventoPublicado;
use App\Models\Evento;
use App\Models\Notificacion;
use App\Repositories\EventoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class EventoService
{
    public function __construct(
        private readonly EventoRepositoryInterface $eventos,
        private readonly ImageService $images,
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
     * @throws InvalidArgumentException If image validation fails
     */
    public function create(array $data, ?UploadedFile $image = null): Evento
    {
        $payload = $this->buildPayload($data, $image);

        /** @var Evento $evento */
        $evento = $this->eventos->create($payload);

        Notificacion::create([
            'usuario_id' => null,
            'titulo' => 'Nuevo evento turistico disponible',
            'mensaje' => sprintf('Ya puedes explorar el evento "%s" en el mapa.', $evento->nombre),
            'leido' => false,
        ]);

        EventoCreado::dispatch($evento);
        NuevoEventoPublicado::dispatch($evento);

        return $evento;
    }

    /**
     * Update an Evento, rebuilding GeoJSON if coordinates are provided.
     *
     * @param array<string, mixed> $data
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException If image validation fails
     */
    public function update(string $id, array $data, ?UploadedFile $image = null): Evento
    {
        // Ensure the record exists before updating
        $this->findById($id);

        $payload = $this->buildPayload($data, $image);

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
    private function buildPayload(array $data, ?UploadedFile $image = null): array
    {
        $payload = $data;

        if (array_key_exists('rating', $payload)) {
            $payload['rating'] = (float) $payload['rating'];
        }

        if ($image !== null) {
            $payload['imagenes'] = [$this->images->store($image, 'eventos')];
        }

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
        unset($payload['latitud'], $payload['longitud'], $payload['imagen']);

        return $payload;
    }
}
