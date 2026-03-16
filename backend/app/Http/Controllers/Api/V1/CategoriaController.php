<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCategoriaRequest;
use App\Http\Requests\Api\V1\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoriaController extends Controller
{
    use ApiResponse;

    // ──────────────────────────────────────────────
    // GET /api/v1/categorias
    // ──────────────────────────────────────────────

    /**
     * List all categories (public endpoint).
     */
    public function index(): JsonResponse
    {
        $categorias = Categoria::all();

        return $this->success(
            data: $categorias,
            message: 'Categorías obtenidas correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // GET /api/v1/categorias/{id}
    // ──────────────────────────────────────────────

    /**
     * Return a single Categoria (public endpoint).
     */
    public function show(string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if ($categoria === null) {
            return $this->error('Categoría no encontrada.', 404);
        }

        return $this->success(
            data: $categoria,
            message: 'Categoría obtenida correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // POST /api/v1/categorias
    // ──────────────────────────────────────────────

    /**
     * Create a new Categoria.
     * ModelObserver (via LogsActivity trait) logs the creation automatically.
     */
    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $categoria = Categoria::create($request->validated());

        return $this->success(
            data: $categoria,
            message: 'Categoría creada correctamente.',
            code: 201,
        );
    }

    // ──────────────────────────────────────────────
    // PUT /api/v1/categorias/{id}
    // ──────────────────────────────────────────────

    /**
     * Update an existing Categoria.
     * ModelObserver logs the update automatically.
     */
    public function update(UpdateCategoriaRequest $request, string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if ($categoria === null) {
            return $this->error('Categoría no encontrada.', 404);
        }

        $categoria->update($request->validated());

        return $this->success(
            data: $categoria->fresh(),
            message: 'Categoría actualizada correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // DELETE /api/v1/categorias/{id}
    // ──────────────────────────────────────────────

    /**
     * Delete a Categoria.
     * ModelObserver logs the deletion automatically.
     */
    public function destroy(string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if ($categoria === null) {
            return $this->error('Categoría no encontrada.', 404);
        }

        $categoria->delete();

        return $this->success(
            data: null,
            message: 'Categoría eliminada correctamente.',
        );
    }
}
