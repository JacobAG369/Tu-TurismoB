<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Evento;
use App\Repositories\EventoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class EventoRepository extends BaseRepository implements EventoRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Evento());
    }

    /**
     * Return lightweight markers for the map.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getMapMarkers(): Collection
    {
        return Evento::select(['_id', 'nombre', 'descripcion', 'ubicacion', 'fecha', 'imagenes', 'rating', 'rating_promedio'])
            ->get()
            ->filter(fn (Evento $evento): bool => $this->hasValidGeoPoint($evento->ubicacion))
            ->map(fn (Evento $evento): array => [
                'id' => (string) $evento->_id,
                'nombre' => $evento->nombre,
                'descripcion' => $evento->descripcion,
                'ubicacion' => $evento->ubicacion,
                'fecha' => $evento->fecha,
                'imagen_url' => is_array($evento->imagenes) && count($evento->imagenes) > 0 ? $evento->imagenes[0] : null,
                'imagenes' => $evento->imagenes,
                'rating' => $evento->rating ?? $evento->rating_promedio ?? 0,
                'reviews_count' => 0, // TODO: add relationship to get actual count
                'tipo_recurso' => 'evento',
                'tipo' => 'evento',
            ])
            ->values();
    }

    /**
     * Return all Eventos.
     *
     * @return Collection<int, Evento>
     */
    public function all(): EloquentCollection
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
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): EloquentCollection
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

    private function hasValidGeoPoint(mixed $ubicacion): bool
    {
        return is_array($ubicacion)
            && ($ubicacion['type'] ?? null) === 'Point'
            && isset($ubicacion['coordinates'])
            && is_array($ubicacion['coordinates'])
            && count($ubicacion['coordinates']) === 2;
    }
}
