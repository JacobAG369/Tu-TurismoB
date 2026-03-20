<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventoRequest;
use App\Http\Requests\Api\V1\UpdateEventoRequest;
use App\Services\EventoService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EventoService $service,
    ) {}

    // ──────────────────────────────────────────────
    // GET /api/v1/eventos
    // Optional query params: ?lat=&lng=&radio= (radius search in metres)
    // ──────────────────────────────────────────────

    /**
     * List all Eventos, or search by geographic radius when coordinates are given.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled(['lat', 'lng', 'radio'])) {
            $eventos = $this->service->searchByRadius(
                lat:            (float) $request->input('lat'),
                lng:            (float) $request->input('lng'),
                radiusInMeters: (int)   $request->input('radio'),
            );

            return $this->success(
                data: $eventos,
                message: 'Eventos encontrados dentro del radio especificado.',
            );
        }

        return $this->success(
            data: $this->service->getAll(),
            message: 'Eventos obtenidos correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // GET /api/v1/eventos/{id}
    // ──────────────────────────────────────────────

    /**
     * Return a single Evento by its MongoDB _id.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $evento = $this->service->findById($id);
        } catch (ModelNotFoundException) {
            return $this->error('Evento no encontrado.', 404);
        }

        return $this->success(
            data: $evento,
            message: 'Evento obtenido correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // POST /api/v1/eventos
    // ──────────────────────────────────────────────

    /**
     * Validate, format GeoJSON, and persist a new Evento.
     */
    public function store(StoreEventoRequest $request): JsonResponse
    {
        $evento = $this->service->create($request->validated(), $request->file('imagen'));

        return $this->success(
            data: $evento,
            message: 'Evento creado correctamente.',
            code: 201,
        );
    }

    // ──────────────────────────────────────────────
    // PUT /api/v1/eventos/{id}
    // ──────────────────────────────────────────────

    /**
     * Validate, rebuild GeoJSON if coordinates provided, and update Evento.
     */
    public function update(UpdateEventoRequest $request, string $id): JsonResponse
    {
        try {
            $evento = $this->service->update($id, $request->validated(), $request->file('imagen'));
        } catch (ModelNotFoundException) {
            return $this->error('Evento no encontrado.', 404);
        }

        return $this->success(
            data: $evento,
            message: 'Evento actualizado correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // DELETE /api/v1/eventos/{id}
    // ──────────────────────────────────────────────

    /**
     * Delete an Evento.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (ModelNotFoundException) {
            return $this->error('Evento no encontrado.', 404);
        }

        return $this->success(
            data: null,
            message: 'Evento eliminado correctamente.',
        );
    }
}
