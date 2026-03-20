<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EventoRepositoryInterface;
use App\Repositories\LugarRepositoryInterface;
use App\Repositories\RestauranteRepositoryInterface;
use Illuminate\Support\Collection;

class MapaService
{
    public function __construct(
        private readonly LugarRepositoryInterface $lugares,
        private readonly EventoRepositoryInterface $eventos,
        private readonly RestauranteRepositoryInterface $restaurantes,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getMarkers(?string $categoriaId): Collection
    {
        return match ($this->normalizeCategory($categoriaId)) {
            'lugares' => $this->lugares->getMapMarkers(),
            'eventos' => $this->eventos->getMapMarkers(),
            'restaurantes' => $this->restaurantes->getMapMarkers(),
            'monumentos' => $this->lugares->getMapMarkers(),
            'hoteles' => collect(),
            default => $this->lugares->getMapMarkers()
                ->concat($this->eventos->getMapMarkers())
                ->concat($this->restaurantes->getMapMarkers())
                ->values(),
        };
    }

    private function normalizeCategory(?string $categoriaId): string
    {
        $category = mb_strtolower(trim((string) $categoriaId));

        return $category === '' ? 'all' : $category;
    }
}
