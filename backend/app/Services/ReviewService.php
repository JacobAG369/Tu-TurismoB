<?php

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
     * Store a new review.
     */
    public function store(array $data): array
    {
        // Check if the user already reviewed this place
        $existing = $this->reviewRepository->findByUserAndLugar(
            $data['usuario_id'],
            $data['lugar_id']
        );

        if ($existing) {
            throw new \InvalidArgumentException('El usuario ya ha dejado una reseña para este lugar.');
        }

        // Check if the place exists
        $lugar = $this->lugarRepository->find($data['lugar_id']);
        if (!$lugar) {
            throw new \InvalidArgumentException('El lugar especificado no existe.');
        }

        // Create the review
        $review = $this->reviewRepository->create($data);

        // Update average rating
        $this->updateLugarAverageRating($data['lugar_id']);

        Log::info("Review agregada: {$review->id} por usuario {$data['usuario_id']}");

        return ['review' => $review];
    }

    /**
     * Delete a review.
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

        // Update average rating
        $this->updateLugarAverageRating($lugarId);

        Log::info("Review eliminada: {$id} por usuario {$userId}");
    }

    /**
     * Get reviews by Lugar.
     */
    public function getByLugar(string $lugarId, int $perPage = 15)
    {
        // Simple validation that the place exists
        $lugar = $this->lugarRepository->find($lugarId);
        if (!$lugar) {
            throw new \InvalidArgumentException('El lugar especificado no existe.');
        }
        
        return $this->reviewRepository->getReviewsByLugar($lugarId, $perPage);
    }

    /**
     * Recalculates and updates the average rating of a Lugar.
     */
    protected function updateLugarAverageRating(string $lugarId): void
    {
        $newRating = $this->reviewRepository->getAverageRatingForLugar($lugarId);
        
        // ensure default rating of 0 instead of empty/null
        if (is_null($newRating)) {
            $newRating = 0.0;
        }

        $this->lugarRepository->update($lugarId, ['rating_promedio' => round($newRating, 2)]);
    }
}
