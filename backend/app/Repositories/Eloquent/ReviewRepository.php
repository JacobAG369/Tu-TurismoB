<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Review;
use App\Repositories\ReviewRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewRepository extends BaseRepository implements ReviewRepositoryInterface
{
    /**
     * Constructor to bind model to repository.
     *
     * @param Review $model
     */
    public function __construct(Review $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all reviews for a specific place.
     *
     * @param string $lugarId
     * @param int $perPage
     * @return LengthAwarePaginator|Collection
     */
    public function getReviewsByLugar(string $lugarId, int $perPage = 15)
    {
        // Load the related user so it comes with author details
        $query = $this->model->where('lugar_id', $lugarId)->with('usuario')->latest();
        
        return $perPage > 0 ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Find a review submitted by a user for a specific place.
     *
     * @param string $userId
     * @param string $lugarId
     * @return Review|null
     */
    public function findByUserAndLugar(string $userId, string $lugarId): ?Review
    {
        return $this->model
            ->where('usuario_id', $userId)
            ->where('lugar_id', $lugarId)
            ->first();
    }

    /**
     * Calculate average rating for a specific place.
     *
     * @param string $lugarId
     * @return float
     */
    public function getAverageRatingForLugar(string $lugarId): float
    {
        return (float) $this->model->where('lugar_id', $lugarId)->avg('rating');
    }
}
