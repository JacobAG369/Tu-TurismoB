<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Restaurante;
use App\Repositories\RestauranteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RestauranteRepository extends BaseRepository implements RestauranteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Restaurante());
    }

    /**
     * Return all Restaurantes.
     *
     * @return Collection<int, Restaurante>
     */
    public function all(): Collection
    {
        return Restaurante::all();
    }

    /**
     * Find a single Restaurante by MongoDB _id.
     */
    public function findById(string $id): ?Restaurante
    {
        /** @var Restaurante|null */
        return Restaurante::find($id);
    }

    // ──────────────────────────────────────────────
    // Geospatial
    // ──────────────────────────────────────────────

    /**
     * Return all Restaurantes within $radiusInMeters of [$lng, $lat].
     *
     * Requires a 2dsphere index on the `ubicacion` field.
     * GeoJSON coordinates order: [longitude, latitude].
     *
     * @return Collection<int, Restaurante>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection
    {
        return Restaurante::where('ubicacion', 'nearSphere', [
                '$geometry'    => [
                    'type'        => 'Point',
                    'coordinates' => [$lng, $lat],   // GeoJSON: lng first
                ],
                '$maxDistance' => $radiusInMeters,
            ])
            ->get();
    }
}
