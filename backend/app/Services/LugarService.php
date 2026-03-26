<?php

// CRUD de lugares con GeoJSON. longitude va primero. sí, es al revés de lo que piensas.

declare(strict_types=1);

namespace App\Services;

use App\Events\LugarCreado;
use App\Models\Lugar;
use App\Repositories\LugarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class LugarService
{
    public function __construct(
        private readonly LugarRepositoryInterface $lugares,
        private readonly ImageService $images,
    ) {}

    // ──────────────────────────────────────────────
    // Queries
    // ──────────────────────────────────────────────

    /**
     * @return Collection<int, Lugar>
     */
    public function getAll(): Collection
    {
        return $this->lugares->all();
    }

    /**
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
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     */
    public function create(array $data, ?UploadedFile $image = null): Lugar
    {
        $payload = $this->buildPayload($data, $image);

        /** @var Lugar $lugar */
        $lugar = $this->lugares->create($payload);

        LugarCreado::dispatch($lugar);

        return $lugar;
    }

    /**
     * @param array<string, mixed> $data
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function update(string $id, array $data, ?UploadedFile $image = null): Lugar
    {
        // verificar que existe antes de actualizar
        $this->findById($id);

        $payload = $this->buildPayload($data, $image);

        /** @var Lugar $lugar */
        $lugar = $this->lugares->update($id, $payload);

        return $lugar;
    }

    /**
     * @throws ModelNotFoundException
     */
    public function delete(string $id): void
    {
        $this->findById($id);  // lanza excepción si no existe

        $this->lugares->delete($id);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     * @throws InvalidArgumentException
     */
    private function buildPayload(array $data, ?UploadedFile $image = null): array
    {
        $payload = $data;

        if (array_key_exists('rating', $payload)) {
            $payload['rating'] = (float) $payload['rating'];
            $payload['rating_promedio'] = (float) $payload['rating'];
        }

        // validación y almacenamiento de imagen
        if ($image !== null) {
            $imageUrl = $this->images->store($image, 'lugares');
            $payload['imagenes'] = [$imageUrl];
        }

        if (isset($data['latitud'], $data['longitud'])) {
            $payload['ubicacion'] = [
                'type'        => 'Point',
                'coordinates' => [
                    (float) $data['longitud'],  // GeoJSON: longitud va primero
                    (float) $data['latitud'],
                ],
            ];
        }

        // eliminar campos crudos — se guardan dentro de `ubicacion`
        unset($payload['latitud'], $payload['longitud'], $payload['imagen']);

        return $payload;
    }
}
