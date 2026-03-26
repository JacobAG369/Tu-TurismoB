<?php

// restaurantes: como LugarService pero con menú. misma lógica, distinta colección.

declare(strict_types=1);

namespace App\Services;

use App\Models\Restaurante;
use App\Repositories\RestauranteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class RestauranteService
{
    public function __construct(
        private readonly RestauranteRepositoryInterface $restaurantes,
        private readonly ImageService $images,
    ) {}

    // ──────────────────────────────────────────────
    // Queries
    // ──────────────────────────────────────────────

    /**
     * @return Collection<int, Restaurante>
     */
    public function getAll(): Collection
    {
        return $this->restaurantes->all();
    }

    /**
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
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     */
    public function create(array $data, ?UploadedFile $image = null): Restaurante
    {
        $payload = $this->buildPayload($data, $image);

        /** @var Restaurante $restaurante */
        $restaurante = $this->restaurantes->create($payload);

        return $restaurante;
    }

    /**
     * @param array<string, mixed> $data
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function update(string $id, array $data, ?UploadedFile $image = null): Restaurante
    {
        // verificar que existe antes de actualizar
        $this->findById($id);

        $payload = $this->buildPayload($data, $image);

        /** @var Restaurante $restaurante */
        $restaurante = $this->restaurantes->update($id, $payload);

        return $restaurante;
    }

    /**
     * @throws ModelNotFoundException
     */
    public function delete(string $id): void
    {
        $this->findById($id);  // lanza excepción si no existe

        $this->restaurantes->delete($id);
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
            $imageUrl = $this->images->store($image, 'restaurantes');
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
