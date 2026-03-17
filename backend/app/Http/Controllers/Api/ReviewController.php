<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Services\ReviewService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Get all reviews for a specific place.
     */
    public function getByLugar(string $lugarId, Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $reviews = $this->reviewService->getByLugar($lugarId, $perPage);
            
            return $this->success($reviews, 'Reseñas obtenidas con éxito.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->error('Error al obtener reseñas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a new review.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        try {
            $userId = (string) auth()->id();
            if (!$userId) {
                return $this->error('Usuario no autenticado.', 401);
            }

            $data = $request->validated();
            $data['usuario_id'] = $userId;
            
            $result = $this->reviewService->store($data);
                
            return $this->success($result['review'], 'Reseña creada exitosamente.', 201);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400); // Bad Request (or 404 for Place not found)
        } catch (\Exception $e) {
            return $this->error('Error al crear reseña: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a review.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $userId = (string) auth()->id();
            if (!$userId) {
                return $this->error('Usuario no autenticado.', 401);
            }

            $this->reviewService->destroy($id, $userId);
            
            return $this->success(null, 'Reseña eliminada exitosamente.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400); // 400 or 403 or 404 based on exception message
        } catch (\Exception $e) {
            return $this->error('Error al eliminar reseña: ' . $e->getMessage(), 500);
        }
    }
}
