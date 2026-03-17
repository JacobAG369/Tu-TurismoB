<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReviewRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all reviews for a specific place.
     *
     * @param string $lugarId
     * @param int $perPage
     * @return LengthAwarePaginator|Collection
     */
    public function getReviewsByLugar(string $lugarId, int $perPage = 15);

    /**
     * Find a review submitted by a user for a specific place.
     *
     * @param string $userId
     * @param string $lugarId
     * @return Review|null
     */
    public function findByUserAndLugar(string $userId, string $lugarId): ?Review;

    /**
     * Calculate average rating for a specific place.
     *
     * @param string $lugarId
     * @return float
     */
    public function getAverageRatingForLugar(string $lugarId): float;
}
