<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Lugar;
use App\Repositories\LugarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

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
     * Return lightweight markers for the map.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getMapMarkers(): Collection
    {
        return Lugar::select(['_id', 'nombre', 'descripcion', 'ubicacion', 'direccion', 'imagenes', 'rating', 'rating_promedio'])
            ->get()
            ->filter(fn (Lugar $lugar): bool => $this->hasValidGeoPoint($lugar->ubicacion))
            ->map(fn (Lugar $lugar): array => [
                'id' => (string) $lugar->_id,
                'nombre' => $lugar->nombre,
                'descripcion' => $lugar->descripcion,
                'ubicacion' => $lugar->ubicacion,
                'direccion' => $lugar->direccion,
                'imagen_url' => is_array($lugar->imagenes) && count($lugar->imagenes) > 0 ? $lugar->imagenes[0] : null,
                'imagenes' => $lugar->imagenes,
                'rating' => $lugar->rating ?? $lugar->rating_promedio ?? 0,
                'reviews_count' => 0, // TODO: add relationship to get actual count
                'tipo_recurso' => 'lugar',
                'tipo' => 'lugar',
            ])
            ->values();
    }

    /**
     * Return all Lugares with their Categoria pre-loaded (avoids N+1).
     */
    public function all(): EloquentCollection
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
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): EloquentCollection
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

    private function hasValidGeoPoint(mixed $ubicacion): bool
    {
        return is_array($ubicacion)
            && ($ubicacion['type'] ?? null) === 'Point'
            && isset($ubicacion['coordinates'])
            && is_array($ubicacion['coordinates'])
            && count($ubicacion['coordinates']) === 2;
    }
}
