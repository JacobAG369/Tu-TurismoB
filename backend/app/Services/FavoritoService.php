<?php

// favoritos: porque a veces el usuario quiere guardar un lugar y nunca más volver. funciona igual.

declare(strict_types=1);

namespace App\Services;

use App\Models\Evento;
use App\Models\Favorito;
use App\Models\Lugar;
use App\Models\Restaurante;
use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\LugarRepositoryInterface;
use App\Repositories\EventoRepositoryInterface;
use App\Repositories\RestauranteRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FavoritoService
{
    public function __construct(
        private readonly FavoritoRepositoryInterface $favoritoRepository,
        private readonly LugarRepositoryInterface $lugarRepository,
        private readonly EventoRepositoryInterface $eventoRepository,
        private readonly RestauranteRepositoryInterface $restauranteRepository,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getFavoritesByUser(string $userId): Collection
    {
        return $this->favoritoRepository
            ->getFavoritesByUser($userId)
            ->map(fn (Favorito $favorito): array => $this->enrichFavorite($favorito));
    }

    /**
     * @return array<string, mixed>
     */
    public function store(string $userId, string $tipo, string $referenciaId): array
    {
        $resource = $this->findResource($tipo, $referenciaId);

        if ($resource === null) {
            throw new \RuntimeException('El recurso referenciado no existe.');
        }

        $existing = $this->favoritoRepository->findByUserAndReference($userId, $tipo, $referenciaId);

        if ($existing !== null) {
            throw new \RuntimeException('El recurso ya se encuentra en favoritos.');
        }

        $favorito = $this->favoritoRepository->create([
            'usuario_id' => $userId,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'fecha_guardado' => now(),
        ]);

        return $this->enrichFavorite($favorito);
    }

    public function destroy(string $userId, string $referenciaId): bool
    {
        return $this->favoritoRepository->deleteByUserAndResourceId($userId, $referenciaId);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichFavorite(Favorito $favorito): array
    {
        $resource = $this->findResource($favorito->tipo, $favorito->referencia_id);

        return [
            'id' => (string) $favorito->_id,
            'usuario_id' => $favorito->usuario_id,
            'tipo' => $favorito->tipo,
            'referencia_id' => $favorito->referencia_id,
            'fecha_guardado' => $favorito->fecha_guardado?->toIso8601String(),
            'recurso' => [
                'id' => $favorito->referencia_id,
                'nombre' => $resource?->nombre,
                'imagen' => $this->resolveImage($resource),
                'rating' => $this->resolveRating($resource),
            ],
        ];
    }

    private function findResource(string $tipo, string $referenciaId): Lugar|Evento|Restaurante|null
    {
        return match ($tipo) {
            'lugar' => $this->lugarRepository->findById($referenciaId),
            'evento' => $this->eventoRepository->findById($referenciaId),
            'restaurante' => $this->restauranteRepository->findById($referenciaId),
            default => null,
        };
    }

    private function resolveImage(?Model $resource): ?string
    {
        if ($resource === null) {
            return null;
        }

        $imagenes = $resource->imagenes ?? [];

        return is_array($imagenes) && isset($imagenes[0]) ? (string) $imagenes[0] : null;
    }

    private function resolveRating(?Model $resource): ?float
    {
        if ($resource === null) {
            return null;
        }

        if (isset($resource->rating_promedio)) {
            return (float) $resource->rating_promedio;
        }

        if (isset($resource->puntuacion_promedio)) {
            return (float) $resource->puntuacion_promedio;
        }

        return null;
    }
}
