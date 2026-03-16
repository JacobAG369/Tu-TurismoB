<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLugarRequest;
use App\Http\Requests\Api\V1\UpdateLugarRequest;
use App\Services\LugarService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LugarController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LugarService $service,
    ) {}

    // ──────────────────────────────────────────────
    // GET /api/v1/lugares
    // Optional query params: ?lat=&lng=&radio= (radius search in metres)
    // ──────────────────────────────────────────────

    /**
     * List all Lugares, or search by geographic radius when coordinates are given.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled(['lat', 'lng', 'radio'])) {
            $lugares = $this->service->searchByRadius(
                lat:            (float) $request->input('lat'),
                lng:            (float) $request->input('lng'),
                radiusInMeters: (int)   $request->input('radio'),
            );

            return $this->success(
                data: $lugares,
                message: 'Lugares encontrados dentro del radio especificado.',
            );
        }

        return $this->success(
            data: $this->service->getAll(),
            message: 'Lugares obtenidos correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // GET /api/v1/lugares/{id}
    // ──────────────────────────────────────────────

    /**
     * Return a single Lugar by its MongoDB _id.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $lugar = $this->service->findById($id);
        } catch (ModelNotFoundException) {
            return $this->error('Lugar no encontrado.', 404);
        }

        return $this->success(
            data: $lugar,
            message: 'Lugar obtenido correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // POST /api/v1/lugares
    // ──────────────────────────────────────────────

    /**
     * Validate, format GeoJSON, and persist a new Lugar.
     * The ModelObserver will log the creation automatically.
     */
    public function store(StoreLugarRequest $request): JsonResponse
    {
        $lugar = $this->service->create($request->validated());

        return $this->success(
            data: $lugar,
            message: 'Lugar creado correctamente.',
            code: 201,
        );
    }

    // ──────────────────────────────────────────────
    // PUT /api/v1/lugares/{id}
    // ──────────────────────────────────────────────

    /**
     * Validate, rebuild GeoJSON if coordinates provided, and update Lugar.
     * The ModelObserver will log the update automatically.
     */
    public function update(UpdateLugarRequest $request, string $id): JsonResponse
    {
        try {
            $lugar = $this->service->update($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->error('Lugar no encontrado.', 404);
        }

        return $this->success(
            data: $lugar,
            message: 'Lugar actualizado correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // DELETE /api/v1/lugares/{id}
    // ──────────────────────────────────────────────

    /**
     * Delete a Lugar.
     * The ModelObserver will log the deletion automatically.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (ModelNotFoundException) {
            return $this->error('Lugar no encontrado.', 404);
        }

        return $this->success(
            data: null,
            message: 'Lugar eliminado correctamente.',
        );
    }
}
