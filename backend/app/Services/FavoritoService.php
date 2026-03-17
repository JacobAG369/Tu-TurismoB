<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\LugarRepositoryInterface;
use App\Repositories\EventoRepositoryInterface;
use App\Repositories\RestauranteRepositoryInterface;
use Illuminate\Support\Facades\Log;

class FavoritoService
{
    public function __construct(
        protected FavoritoRepositoryInterface $favoritoRepository,
        protected LugarRepositoryInterface $lugarRepository,
        protected EventoRepositoryInterface $eventoRepository,
        protected RestauranteRepositoryInterface $restauranteRepository
    ) {}

    /**
     * Get all favorites for a specific user.
     */
    public function getFavoritesByUser(string $userId)
    {
        return $this->favoritoRepository->getFavoritesByUser($userId, 0); // 0 means return all, or could pass pagination
    }

    /**
     * Toggles a favorite for a user.
     * Returns an array with the 'status' key: 'added' or 'removed'.
     *
     * @param string $userId
     * @param string $tipo
     * @param string $referenciaId
     * @return array
     * @throws \InvalidArgumentException
     */
    public function toggle(string $userId, string $tipo, string $referenciaId): array
    {
        // Validate that the reference exists
        $exists = false;
        switch ($tipo) {
            case 'lugar':
                $exists = (bool) $this->lugarRepository->find($referenciaId);
                break;
            case 'evento':
                $exists = (bool) $this->eventoRepository->find($referenciaId);
                break;
            case 'restaurante':
                $exists = (bool) $this->restauranteRepository->find($referenciaId);
                break;
        }

        if (!$exists) {
            throw new \InvalidArgumentException("La referencia '{$referenciaId}' no se encontró en la colección de '{$tipo}'.");
        }

        // Check if the favorite already exists
        $favorito = $this->favoritoRepository->findByUserAndReference($userId, $tipo, $referenciaId);

        if ($favorito) {
            // Remove it
            $this->favoritoRepository->delete($favorito->id);
            Log::info("Favorito removido: Tipo [{$tipo}], Referencia [{$referenciaId}], Usuario [{$userId}]");
            
            return ['status' => 'removed'];
        }

        // Add it
        $this->favoritoRepository->create([
            'usuario_id' => $userId,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
        ]);

        Log::info("Favorito agregado: Tipo [{$tipo}], Referencia [{$referenciaId}], Usuario [{$userId}]");

        return ['status' => 'added'];
    }
}
