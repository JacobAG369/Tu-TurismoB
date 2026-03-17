<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Evento;
use App\Repositories\EventoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EventoRepository extends BaseRepository implements EventoRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Evento());
    }

    /**
     * Return all Eventos.
     *
     * @return Collection<int, Evento>
     */
    public function all(): Collection
    {
        return Evento::all();
    }

    /**
     * Find a single Evento by MongoDB _id.
     */
    public function findById(string $id): ?Evento
    {
        /** @var Evento|null */
        return Evento::find($id);
    }

    // ──────────────────────────────────────────────
    // Geospatial
    // ──────────────────────────────────────────────

    /**
     * Return all Eventos within $radiusInMeters of [$lng, $lat].
     *
     * Requires a 2dsphere index on the `ubicacion` field.
     * GeoJSON coordinates order: [longitude, latitude].
     *
     * @return Collection<int, Evento>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection
    {
        return Evento::where('ubicacion', 'nearSphere', [
                '$geometry'    => [
                    'type'        => 'Point',
                    'coordinates' => [$lng, $lat],   // GeoJSON: lng first
                ],
                '$maxDistance' => $radiusInMeters,
            ])
            ->get();
    }
}
