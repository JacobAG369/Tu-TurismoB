<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRestauranteRequest;
use App\Http\Requests\Api\V1\UpdateRestauranteRequest;
use App\Services\RestauranteService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestauranteController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RestauranteService $service,
    ) {}

    // ──────────────────────────────────────────────
    // GET /api/v1/restaurantes
    // Optional query params: ?lat=&lng=&radio= (radius search in metres)
    // ──────────────────────────────────────────────

    /**
     * List all Restaurantes, or search by geographic radius when coordinates are given.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled(['lat', 'lng', 'radio'])) {
            $restaurantes = $this->service->searchByRadius(
                lat:            (float) $request->input('lat'),
                lng:            (float) $request->input('lng'),
                radiusInMeters: (int)   $request->input('radio'),
            );

            return $this->success(
                data: $restaurantes,
                message: 'Restaurantes encontrados dentro del radio especificado.',
            );
        }

        return $this->success(
            data: $this->service->getAll(),
            message: 'Restaurantes obtenidos correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // GET /api/v1/restaurantes/{id}
    // ──────────────────────────────────────────────

    /**
     * Return a single Restaurante by its MongoDB _id.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $restaurante = $this->service->findById($id);
        } catch (ModelNotFoundException) {
            return $this->error('Restaurante no encontrado.', 404);
        }

        return $this->success(
            data: $restaurante,
            message: 'Restaurante obtenido correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // POST /api/v1/restaurantes
    // ──────────────────────────────────────────────

    /**
     * Validate, format GeoJSON, and persist a new Restaurante.
     */
    public function store(StoreRestauranteRequest $request): JsonResponse
    {
        $restaurante = $this->service->create($request->validated());

        return $this->success(
            data: $restaurante,
            message: 'Restaurante creado correctamente.',
            code: 201,
        );
    }

    // ──────────────────────────────────────────────
    // PUT /api/v1/restaurantes/{id}
    // ──────────────────────────────────────────────

    /**
     * Validate, rebuild GeoJSON if coordinates provided, and update Restaurante.
     */
    public function update(UpdateRestauranteRequest $request, string $id): JsonResponse
    {
        try {
            $restaurante = $this->service->update($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->error('Restaurante no encontrado.', 404);
        }

        return $this->success(
            data: $restaurante,
            message: 'Restaurante actualizado correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // DELETE /api/v1/restaurantes/{id}
    // ──────────────────────────────────────────────

    /**
     * Delete a Restaurante.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (ModelNotFoundException) {
            return $this->error('Restaurante no encontrado.', 404);
        }

        return $this->success(
            data: null,
            message: 'Restaurante eliminado correctamente.',
        );
    }
}
