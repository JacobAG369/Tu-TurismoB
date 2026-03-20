<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Restaurante;
use App\Repositories\RestauranteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class RestauranteRepository extends BaseRepository implements RestauranteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Restaurante());
    }

    /**
     * Return lightweight markers for the map.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getMapMarkers(): Collection
    {
        return Restaurante::select(['_id', 'nombre', 'descripcion', 'ubicacion', 'direccion', 'telefono', 'horario', 'web', 'imagenes', 'rating', 'rating_promedio'])
            ->get()
            ->filter(fn (Restaurante $restaurante): bool => $this->hasValidGeoPoint($restaurante->ubicacion))
            ->map(fn (Restaurante $restaurante): array => [
                'id' => (string) $restaurante->_id,
                'nombre' => $restaurante->nombre,
                'descripcion' => $restaurante->descripcion,
                'ubicacion' => $restaurante->ubicacion,
                'direccion' => $restaurante->direccion,
                'telefono' => $restaurante->telefono,
                'horario' => $restaurante->horario,
                'sitio_web' => $restaurante->web,
                'imagen_url' => is_array($restaurante->imagenes) && count($restaurante->imagenes) > 0 ? $restaurante->imagenes[0] : null,
                'imagenes' => $restaurante->imagenes,
                'rating' => $restaurante->rating ?? $restaurante->rating_promedio ?? 0,
                'reviews_count' => 0, // TODO: add relationship to get actual count
                'tipo_recurso' => 'restaurante',
                'tipo' => 'restaurante',
            ])
            ->values();
    }

    /**
     * Return all Restaurantes.
     *
     * @return Collection<int, Restaurante>
     */
    public function all(): EloquentCollection
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
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): EloquentCollection
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

    private function hasValidGeoPoint(mixed $ubicacion): bool
    {
        return is_array($ubicacion)
            && ($ubicacion['type'] ?? null) === 'Point'
            && isset($ubicacion['coordinates'])
            && is_array($ubicacion['coordinates'])
            && count($ubicacion['coordinates']) === 2;
    }
}
