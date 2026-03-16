<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Lugar;
use App\Repositories\LugarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LugarRepository extends BaseRepository implements LugarRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Lugar());
    }

    // ──────────────────────────────────────────────
    // Override base methods to eager-load `categoria`
    // ──────────────────────────────────────────────

    /**
     * Return all Lugares with their Categoria pre-loaded (avoids N+1).
     */
    public function all(): Collection
    {
        return Lugar::with('categoria')->get();
    }

    /**
     * Find a single Lugar by MongoDB _id with Categoria pre-loaded.
     */
    public function findById(string $id): ?Lugar
    {
        /** @var Lugar|null */
        return Lugar::with('categoria')->find($id);
    }

    // ──────────────────────────────────────────────
    // Geospatial
    // ──────────────────────────────────────────────

    /**
     * Return all Lugares within $radiusInMeters of [$lng, $lat].
     *
     * Requires a 2dsphere index on the `ubicacion` field.
     * GeoJSON coordinates order: [longitude, latitude].
     *
     * @return Collection<int, Lugar>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection
    {
        return Lugar::with('categoria')
            ->where('ubicacion', 'nearSphere', [
                '$geometry'    => [
                    'type'        => 'Point',
                    'coordinates' => [$lng, $lat],   // GeoJSON: lng first
                ],
                '$maxDistance' => $radiusInMeters,
            ])
            ->get();
    }
}
