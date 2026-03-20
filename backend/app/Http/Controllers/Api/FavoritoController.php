<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFavoritoRequest;
use App\Services\FavoritoService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoritoController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FavoritoService $favoritoService,
    ) {}

    /**
     * Get user's favorites.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (string) auth()->id();

        if ($userId === '') {
            return $this->error('Usuario no autenticado.', 401);
        }

        return $this->success(
            $this->favoritoService->getFavoritesByUser($userId),
            'Favoritos obtenidos con exito.',
        );
    }

    public function store(StoreFavoritoRequest $request): JsonResponse
    {
        try {
            $userId = (string) auth()->id();

            if ($userId === '') {
                return $this->error('Usuario no autenticado.', 401);
            }

            $data = $request->validated();

            $favorito = $this->favoritoService->store(
                $userId,
                $data['tipo'],
                $data['referencia_id'],
            );

            return $this->success($favorito, 'Favorito creado correctamente.', 201);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $code = $message === 'El recurso ya se encuentra en favoritos.' ? 409 : 404;

            return $this->error($message, $code);
        }
    }

    public function destroy(string $referenciaId): JsonResponse
    {
        $userId = (string) auth()->id();

        if ($userId === '') {
            return $this->error('Usuario no autenticado.', 401);
        }

        $deleted = $this->favoritoService->destroy($userId, $referenciaId);

        if (! $deleted) {
            return $this->error('Favorito no encontrado.', 404);
        }

        return $this->success([
            'referencia_id' => $referenciaId,
            'deleted' => true,
        ], 'Favorito eliminado correctamente.');
    }
}
