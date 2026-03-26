<?php

// reseñas de lugares. una por usuario. el rating se recalcula solo, no hay magia.

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReviewRepositoryInterface;
use App\Repositories\LugarRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    public function __construct(
        protected ReviewRepositoryInterface $reviewRepository,
        protected LugarRepositoryInterface $lugarRepository
    ) {}

    /**
     * Guarda una nueva reseña.
     */
    public function store(array $data): array
    {
        // verificar que el usuario no haya reseñado ya este lugar
        $existing = $this->reviewRepository->findByUserAndLugar(
            $data['usuario_id'],
            $data['lugar_id']
        );

        if ($existing) {
            throw new \InvalidArgumentException('El usuario ya ha dejado una reseña para este lugar.');
        }

        // verificar que el lugar existe
        $lugar = $this->lugarRepository->find($data['lugar_id']);
        if (!$lugar) {
            throw new \InvalidArgumentException('El lugar especificado no existe.');
        }

        // crear la reseña
        $review = $this->reviewRepository->create($data);

        // actualizar rating promedio
        $this->updateLugarAverageRating($data['lugar_id']);

        Log::info("Review agregada: {$review->id} por usuario {$data['usuario_id']}");

        return ['review' => $review];
    }

    /**
     * Elimina una reseña.
     */
    public function destroy(string $id, string $userId): void
    {
        $review = $this->reviewRepository->find($id);

        if (!$review) {
            throw new \InvalidArgumentException('La reseña no existe.');
        }

        if ($review->usuario_id !== $userId) {
            throw new \InvalidArgumentException('No tienes permiso para eliminar esta reseña.');
        }

        $lugarId = $review->lugar_id;
        $this->reviewRepository->delete($id);

        // actualizar rating promedio
        $this->updateLugarAverageRating($lugarId);

        Log::info("Review eliminada: {$id} por usuario {$userId}");
    }

    /**
     * Obtener reseñas por lugar.
     */
    public function getByLugar(string $lugarId, int $perPage = 15)
    {
        // validar que el lugar existe
        $lugar = $this->lugarRepository->find($lugarId);
        if (!$lugar) {
            throw new \InvalidArgumentException('El lugar especificado no existe.');
        }
        
        return $this->reviewRepository->getReviewsByLugar($lugarId, $perPage);
    }

    /**
     * Recalcula y actualiza el rating promedio de un Lugar.
     */
    protected function updateLugarAverageRating(string $lugarId): void
    {
        $newRating = $this->reviewRepository->getAverageRatingForLugar($lugarId);
        
        // por defecto 0 si no hay reseñas
        if (is_null($newRating)) {
            $newRating = 0.0;
        }

        $this->lugarRepository->update($lugarId, ['rating_promedio' => round($newRating, 2)]);
    }
}
